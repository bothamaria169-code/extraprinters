<?php
declare(strict_types=1);

/**
 * Structured data (schema.org) and the local SEO landing pages.
 * Main focus (client brief): INVOICE BOOKS and PAPER SALES in Johannesburg.
 */

const AREAS = ['Johannesburg CBD', 'Braamfontein', 'Sandton', 'Randburg', 'Rosebank', 'Roodepoort', 'Soweto', 'Midrand', 'Fourways', 'Germiston', 'Kempton Park', 'Alberton', 'Booysens', 'City Deep'];

function schemaLocalBusiness(): array
{
    $s = [
        '@context' => 'https://schema.org', '@type' => ['LocalBusiness', 'Store'], '@id' => CANONICAL_URL . '/#business',
        'name' => biz('name'), 'url' => CANONICAL_URL . '/', 'logo' => CANONICAL_URL . '/assets/img/mark-512.png',
        'image' => CANONICAL_URL . '/assets/img/og-default.jpg',
        'description' => 'Paper supplier and printing company in Johannesburg: invoice books, NCR and carbonless paper, till rolls, business cards, flyers, banners and signage.',
        'email' => biz('email'), 'priceRange' => 'R–RR',
        'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => biz('street'), 'addressLocality' => biz('suburb') ?: biz('city'),
            'addressRegion' => 'Gauteng', 'postalCode' => biz('postcode'), 'addressCountry' => 'ZA']),
        'areaServed' => array_map(fn($a) => ['@type' => 'City', 'name' => $a], ['Johannesburg', 'Sandton', 'Randburg', 'Roodepoort', 'Soweto', 'Midrand', 'Germiston']),
        'sameAs' => array_values(array_filter([biz('facebook'), biz('instagram'), biz('tiktok'), biz('linkedin'), get_setting('google_profile_url')])),
    ];
    if (biz('phone')) $s['telephone'] = biz('phone');
    if (get_setting('biz_lat') && get_setting('biz_lng')) $s['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) get_setting('biz_lat'), 'longitude' => (float) get_setting('biz_lng')];
    $s['openingHoursSpecification'] = [
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:00', 'closes' => '17:00'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '08:00', 'closes' => '13:00'],
    ];
    return $s;
}

function schemaProduct(array $p, array $images): array
{
    $s = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'],
        'description' => excerpt($p['description'] ?: $p['tagline'], 300), 'sku' => 'XP-' . $p['id'],
        'brand' => ['@type' => 'Brand', 'name' => 'Extra Printers'], 'category' => $p['cat_name'],
        'url' => CANONICAL_URL . '/product/' . $p['slug']];
    if ($images) $s['image'] = array_map(fn($u) => preg_replace('#^' . preg_quote(APP_URL, '#') . '#', CANONICAL_URL, $u), $images);
    if ($p['price_from'] !== null) {
        $s['offers'] = ['@type' => 'Offer', 'priceCurrency' => 'ZAR', 'price' => number_format((float) $p['price_from'], 2, '.', ''),
            'availability' => 'https://schema.org/InStock', 'seller' => ['@id' => CANONICAL_URL . '/#business'], 'url' => $s['url']];
    }
    return $s;
}

/** @param array<int, array{0: string, 1: string}> $crumbs [name, url] */
function schemaBreadcrumb(array $crumbs): array
{
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
        fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], $crumbs, array_keys($crumbs))];
}

/** @param array<string, string> $qa */
function schemaFaq(array $qa): array
{
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
        fn($q, $a) => ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]], array_keys($qa), $qa)];
}

function breadcrumbsHtml(array $crumbs): string
{
    $out = '<nav class="crumbs" aria-label="Breadcrumb">';
    foreach ($crumbs as $i => [$name, $link]) {
        $out .= $i < count($crumbs) - 1 ? '<a href="' . e($link) . '">' . e($name) . '</a><i class="fa-solid fa-chevron-right"></i>' : '<span>' . e($name) . '</span>';
    }
    return $out . '</nav>';
}

/**
 * Local landing pages, served at /<slug> by landing.php. Each targets one
 * money keyword + Johannesburg and links to the products that sell it.
 */
