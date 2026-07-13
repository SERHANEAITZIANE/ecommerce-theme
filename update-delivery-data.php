<?php
/**
 * Update delivery data from JSON files
 *
 * This script loads the delivery data from the JSON files and updates
 * the WordPress options used by the checkout system.
 */

define('ABSPATH') || exit;

// Load WordPress environment
if (!defined('WP_LOAD_IMPORTERS')) {
    require_once '../../../../wp-load.php';
}

// Load the data loading classes
require_once 'inc/load-zr-data.php';
require_once 'inc/update-delivery-pricing.php';

// Load wilayas, communes, and desks data
echo 'Loading wilayas, communes, and desks data...';
$loader = new Ayra_Load_ZR_Data();
$loader->load_all_data();
echo ' Done!\n';

// Update delivery pricing
echo 'Updating delivery pricing...';
$updater = new Ayra_Update_Delivery_Pricing();
$updater->update_pricing();
echo ' Done!\n';

echo '\nDelivery data updated successfully from JSON files.\n';
?>