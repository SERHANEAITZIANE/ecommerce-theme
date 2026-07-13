<?php
define('WP_USE_THEMES', false);
require_once 'wp-load.php';

// Mock API responses
\ = [];
\ = new Ayra_ZR_Express_API();
\ = \->request('POST', '/hubs/search', [
    'pageNumber' => 1, 'pageSize' => 500
]);
\ = \['items'] ?? \['data'] ?? [];

foreach (\ as \) {
    if (!empty(\['isPickupPoint'])) {
        \ = \['address']['districtTerritoryId'] ?? null;
        if (\) {
            \[\] = \['id'];
        }
    }
}
echo "Hubs fetched: " . count(\) . "\n";
echo "Hubs mapped: " . count(\) . "\n";
