<?php
define("WP_USE_THEMES", false);
require_once "../../../wp-load.php";

$tenant = get_option('ayra_zr_tenant_id');
$key = get_option('ayra_zr_api_key');
$api = new Ayra_ZR_Express_API($tenant, $key);

echo "Fetching wilayas...\n";
$wilayas = $api->request('POST', '/territories/search', [
    'advancedFilter' => ['logic' => 'and', 'filters' => [['field' => 'level', 'operator' => 'eq', 'value' => 'Wilaya']]],
    'pageNumber' => 1, 'pageSize' => 500
]);

echo "Fetching communes...\n";
$communes = $api->request('POST', '/territories/search', [
    'advancedFilter' => ['logic' => 'and', 'filters' => [['field' => 'level', 'operator' => 'eq', 'value' => 'Commune']]],
    'pageNumber' => 1, 'pageSize' => 2000
]);

$processed = [];
foreach ($wilayas['items'] as $w) {
    $code = str_pad($w['code'], 2, '0', STR_PAD_LEFT);
    $w_name = ($w['nameArabic'] ? trim($w['nameArabic']) . ' - ' : '') . trim($w['name']);
    $processed[$w['id']] = [
        'id' => $w['id'],
        'code' => $code,
        'name' => $w_name,
        'communes' => [],
        'communes_map' => []
    ];
}

foreach ($communes['items'] as $c) {
    $pid = $c['parentId'];
    if (isset($processed[$pid])) {
        $c_name = trim($c['nameArabic'] ?: $c['name']);
        if ($c_name) {
            $processed[$pid]['communes'][] = $c_name;
            $processed[$pid]['communes_map'][$c_name] = $c['id'];
        }
    }
}

$static_data = function_exists('ayra_get_wilayas_data_static') ? ayra_get_wilayas_data_static() : [];
$final_data = [];
foreach ($processed as $w) {
    $code = $w['code'];
    $communes_unique = array_unique($w['communes']);
    sort($communes_unique);
    $final_data[$code] = [
        'id' => $w['id'],
        'name' => $w['name'],
        'communes' => array_values($communes_unique),
        'communes_map' => $w['communes_map'],
        'zone' => isset($static_data[$code]) ? $static_data[$code]['zone'] : 'north'
    ];
}

update_option('ayra_zr_api_territories', $final_data, false);
echo "Territories synced successfully with IDs! Total Wilayas: " . count($final_data) . "\n";
