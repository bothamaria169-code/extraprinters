<?php
declare(strict_types=1);

/**
 * Sends product photo posts to the Extra Printers TikTok account through
 * TikTok's Content Posting API (SWIFTDNA, adapted from InvoiceChat's video
 * poster).
 *
 * Mode "upload to inbox" (scope video.upload): the post arrives in the TikTok
 * app's inbox as a draft; open it, add a trending sound and press Post. This
 * is all a Sandbox / unaudited app may do, so it works from day one.
 *
 * TikTok downloads photos itself (PULL_FROM_URL), so the photo address must
 * be on a domain verified in the TikTok developer portal (Manage URL
 * properties → extraprinters.co.za). It works on the live site only.
 */
final class TikTok
{
    private const AUTH  = 'https://www.tiktok.com/v2/auth/authorize/';
    private const API   = 'https://open.tiktokapis.com/v2/';
    public const SCOPES = 'user.info.basic,video.upload';

    /** @return array{client_key: string, client_secret: string, token: string, refresh: string, expires: int, name: string} */
    public static function config(): array
    {
        return [
            'client_key'    => trim(get_setting('tiktok_client_key')),
            'client_secret' => trim(get_setting('tiktok_client_secret')),
            'token'         => get_setting('tiktok_access_token'),
            'refresh'       => get_setting('tiktok_refresh_token'),
            'expires'       => (int) get_setting('tiktok_expires_at', '0'),
            'name'          => get_setting('tiktok_name'),
        ];
    }

    public static function ready(): bool
    {
        $c = self::config();
        return $c['client_key'] !== '' && $c['client_secret'] !== '';
    }

    public static function connected(): bool
    {
        return self::ready() && self::config()['refresh'] !== '';
    }

    /** Must be listed under Login Kit → Redirect URI in the TikTok developer portal, exactly. */
    public static function redirectUri(): string
    {
        return CANONICAL_URL . '/admin/tiktok-connect.php';
    }

    public static function loginUrl(string $state): string
    {
        return self::AUTH . '?' . http_build_query([
            'client_key'    => self::config()['client_key'],
            'scope'         => self::SCOPES,
            'response_type' => 'code',
            'redirect_uri'  => self::redirectUri(),
            'state'         => $state,
        ]);
    }

    /** Swaps the code TikTok sends back for tokens. Returns an error message, or '' on success. */
    public static function exchangeCode(string $code): string
    {
        $c = self::config();
        $r = self::form('oauth/token/', [
            'client_key' => $c['client_key'], 'client_secret' => $c['client_secret'],
            'code' => $code, 'grant_type' => 'authorization_code', 'redirect_uri' => self::redirectUri(),
        ]);
        if (empty($r['access_token'])) {
            return 'TikTok did not give us access: ' . self::error($r);
        }
        self::saveTokens($r);
        $me = self::json('GET', 'user/info/?fields=open_id,display_name', null);
        set_setting('tiktok_name', (string) ($me['data']['user']['display_name'] ?? ''));
        return '';
    }

    public static function disconnect(): void
    {
        foreach (['tiktok_access_token', 'tiktok_refresh_token', 'tiktok_expires_at', 'tiktok_name'] as $k) {
            set_setting($k, '');
        }
    }

    private static function saveTokens(array $r): void
    {
        set_setting('tiktok_access_token', (string) $r['access_token']);
        set_setting('tiktok_refresh_token', (string) ($r['refresh_token'] ?? self::config()['refresh']));
        set_setting('tiktok_expires_at', (string) (time() + (int) ($r['expires_in'] ?? 86400) - 300));
    }

    /** A working access token: they last a day and are refreshed (the refresh token lasts a year). */
    private static function token(): string
    {
        $c = self::config();
        if ($c['token'] !== '' && $c['expires'] > time()) {
            return $c['token'];
        }
        if ($c['refresh'] === '') return '';
        $r = self::form('oauth/token/', [
            'client_key' => $c['client_key'], 'client_secret' => $c['client_secret'],
            'grant_type' => 'refresh_token', 'refresh_token' => $c['refresh'],
        ]);
        if (empty($r['access_token'])) return '';
        self::saveTokens($r);
        return (string) $r['access_token'];
    }

