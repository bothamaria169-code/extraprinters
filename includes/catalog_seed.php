<?php
declare(strict_types=1);

/**
 * The starting catalogue, from the client's brief. Seeded once by
 * database/migrate.php; after that everything is edited in Admin → Products.
 *
 * Option groups: ['key', 'Label', [values...]] — a 'qty' group may end with
 * custom => minimum, which adds a "Custom amount" box ("custom above 10 000").
 */

const QTY_RUN = [100, 300, 500, 1000, 2000, 5000, 10000];

function seedCatalogue(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn() > 0) return;

    $cats = [
        // section, slug, name, blurb, icon, colour
        ['paper', 'gloss-paper', 'Gloss Paper', 'Bright, shiny coated paper for vivid colour', 'fa-wand-magic-sparkles', 'cyan'],
        ['paper', 'ncr-paper', 'NCR Paper', 'No-carbon-required sets for invoices and forms', 'fa-copy', 'magenta'],
        ['paper', 'matte-paper', 'Matte Paper', 'Smooth, glare-free coated and bond paper', 'fa-file', 'yellow'],
        ['paper', 'carbonless-paper', 'Carbonless Paper', 'CB, CFB and CF sheets for multi-part forms', 'fa-layer-group', 'blue'],
        ['paper', 'self-carbonated-paper', 'Self Carbonated Paper', 'Self-contained copies, no backing sheet needed', 'fa-clone', 'cyan'],
        ['paper', 'paper-rolls', 'Paper Rolls', 'Thermal and bond till rolls for retail machines', 'fa-receipt', 'magenta'],
        ['paper', 'sticker-paper', 'Sticker Paper', 'Self-adhesive gloss, matte, vinyl and clear sheets', 'fa-note-sticky', 'yellow'],
        ['paper', 'business-card-paper', 'Business Card Paper', 'Heavy board from 300 to 400 gsm', 'fa-address-card', 'blue'],
        ['printing', 'marketing-print', 'Marketing Print', 'Posters, flyers and calendars that get noticed', 'fa-bullhorn', 'magenta'],
        ['printing', 'business-stationery', 'Business Stationery', 'Invoice books, cards, letterheads and more', 'fa-briefcase', 'blue'],
        ['printing', 'branded-merchandise', 'Clothing & Branded Gifts', 'T-shirts, embroidery, pens and diaries', 'fa-shirt', 'yellow'],
        ['signage', 'banners-displays', 'Banners & Displays', 'Pull up, X-frame and A-frame banners', 'fa-rectangle-ad', 'cyan'],
        ['signage', 'outdoor-branding', 'Outdoor Branding', 'Gazebos, sharkfins and flags for events', 'fa-flag', 'magenta'],
        ['signage', 'lightboxes', 'Lightboxes', 'Illuminated circle and box signs', 'fa-lightbulb', 'yellow'],
        ['services', 'services', 'Services', 'Design and signage installation', 'fa-pen-ruler', 'blue'],
    ];
    $ins = $db->prepare('INSERT INTO categories (section, slug, name, blurb, icon, color, sort) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $catId = [];
    foreach ($cats as $i => $c) {
        $ins->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $i + 1]);
        $catId[$c[1]] = (int) $db->lastInsertId();
    }

    $paperQty = ['qty', 'Quantity', ['100 sheets', '250 sheets', '500 sheets (1 ream)', '1 000 sheets', '2 500 sheets (1 box)'], 'custom' => 2500];
    $sizesA = ['size', 'Size', ['A4', 'A3', 'A2', 'A1']];
    $sides  = ['sides', 'Printed sides', ['Single sided', 'Double sided']];

    // slug, category, name, tagline, options[], extras
    $p = [
        // ── Paper ─────────────────────────────────────────────
        ['gloss-paper', 'gloss-paper', 'Gloss Paper', 'High-gloss coated paper in A1, A2, A3 and A4, 115–350 gsm', [$sizesA, ['weight', 'Weight', ['115 gsm', '130 gsm', '150 gsm', '170 gsm', '200 gsm', '250 gsm', '300 gsm', '350 gsm']], $paperQty], ['featured' => 1, 'art' => 0]],
        ['ncr-paper', 'ncr-paper', 'NCR Paper', 'No-carbon-required paper sets in white, pink, yellow and blue', [['size', 'Size', ['A4', 'A3', 'A5']], ['parts', 'Parts', ['2-part (white/pink)', '3-part (white/pink/yellow)', '4-part (white/pink/yellow/blue)']], ['qty', 'Quantity', ['250 sets', '500 sets', '1 000 sets', '2 500 sets'], 'custom' => 2500]], ['featured' => 1, 'art' => 0]],
        ['matte-paper', 'matte-paper', 'Matte Paper', 'Matte coated and bond paper in A1–A4, 80–350 gsm', [$sizesA, ['weight', 'Weight', ['80 gsm bond', '100 gsm bond', '115 gsm', '150 gsm', '170 gsm', '250 gsm', '300 gsm', '350 gsm']], $paperQty], ['featured' => 1, 'art' => 0]],
        ['carbonless-paper', 'carbonless-paper', 'Carbonless Paper', 'CB (top), CFB (middle) and CF (bottom) sheets in four colours', [['size', 'Size', ['A4', 'A3', 'SRA3']], ['sheet', 'Sheet type', ['CB – top sheet', 'CFB – middle sheet', 'CF – bottom sheet']], ['colour', 'Colour', ['White', 'Pink', 'Yellow', 'Blue']], ['qty', 'Quantity', ['500 sheets (1 ream)', '2 500 sheets (1 box)'], 'custom' => 2500]], ['art' => 0]],
        ['self-carbonated-paper', 'self-carbonated-paper', 'Self Carbonated Paper', 'Self-contained (SC) paper that copies on its own surface', [['size', 'Size', ['A4', 'A3']], ['colour', 'Colour', ['White', 'Pink', 'Yellow', 'Blue']], ['qty', 'Quantity', ['500 sheets (1 ream)', '2 500 sheets (1 box)'], 'custom' => 2500]], ['art' => 0]],
        ['till-rolls', 'paper-rolls', 'Paper Rolls for Till Machines', 'Thermal and bond rolls for POS, card machines and cash registers', [['roll', 'Roll size', ['80 × 80 mm thermal', '80 × 70 mm thermal', '57 × 40 mm thermal (card machine)', '57 × 50 mm thermal', '76 × 76 mm bond (1-ply)', '76 × 76 mm bond (2-ply)']], ['qty', 'Quantity', ['10 rolls', '20 rolls', '50 rolls (1 box)', '100 rolls'], 'custom' => 100]], ['featured' => 1, 'art' => 0]],
        ['sticker-paper', 'sticker-paper', 'Sticker Paper', 'Printable self-adhesive sheets for labels and stickers', [['size', 'Size', ['A4', 'A3', 'SRA3']], ['finish', 'Finish', ['Gloss paper', 'Matte paper', 'White vinyl', 'Clear vinyl']], ['qty', 'Quantity', ['50 sheets', '100 sheets', '250 sheets', '500 sheets'], 'custom' => 500]], ['art' => 0]],
        ['business-card-paper', 'business-card-paper', 'Business Card Paper', 'Premium board for printing your own business cards', [['size', 'Size', ['A4', 'A3', 'SRA3']], ['weight', 'Weight', ['300 gsm', '350 gsm', '400 gsm']], ['finish', 'Finish', ['Matte', 'Gloss', 'Linen texture', 'Pearlescent']], ['qty', 'Quantity', ['100 sheets', '250 sheets', '500 sheets'], 'custom' => 500]], ['art' => 0]],

        // ── Printing ──────────────────────────────────────────
        ['posters', 'marketing-print', 'Posters', 'Full-colour posters in A3, A2 and A1', [['size', 'Size', ['A3', 'A2', 'A1']], ['paper', 'Paper', ['150 gsm gloss', '170 gsm matte', '200 gsm satin', '250 gsm gloss']], ['qty', 'Quantity', ['1', '5', '10', '25', '50', '100', '250'], 'custom' => 250]], []],
        ['flyers', 'marketing-print', 'Flyers', 'A5 flyers from 1 000 to 10 000+, single or double sided', [['size', 'Size', ['A5', 'A4', 'A6', 'DL']], ['paper', 'Paper', ['128 gsm gloss', '150 gsm gloss', '170 gsm matte']], $sides, ['qty', 'Quantity', ['1 000', '2 000', '5 000', '10 000'], 'custom' => 10000]], ['featured' => 1]],
        ['calendars', 'marketing-print', 'Calendars', 'Branded desk and wall calendars in A5, A4 and A3', [['size', 'Size', ['A5', 'A4', 'A3']], ['type', 'Type', ['Desk calendar (tent)', 'Wall calendar (wire-bound)', 'Single-page wall planner']], ['qty', 'Quantity', ['25', '50', '100', '250', '500', '1 000'], 'custom' => 1000]], ['featured' => 1]],
        ['invoice-books', 'business-stationery', 'Invoice Books', 'Numbered NCR invoice books in A6, A5, A4 and A3', [['size', 'Size', ['A6', 'A5', 'A4', 'A3']], ['parts', 'Copies', ['Duplicate (2-part)', 'Triplicate (3-part)', 'Quadruplicate (4-part)']], ['sets', 'Sets per book', ['50 sets', '100 sets']], ['print', 'Print', ['Black only', 'Full colour logo']], ['qty', 'Number of books', ['5', '10', '20', '50', '100', '250'], 'custom' => 250]], ['featured' => 1, 'seo_title' => 'Invoice Books Printing Johannesburg | NCR Duplicate & Triplicate Books']],
        ['business-cards', 'business-stationery', 'Business Cards', 'Crisp cards on 350 gsm board, from 100 to 10 000+', [$sides, ['finish', 'Finish', ['Matte', 'Gloss', 'Matte lamination', 'Soft-touch + spot UV']], ['qty', 'Quantity', QTY_RUN, 'custom' => 10000]], ['featured' => 1]],
        ['letterheads', 'business-stationery', 'Letterheads', 'A4 letterheads on 80–120 gsm bond, from 100 to 10 000+', [['paper', 'Paper', ['80 gsm bond', '100 gsm bond', '120 gsm premium']], ['print', 'Print', ['Full colour', '1 colour']], ['qty', 'Quantity', QTY_RUN, 'custom' => 10000]], []],
        ['dl-envelopes', 'business-stationery', 'DL Envelopes', 'Branded DL envelopes, with or without a window', [['window', 'Window', ['No window', 'Window (left)']], ['print', 'Print', ['1 colour logo', 'Full colour']], ['qty', 'Quantity', ['250', '500', '1 000', '2 500', '5 000'], 'custom' => 5000]], []],
        ['certificates', 'business-stationery', 'Certificates', 'Award and course certificates on premium board', [['size', 'Size', ['A4', 'A3']], ['paper', 'Paper', ['250 gsm matte', '300 gsm parchment', '300 gsm with gold foil']], ['qty', 'Quantity', ['10', '25', '50', '100', '250'], 'custom' => 250]], []],
        ['company-profiles', 'business-stationery', 'Company Profiles', 'Bound company profiles and brochures', [['size', 'Size', ['A4', 'A5']], ['pages', 'Pages', ['8 pages', '12 pages', '16 pages', '24 pages', '32 pages']], ['binding', 'Binding', ['Saddle stitch (stapled)', 'Perfect bound', 'Wire bound']], ['qty', 'Quantity', ['10', '25', '50', '100', '250'], 'custom' => 250]], []],
        ['printed-t-shirts', 'branded-merchandise', 'Printed T-shirts', 'Branded T-shirts for teams, events and promotions', [['shirt', 'Shirt', ['Cotton round neck', 'Golf shirt', 'Dri-fit sports']], ['colour', 'Shirt colour', ['White', 'Black', 'Navy', 'Other colour']], ['print', 'Print area', ['Front only', 'Front + back', 'Left chest + back']], ['qty', 'Quantity', ['10', '25', '50', '100', '250'], 'custom' => 250]], []],
        ['embroidery', 'branded-merchandise', 'Embroidery', 'Embroidered logos on shirts, caps, jackets and workwear', [['item', 'Item', ['Golf shirt', 'Cap', 'Jacket / fleece', 'Workwear (conti suit)', 'Own garments']], ['position', 'Position', ['Left chest', 'Back', 'Left chest + back', 'Sleeve']], ['qty', 'Quantity', ['10', '25', '50', '100', '250'], 'custom' => 250]], []],
        ['pen-printing', 'branded-merchandise', 'Pen Printing', 'Branded pens from 100 to 10 000+', [['pen', 'Pen', ['Plastic click pen', 'Metal pen', 'Stylus pen']], ['print', 'Print', ['1 colour logo', 'Full colour']], ['qty', 'Quantity', QTY_RUN, 'custom' => 10000]], []],
        ['diaries', 'branded-merchandise', 'Diaries', 'Branded A5 and A4 diaries, from 100 to 10 000+', [['size', 'Size', ['A5', 'A4']], ['layout', 'Layout', ['Day per page', 'Week to view']], ['branding', 'Branding', ['Debossed logo', 'Printed logo', 'Full custom cover']], ['qty', 'Quantity', QTY_RUN, 'custom' => 10000]], []],

        // ── Signage ───────────────────────────────────────────
        ['pull-up-banner', 'banners-displays', 'Pull Up Banner', 'Roll-up banner with stand and carry bag', [['size', 'Size', ['850 × 2000 mm standard', '1000 × 2000 mm broad base', '850 × 2000 mm premium']], ['qty', 'Quantity', ['1', '2', '5', '10'], 'custom' => 10]], ['featured' => 1]],
        ['x-frame-banner', 'banners-displays', 'X-Frame Banner', 'Light, budget-friendly banner stand', [['size', 'Size', ['600 × 1600 mm', '800 × 1800 mm']], ['qty', 'Quantity', ['1', '2', '5', '10'], 'custom' => 10]], []],
        ['a-frame-banner', 'banners-displays', 'A-Frame Banner', 'Pavement sign for shop fronts', [['size', 'Size', ['A1', 'A0']], $sides, ['qty', 'Quantity', ['1', '2', '5', '10'], 'custom' => 10]], []],
        ['lightbox', 'lightboxes', 'Lightbox (Circle or Box)', 'LED lightbox signs, round or rectangular', [['shape', 'Shape', ['Circle', 'Box']], ['size', 'Size', ['600 mm', '900 mm', '1200 mm', 'Custom size']], $sides, ['qty', 'Quantity', ['1', '2', '5'], 'custom' => 5]], []],
        ['gazebo', 'outdoor-branding', 'Gazebo', 'Branded gazebo with frame and carry bag', [['size', 'Size', ['3 × 3 m', '3 × 4.5 m', '3 × 6 m']], ['print', 'Branding', ['Roof only', 'Roof + back wall', 'Full (roof + 3 walls)']], ['qty', 'Quantity', ['1', '2', '5'], 'custom' => 5]], ['featured' => 1]],
        ['sharkfin', 'outdoor-branding', 'Sharkfin Banner', 'Sharkfin flag with pole and base', [['size', 'Size', ['Small 2.2 m', 'Medium 3 m', 'Large 4.5 m']], ['base', 'Base', ['Ground spike', 'Cross base + water bag']], ['qty', 'Quantity', ['1', '2', '5', '10'], 'custom' => 10]], []],
        ['flags', 'outdoor-branding', 'Flags', 'Telescopic, teardrop and feather flags', [['type', 'Type', ['Teardrop', 'Feather', 'Telescopic', 'Pole / desk flag']], ['size', 'Size', ['Small', 'Medium', 'Large']], ['qty', 'Quantity', ['1', '2', '5', '10', '25'], 'custom' => 25]], []],

        // ── Services ──────────────────────────────────────────
        ['graphic-design', 'services', 'Graphic Design', 'Professional print-ready artwork, R300 per artwork', [['type', 'What we design', ['Logo', 'Flyer / poster', 'Business card', 'Invoice book layout', 'Banner / signage', 'Company profile page', 'Other']], ['qty', 'Number of artworks', ['1', '2', '3', '5', '10']]], ['price' => 300, 'fixed' => 1, 'note' => 'per artwork', 'art' => 0, 'featured' => 1]],
        ['signage-installation', 'services', 'Custom Signage Installation', 'We measure, make and install signage at your premises', [['type', 'Signage type', ['Shop front / fascia', 'Lightbox', 'Wall vinyl / window graphics', 'Vehicle branding', 'Other']]], ['art' => 0]],
    ];

    $ins = $db->prepare('INSERT INTO products (category_id, slug, name, tagline, description, options, price_from, price_note, fixed_price, needs_artwork, featured, sort, seo_title)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($p as $i => [$slug, $cat, $name, $tagline, $opts, $x]) {
        $groups = [];
        foreach ($opts as $o) {
            $g = ['key' => $o[0], 'label' => $o[1], 'values' => array_map('strval', $o[2])];
            if (isset($o['custom'])) $g['custom_above'] = (int) $o['custom'];
            $groups[] = $g;
        }
        $ins->execute([
            $catId[$cat], $slug, $name, $tagline, defaultDescription($name, $tagline, $cat),
            json_encode($groups, JSON_UNESCAPED_UNICODE), $x['price'] ?? null, $x['note'] ?? '',
            $x['fixed'] ?? 0, $x['art'] ?? 1, $x['featured'] ?? 0, $i + 1, $x['seo_title'] ?? '',
        ]);
    }
}

/** A sensible starting description; "Write with AI" in admin replaces it. */
function defaultDescription(string $name, string $tagline, string $cat): string
{
    $isPaper = in_array($cat, ['gloss-paper', 'ncr-paper', 'matte-paper', 'carbonless-paper', 'self-carbonated-paper', 'paper-rolls', 'sticker-paper', 'business-card-paper'], true);
    if ($isPaper) {
        return "{$tagline}. Extra Printers stocks {$name} in Johannesburg for offices, schools, print shops and retailers. "
             . "Choose your size, weight and quantity, then add it to your quote. Bulk and reseller orders are welcome, "
             . "and you can collect from us or get it delivered across Gauteng.";
    }
    return "{$tagline}. Choose your options below, upload your print-ready artwork (or let our designers create it for R300), "
         . "and we'll send you a quote fast. Printed in Johannesburg with collection or delivery across Gauteng.";
}
