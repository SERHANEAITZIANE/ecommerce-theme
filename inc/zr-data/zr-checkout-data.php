<?php
/**
 * AYRA Homewear — ZR Express Checkout Data Provider
 *
 * Reads the locally-cached ZR Express JSON files and builds the
 * JavaScript delivery data object used on the checkout page.
 *
 * JSON files live in: inc/zr-data/
 *   zr_wilayas.json        – 54 wilayas with pricing
 *   zr_communes.json       – 1531 communes with wilaya refs
 *   zr_pickup_desks.json   – 76 pickup desks
 *   zr_pricing.json        – wilaya-level pricing
 */
defined('ABSPATH') || exit;

$ZR_DATA_DIR = __DIR__;

// ──────────────────────────────────────────────────────────────
// 1. LOAD JSON FILES (cached in memory for this request)
// ──────────────────────────────────────────────────────────────

function ayra_zr_load_json(string $file): array {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    if (!$raw) return [];
    // Remove UTF-8 BOM if present
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// Wilayas: [{ id, code, name, price_home, price_stopdesk, price_return, ... }]
function ayra_zr_get_wilayas(): array {
    static $cache = null;
    if ($cache === null) $cache = ayra_zr_load_json('zr_wilayas.json');
    return $cache;
}

// Communes: [{ id, code, name, wilaya_code, wilaya_name, has_home_delivery, has_pickup_point }]
function ayra_zr_get_communes(): array {
    static $cache = null;
    if ($cache === null) $cache = ayra_zr_load_json('zr_communes.json');
    return $cache;
}

// Pickup desks: [{ id, name, city, city_territory_id, district, district_territory_id, ... }]
function ayra_zr_get_desks(): array {
    static $cache = null;
    if ($cache === null) $cache = ayra_zr_load_json('zr_pickup_desks.json');
    return $cache;
}

// ──────────────────────────────────────────────────────────────
// 2. BUILD CHECKOUT JS PAYLOAD
// ──────────────────────────────────────────────────────────────

/**
 * Build the compact data object passed to the checkout page JS.
 *
 * Structure returned:
 * {
 *   wilayas: [{ code, name, price_home, price_desk }],   ← sorted by code
 *   desks:   [{ id, name, city_code, commune, phone, street }],  ← all pickup desks
 *   desks_by_wilaya_code: { "16": [desk, ...], ... }    ← grouped by wilaya code
 * }
 *
 * NOTE: Communes are NOT included here — they're fetched via AJAX
 * (ayra_zr_get_communes_ajax) to keep the page payload small.
 */
function ayra_zr_build_checkout_payload(): array {
    // — Wilayas —
    $raw_wilayas = ayra_zr_get_wilayas();
    $wilayas = [];
    foreach ($raw_wilayas as $w) {
        if (empty($w['code'])) continue;
        $wilayas[] = [
            'code'       => (int) $w['code'],
            'name'       => $w['name'] ?? '',
            'price_home' => (float) ($w['price_home'] ?? 0),
            'price_desk' => (float) ($w['price_stopdesk'] ?? 0),
        ];
    }
    usort($wilayas, fn($a, $b) => $a['code'] <=> $b['code']);

    // — Pickup desks —
    $raw_desks = ayra_zr_get_desks();

    // We need to map each desk's city (wilaya name) to a wilaya code.
    // Build a name→code lookup from wilayas.
    $wilaya_name_to_code = [];
    foreach ($raw_wilayas as $w) {
        if (!empty($w['name']) && !empty($w['code'])) {
            $wilaya_name_to_code[mb_strtolower(trim($w['name']))] = (int) $w['code'];
        }
    }

    $desks            = [];
    $desks_by_wilaya  = [];  // wilaya_code => [desk, ...]

    foreach ($raw_desks as $d) {
        if (empty($d['is_pickup_point'])) continue;

        $city_lc    = mb_strtolower(trim($d['city'] ?? ''));
        $wcode      = $wilaya_name_to_code[$city_lc] ?? 0;

        $desk = [
            'id'           => $d['id'] ?? '',
            'name'         => $d['name'] ?? '',
            'wilaya_code'  => $wcode,
            'wilaya'       => $d['city'] ?? '',
            'commune'      => $d['district'] ?? '',
            'commune_id'   => $d['district_territory_id'] ?? '',
            'street'       => $d['street'] ?? '',
            'phone'        => $d['phone1'] ?? '',
            'hours'        => $d['opening_hours'] ?? '',
            'lat'          => (float) ($d['lat'] ?? 0),
            'lng'          => (float) ($d['lng'] ?? 0),
        ];

        $desks[] = $desk;

        if ($wcode > 0) {
            $desks_by_wilaya[$wcode][] = $desk;
        }
    }

    return [
        'wilayas'          => $wilayas,
        'desks'            => $desks,
        'desks_by_wilaya'  => $desks_by_wilaya,
    ];
}

// ──────────────────────────────────────────────────────────────
// 3. AJAX: Get communes for a given wilaya_code
// ──────────────────────────────────────────────────────────────

add_action('wp_ajax_ayra_zr_get_communes',        'ayra_zr_ajax_get_communes');
add_action('wp_ajax_nopriv_ayra_zr_get_communes', 'ayra_zr_ajax_get_communes');

function ayra_zr_ajax_get_communes() {
    $wilaya_code = isset($_POST['wilaya_code']) ? intval($_POST['wilaya_code']) : 0;
    $mode        = isset($_POST['mode'])        ? sanitize_text_field($_POST['mode']) : 'home';

    if (!$wilaya_code) {
        wp_send_json_error('missing wilaya_code');
    }

    $all_communes = ayra_zr_get_communes();
    $result = [];

    foreach ($all_communes as $c) {
        if ((int)($c['wilaya_code'] ?? 0) !== $wilaya_code) continue;

        if ($mode === 'desk' && empty($c['has_pickup_point'])) continue;
        if ($mode === 'home' && empty($c['has_home_delivery'])) continue;

        $result[] = [
            'id'   => $c['id']   ?? '',
            'code' => $c['code'] ?? '',
            'name' => $c['name'] ?? '',
        ];
    }

    // Sort by name
    usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

    wp_send_json_success($result);
}

// ──────────────────────────────────────────────────────────────
// 4. DELIVERY FEE using ZR data (replaces old delivery-pricing.php)
// ──────────────────────────────────────────────────────────────

/**
 * Get delivery price from ZR data for a wilaya code + type.
 * Falls back to legacy ayra_get_delivery_price() if needed.
 */
function ayra_zr_get_price(int $wilaya_code, string $type): float {
    $wilayas = ayra_zr_get_wilayas();
    foreach ($wilayas as $w) {
        if ((int)($w['code'] ?? 0) === $wilaya_code) {
            if ($type === 'home') return (float)($w['price_home']     ?? 0);
            if ($type === 'desk') return (float)($w['price_stopdesk'] ?? 0);
        }
    }
    return 0.0;
}
