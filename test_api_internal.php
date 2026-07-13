<?php
define("WP_USE_THEMES", false);
require_once "../../../wp-load.php";

$tenant = get_option('ayra_zr_tenant_id');
$key = get_option('ayra_zr_api_key');
$api = new Ayra_ZR_Express_API($tenant, $key);

echo "Tenant: $tenant\n";
echo "Key: " . substr($key, 0, 8) . "...\n";

$territory = $api->resolve_territories('16', 'Alger Centre');
print_r($territory);

$customer_id = $api->find_or_create_customer('Test AYRA', '+213555000000', '', 'Test Address');
if (is_wp_error($customer_id)) {
    echo "Customer Error: " . $customer_id->get_error_message() . "\n";
} else {
    echo "Customer ID: " . $customer_id . "\n";
}