function landings(): array
{
    return [
        'invoice-books-johannesburg' => [
            'title' => 'Invoice Books Printing Johannesburg | NCR Duplicate & Triplicate',
            'h1' => 'Invoice books printed in Johannesburg',
            'desc' => 'Custom invoice books in A6, A5, A4 and A3: duplicate, triplicate or quadruplicate NCR, numbered, with your logo. Get a quote from Extra Printers, Johannesburg.',
            'color' => 'magenta', 'keyword' => 'invoice books Johannesburg',
            'intro' => [
                'Need invoice books, quotation books, delivery note books or receipt books? Extra Printers prints custom NCR books in Johannesburg with your logo, business details and running numbers on every set.',
                'Choose duplicate (2-part), triplicate (3-part) or quadruplicate (4-part) carbonless paper in white, pink, yellow and blue, 50 or 100 sets per book, in A6, A5, A4 or A3. We print in black or full colour, perforate the top sheet for easy tearing, and bind with a sturdy cover and writing board.',
            ],
            'points' => ['Numbered sets so nothing goes missing', 'Duplicate, triplicate or quadruplicate NCR', 'A6, A5, A4 and A3 sizes', 'Your logo, VAT and bank details', 'No artwork? Our designer sets it up for R300', 'Collect in Johannesburg or delivery across Gauteng'],
            'products' => ['invoice-books', 'ncr-paper', 'carbonless-paper', 'letterheads', 'business-cards'],
            'faq' => [
                'How many sets are in an invoice book?' => 'Our invoice books come with 50 or 100 sets per book. A set is the original plus its copies, so a triplicate book of 50 sets has 150 sheets.',
                'What is the difference between duplicate and triplicate?' => 'Duplicate books give you the original plus one copy (2-part NCR). Triplicate gives the original plus two copies (3-part), for example one for the customer, one for accounts and one for the book.',
                'Can you number my invoice books?' => 'Yes. Every set is numbered in sequence, and we continue the numbering from your previous books if you tell us the last number used.',
                'Can you design my invoice book?' => 'Yes. Send us your logo and business details and our designer will set up the layout for R300 per artwork.',
                'Do you deliver invoice books in Johannesburg?' => 'You can collect from us in Johannesburg, or we deliver across Gauteng, including Sandton, Randburg, Roodepoort, Soweto, Midrand and Germiston.',
            ],
        ],
        'paper-supplier-johannesburg' => [
            'title' => 'Paper Supplier Johannesburg | Gloss, Matte, NCR & Sticker Paper',
            'h1' => 'Paper supplier in Johannesburg',
            'desc' => 'Buy gloss, matte, NCR, carbonless, sticker and business card paper in A1–A4, plus till rolls, from Extra Printers in Johannesburg. Bulk and reseller orders welcome.',
            'color' => 'cyan', 'keyword' => 'paper supplier Johannesburg',
            'intro' => [
                'Extra Printers specialises in paper. Offices, schools, print shops, designers and retailers across Johannesburg buy their paper from us in A1, A2, A3 and A4, from 80 gsm bond to 400 gsm board.',
                'We stock gloss and matte coated paper, NCR and carbonless paper for forms, self-carbonated paper, sticker and vinyl sheets, business card board and thermal till rolls for retail machines. Buy by the pack, the ream or the box, and ask about reseller pricing for bulk orders.',
            ],
            'points' => ['A1, A2, A3 and A4 sizes', '80 gsm to 400 gsm', 'Gloss, matte, NCR, carbonless, sticker and card', 'Thermal and bond till rolls', 'Packs, reams and boxes', 'Bulk and reseller orders'],
            'products' => ['gloss-paper', 'matte-paper', 'ncr-paper', 'carbonless-paper', 'self-carbonated-paper', 'till-rolls', 'sticker-paper', 'business-card-paper'],
            'faq' => [
                'What paper sizes do you sell?' => 'We sell A4, A3, A2 and A1, plus SRA3 for some papers. Ask us about other sizes and cutting to size.',
                'What does gsm mean?' => 'Grams per square metre: the weight and thickness of the paper. Normal office paper is 80 gsm, flyers are usually 128–170 gsm, and business cards are 300–400 gsm.',
                'What is the difference between NCR and carbonless paper?' => 'They are the same family. NCR (no carbon required) sets are pre-collated in 2, 3 or 4 parts, while carbonless sheets are sold as CB (top), CFB (middle) and CF (bottom) so you can make your own sets.',
                'Do you sell paper in bulk?' => 'Yes. We sell by the pack, the ream (500 sheets) and the box (2 500 sheets), and give reseller pricing on bulk orders.',
                'Do you deliver paper in Johannesburg?' => 'Yes, across Gauteng, or you can collect from us in Johannesburg.',
            ],
        ],
        'ncr-paper-johannesburg' => [
            'title' => 'NCR Paper Johannesburg | 2, 3 & 4 Part Carbonless Paper',
            'h1' => 'NCR & carbonless paper in Johannesburg',
            'desc' => 'NCR paper sets and carbonless CB, CFB and CF sheets in A5, A4 and A3, in white, pink, yellow and blue. Buy from Extra Printers, Johannesburg.',
            'color' => 'magenta', 'keyword' => 'NCR paper Johannesburg',
            'intro' => ['Print your own forms, dockets and delivery notes on NCR paper. We stock 2-part, 3-part and 4-part pre-collated sets and loose carbonless sheets (CB, CFB, CF) in four colours.', 'Laser and offset compatible. Buy by the ream or the box, collect in Johannesburg or get delivery in Gauteng.'],
            'points' => ['2, 3 and 4-part sets', 'CB, CFB and CF sheets', 'White, pink, yellow and blue', 'A5, A4, A3 and SRA3'],
            'products' => ['ncr-paper', 'carbonless-paper', 'self-carbonated-paper', 'invoice-books'],
            'faq' => ['Can I print NCR paper in my laser printer?' => 'Yes. Feed it in small stacks and fan the sheets first. For large runs, let us print and bind invoice books for you.'],
        ],
        'till-rolls-johannesburg' => [
            'title' => 'Till Rolls Johannesburg | Thermal & Card Machine Paper Rolls',
            'h1' => 'Till rolls and paper rolls in Johannesburg',
            'desc' => 'Thermal till rolls 80×80, 57×40 card machine rolls and bond rolls for cash registers. Buy by the box from Extra Printers, Johannesburg.',
            'color' => 'yellow', 'keyword' => 'till rolls Johannesburg',
            'intro' => ['Keep your tills and card machines running. We supply thermal rolls for POS printers and card machines, and bond rolls for older cash registers, in 10, 20, 50 and 100 roll quantities.'],
            'points' => ['80×80 and 80×70 thermal', '57×40 and 57×50 for card machines', '76×76 bond, 1 and 2-ply', 'Boxes of 50 and 100'],
            'products' => ['till-rolls'],
            'faq' => ['Which till roll fits my card machine?' => 'Most handheld card machines use 57 × 40 mm thermal rolls. Measure the width and diameter of your current roll, or send us a photo on WhatsApp.'],
        ],
        'business-cards-johannesburg' => [
            'title' => 'Business Cards Printing Johannesburg | From 100 Cards',
            'h1' => 'Business cards printed in Johannesburg',
            'desc' => 'Business cards on 350 gsm board with matte, gloss or soft-touch finishes, from 100 to 10 000+. Upload your design or let us design it for R300.',
            'color' => 'blue', 'keyword' => 'business cards Johannesburg',
            'intro' => ['Make a strong first impression with crisp, full-colour business cards printed in Johannesburg. Choose single or double sided, with matte, gloss, matte lamination or soft-touch with spot UV.'],
            'points' => ['100 to 10 000+ cards', '350 gsm board', 'Matte, gloss, lamination, spot UV', 'Design for R300'],
            'products' => ['business-cards', 'letterheads', 'business-card-paper'],
            'faq' => ['What size is a business card?' => 'The standard South African business card is 90 × 50 mm. Add 3 mm bleed on each side, so your artwork is 96 × 56 mm.'],
        ],
        'flyer-printing-johannesburg' => [
            'title' => 'Flyer Printing Johannesburg | A5 Flyers from 1 000',
            'h1' => 'Flyer printing in Johannesburg',
            'desc' => 'A5, A4, A6 and DL flyers in 1 000, 2 000, 5 000, 10 000 or custom runs on gloss or matte paper. Get a quote from Extra Printers.',
            'color' => 'magenta', 'keyword' => 'flyer printing Johannesburg',
            'intro' => ['Promote your business, church, event or special with full-colour flyers. A5 is the most popular size; choose 128 gsm or 150 gsm gloss, or 170 gsm matte, single or double sided.'],
            'points' => ['1 000 to 10 000+ flyers', 'A5, A4, A6 and DL', 'Gloss or matte', 'Single or double sided'],
            'products' => ['flyers', 'posters', 'calendars'],
            'faq' => ['What paper is best for flyers?' => '128 gsm gloss is the best value for handouts. 150 gsm or 170 gsm feels more premium and works well for menus and price lists.'],
        ],
        'pull-up-banners-johannesburg' => [
            'title' => 'Pull Up Banners Johannesburg | Banners, Gazebos & Flags',
            'h1' => 'Pull up banners and event branding in Johannesburg',
            'desc' => 'Pull up banners, X-frame and A-frame banners, gazebos, sharkfins, flags and lightboxes, printed in Johannesburg. Get a quote today.',
            'color' => 'yellow', 'keyword' => 'pull up banners Johannesburg',
            'intro' => ['Stand out at expos, launches and in-store with banners and outdoor branding. Every pull up banner comes with a stand and carry bag, ready to use in minutes.'],
            'points' => ['Pull up, X-frame and A-frame', 'Gazebos, sharkfins and flags', 'Lightboxes', 'Installation on request'],
            'products' => ['pull-up-banner', 'x-frame-banner', 'a-frame-banner', 'gazebo', 'sharkfin', 'flags', 'lightbox'],
            'faq' => ['What size is a pull up banner?' => 'Standard is 850 × 2000 mm; a broad base is 1000 × 2000 mm. Keep text 100 mm above the bottom edge, because it rolls into the base.'],
        ],
    ];
}
