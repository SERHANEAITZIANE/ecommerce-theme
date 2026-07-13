<?php
define("WP_USE_THEMES", false);
require_once "wp-load.php";
$data = get_option("ayra_zr_api_territories");
$w50 = $data["50"] ?? null;
echo json_encode($w50, JSON_UNESCAPED_UNICODE);
