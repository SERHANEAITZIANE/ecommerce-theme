<?php
define("WP_USE_THEMES", false);
require_once "wp-load.php";
$data = get_option("ayra_zr_api_territories", []);
echo "Total Wilayas saved: " . count($data) . "\n";
if (isset($data["50"])) {
    echo "Wilaya 50: " . $data["50"]["name"] . "\n";
} else {
    echo "Wilaya 50 NOT FOUND in ayra_zr_api_territories option.\n";
}
