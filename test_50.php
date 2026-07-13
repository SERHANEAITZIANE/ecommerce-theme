<?php
require_once "wp-load.php";
$data = get_option('ayra_zr_api_territories');
$wilaya_50 = $data['50'] ?? null;
print_r($wilaya_50);
