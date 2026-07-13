<?php
define("WP_USE_THEMES", false);
require_once "wp-load.php";

$orders = wc_get_orders(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC']);
if (empty($orders)) {
    echo "No orders found.\n";
    exit;
}

$order = $orders[0];
echo "Testing Order ID: " . $order->get_id() . "\n";
echo "Delivery Type: " . get_post_meta($order->get_id(), '_billing_delivery_type', true) . "\n";
echo "Wilaya: " . $order->get_billing_state() . "\n";
echo "Commune: " . $order->get_billing_city() . "\n";

$api = new Ayra_ZR_Express_API();
$res = $api->create_parcel_from_order($order->get_id());

if (is_wp_error($res)) {
    echo "Error: " . $res->get_error_message() . "\n";
} else {
    echo "Result: "; print_r($res);
}
