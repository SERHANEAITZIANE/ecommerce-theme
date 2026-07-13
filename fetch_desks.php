<?php
require_once "wp-load.php";
$api = new Ayra_ZR_Express_API();
$res = $api->request("POST", "/hubs/search", ["pageNumber" => 1, "pageSize" => 500]);
if (is_wp_error($res)) { echo "Error: " . $res->get_error_message(); }
else { 
    $items = $res["items"] ?? $res["data"] ?? [];
    $desks = [];
    foreach($items as $item) {
        if (!empty($item["isPickupPoint"])) {
            $desks[] = trim($item["address"]["district"] ?? "");
        }
    }
    $desks = array_filter(array_unique($desks));
    echo "return " . var_export($desks, true) . ";";
}
