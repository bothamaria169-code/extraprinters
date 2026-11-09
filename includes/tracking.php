<?php
declare(strict_types=1);

/**
 * Google Analytics 4, Google Ads conversions and the Meta Pixel, plus the
 * Meta Conversions API copy of each lead (counts even when an ad blocker
 * stops the pixel). IDs go in Admin → Settings → Google / Meta.
 *
 * Browser events: elements with data-track="whatsapp_click" etc. and the
 * xpTrack() calls in app.js. Names match GA4's recommended events:
 * add_to_cart, file_upload, generate_lead, whatsapp_click.
 */

function trackingHead(): string
{
    if (IS_LOCAL && get_setting('track_local') !== '1') return '<script>window.xpTrack=function(){};</script>';
    $ga   = preg_replace('/[^A-Z0-9-]/', '', strtoupper(get_setting('ga4_id')));
    $ads  = preg_replace('/[^A-Z0-9-]/', '', strtoupper(get_setting('gads_id')));
    $lead = preg_replace('/[^\w-]/', '', get_setting('gads_label_lead'));
    $wa   = preg_replace('/[^\w-]/', '', get_setting('gads_label_whatsapp'));
    $px   = preg_replace('/\D/', '', get_setting('meta_pixel_id'));
    $out  = '';

    if ($ga || $ads) {
        $first = $ga ?: $ads;
        $out .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$first}\"></script>"
              . "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());"
              . ($ga ? "gtag('config','{$ga}');" : '') . ($ads ? "gtag('config','{$ads}');" : '') . '</script>';
    }
    if ($px) {
        $out .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};"
              . "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];"
              . "s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$px}');fbq('track','PageView');</script>";
    }
    $map = json_encode(['lead' => $ads && $lead ? "{$ads}/{$lead}" : '', 'whatsapp' => $ads && $wa ? "{$ads}/{$wa}" : '']);
    // One function for every event: GA4 + Ads conversion + Meta standard event.
    $out .= "<script>window.xpTrack=function(ev,p){p=p||{};var c={$map};"
          . "if(window.gtag){gtag('event',ev,p);if(ev==='generate_lead'&&c.lead)gtag('event','conversion',{send_to:c.lead,value:250,currency:'ZAR',transaction_id:p.ref||''});"
          . "if(ev==='whatsapp_click'&&c.whatsapp)gtag('event','conversion',{send_to:c.whatsapp,value:100,currency:'ZAR'});}"
          . "if(window.fbq){var m={add_to_cart:'AddToCart',generate_lead:'Lead',whatsapp_click:'Contact',view_item:'ViewContent'}[ev];if(m)fbq('track',m,p,p.ref?{eventID:'lead-'+p.ref}:undefined);}};</script>";
    return $out;
}

/** Server copy of a Lead for the Meta Conversions API (same event id as the pixel). */
function metaCapiLead(array $quote): void
{
    $px = preg_replace('/\D/', '', get_setting('meta_pixel_id'));
    $token = trim(get_setting('meta_capi_token'));
    if ($px === '' || $token === '') return;
    $h = fn(string $v) => $v === '' ? null : hash('sha256', strtolower(trim($v)));
    $phone = preg_replace('/\D/', '', (string) $quote['phone']);
    if (str_starts_with($phone, '0')) $phone = '27' . substr($phone, 1);
    $body = ['data' => [[
        'event_name' => 'Lead', 'event_time' => time(), 'event_id' => 'lead-' . $quote['ref'], 'action_source' => 'website',
        'event_source_url' => APP_URL . '/checkout.php',
        'user_data' => array_filter(['em' => [$h((string) $quote['email'])], 'ph' => $phone ? [$h($phone)] : null,
            'client_ip_address' => clientIp(), 'client_user_agent' => userAgent(), 'country' => [$h('za')]]),
        'custom_data' => ['currency' => 'ZAR', 'value' => 250, 'content_name' => $quote['type']],
    ]]];
    $ch = curl_init('https://graph.facebook.com/v21.0/' . $px . '/events?access_token=' . rawurlencode($token));
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4]);
    $res = (string) curl_exec($ch);
    if ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) error_log('Extra Printers CAPI: ' . mb_substr($res, 0, 300));
    curl_close($ch);
}
