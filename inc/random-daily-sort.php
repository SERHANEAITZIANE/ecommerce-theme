<?php
/**
 * Daily Random Product Sorting via WP Cron
 */

defined('ABSPATH') || exit;

// 1. Schedule the daily cron event at 03:00 AM
function ayra_schedule_daily_random_sort() {
    if (!wp_next_scheduled('ayra_daily_random_sort_event')) {
        // Find the next 03:00 AM based on the site's timezone
        $timezone = wp_timezone();
        $next_3am = new DateTime('03:00:00', $timezone);
        
        // If 3 AM has already passed today, schedule for tomorrow
        if ($next_3am->getTimestamp() <= time()) {
            $next_3am->modify('+1 day');
        }
        
        wp_schedule_event($next_3am->getTimestamp(), 'daily', 'ayra_daily_random_sort_event');
    }
}
// Hook into admin_init so it runs when admin visits, or simply 'init'. 'init' is safer.
add_action('init', 'ayra_schedule_daily_random_sort');

// 2. The function that actually shuffles the menu_order
function ayra_shuffle_products_menu_order() {
    global $wpdb;

    // Get all published product IDs
    $product_ids = $wpdb->get_col("
        SELECT ID 
        FROM {$wpdb->posts} 
        WHERE post_type = 'product' 
        AND post_status = 'publish'
    ");

    if (empty($product_ids)) {
        return;
    }

    // Shuffle the array of product IDs randomly
    shuffle($product_ids);

    // Update menu_order for each product
    $menu_order = 1;
    foreach ($product_ids as $product_id) {
        $wpdb->update(
            $wpdb->posts,
            array('menu_order' => $menu_order),
            array('ID' => $product_id),
            array('%d'),
            array('%d')
        );
        $menu_order++;
    }

    // Clear WooCommerce transients for shop page caching
    wc_delete_product_transients();
    if (class_exists('WC_Cache_Helper')) {
        WC_Cache_Helper::get_transient_version('catalog', true);
    }
}
add_action('ayra_daily_random_sort_event', 'ayra_shuffle_products_menu_order');

// 3. Force default catalog sorting to 'menu_order'
function ayra_set_default_catalog_orderby() {
    return 'menu_order';
}
add_filter('woocommerce_default_catalog_orderby', 'ayra_set_default_catalog_orderby');