    /**
     * Sends a photo post (1–35 public JPG/WEBP URLs on the verified domain) to
     * the TikTok inbox as a draft.
     *
     * @param string[] $photoUrls
     * @return array{ok: bool, id: string, error: string}
     */
    public static function postPhotos(array $photoUrls, string $title, string $caption): array
    {
        if (!self::connected()) {
            return ['ok' => false, 'id' => '', 'error' => 'TikTok is not connected.'];
        }
        if (self::token() === '') {
            return ['ok' => false, 'id' => '', 'error' => 'TikTok sign-in has expired. Press Connect TikTok again.'];
        }
        $photoUrls = array_values(array_slice(array_filter($photoUrls, fn($u) => str_starts_with($u, 'https://')), 0, 35));
        if (!$photoUrls) {
            return ['ok' => false, 'id' => '', 'error' => 'TikTok needs a public https photo (it works on the live site only).'];
        }

        $init = self::json('POST', 'post/publish/content/init/', [
            'post_info'   => ['title' => mb_substr($title, 0, 90), 'description' => mb_substr($caption, 0, 4000)],
            'source_info' => ['source' => 'PULL_FROM_URL', 'photo_cover_index' => 0, 'photo_images' => $photoUrls],
            'post_mode'   => 'MEDIA_UPLOAD',
            'media_type'  => 'PHOTO',
        ]);
        $publishId = (string) ($init['data']['publish_id'] ?? '');
        if ($publishId === '') {
            error_log('TikTok photo init failed: ' . json_encode($init));
            $msg = self::error($init);
            if (preg_match('/url_ownership|domain|verify/i', $msg)) {
                $msg .= '. Verify extraprinters.co.za in the TikTok developer portal: Manage URL properties.';
            }
            return ['ok' => false, 'id' => '', 'error' => 'TikTok said: ' . $msg];
        }

        // TikTok downloads the photos after accepting the request; a bad photo fails here.
        for ($try = 0; $try < 6; $try++) {
            sleep(4);
            $r = self::json('POST', 'post/publish/status/fetch/', ['publish_id' => $publishId]);
            $status = (string) ($r['data']['status'] ?? 'UNKNOWN');
            if ($status === 'FAILED') {
                return ['ok' => false, 'id' => $publishId, 'error' => 'TikTok rejected the post: ' . ($r['data']['fail_reason'] ?? 'no reason given') . '.'];
            }
            if (in_array($status, ['SEND_TO_USER_INBOX', 'PUBLISH_COMPLETE'], true)) break;
        }
        return ['ok' => true, 'id' => $publishId, 'error' => ''];
    }

    private static function form(string $path, array $fields): array
    {
        $ch = curl_init(self::API . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        ]);
        $body = (string) curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        return $err !== '' ? ['error' => ['message' => $err]] : (json_decode($body, true) ?: ['error' => ['message' => 'Unexpected reply from TikTok']]);
    }

    private static function json(string $method, string $path, ?array $payload): array
    {
        $ch = curl_init(self::API . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . self::token(), 'Content-Type: application/json; charset=UTF-8'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        ] + ($payload !== null ? [CURLOPT_POSTFIELDS => json_encode($payload)] : []));
        $body = (string) curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        return $err !== '' ? ['error' => ['message' => $err]] : (json_decode($body, true) ?: ['error' => ['message' => 'Unexpected reply from TikTok']]);
    }

    private static function error(array $r): string
    {
        $e = $r['error'] ?? null;
        if (is_array($e) && ($e['code'] ?? '') === 'ok') return 'no reason given';
        return (string) (is_array($e) ? ($e['message'] ?: ($e['code'] ?? 'no reason given')) : ($r['error_description'] ?? $e ?? 'no reason given'));
    }
}
