<?php
/**
 * AYRA Homewear - AJAX Handlers
 */
defined('ABSPATH') || exit;

// ─── AJAX: Add to cart (variable product) ───────────────
function ayra_ajax_add_to_cart()
{
    check_ajax_referer('ayra_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);
    $variation_id = intval($_POST['variation_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    $size = sanitize_text_field($_POST['size'] ?? '');

    if (!$product_id) {
        wp_send_json_error(['message' => 'حدث خطأ، حاولي مرة أخرى']);
    }

    if (!$variation_id) {
        // Signal the front-end to scroll up to the size filter instead of
        // showing a red error toast.
        wp_send_json_error([
            'message'   => 'يرجى اختيار المقاس أولاً',
            'need_size' => true,
        ]);
    }

    // Build variation attributes
    $variation = wc_get_product($variation_id);
    if (!$variation) {
        wp_send_json_error(['message' => 'المنتج غير موجود']);
    }

    $variation_attrs = $variation->get_attributes();

    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_attrs);

    if ($cart_item_key) {
        // Get updated cart fragments
        ob_start();
        ayra_render_mini_cart();
        $mini_cart_html = ob_get_clean();

        wp_send_json_success([
            'message' => 'تمت الإضافة للسلة ✓',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'mini_cart' => $mini_cart_html,
        ]);
    } else {
        wp_send_json_error(['message' => 'خطأ في الإضافة للسلة']);
    }
}
add_action('wp_ajax_ayra_add_to_cart', 'ayra_ajax_add_to_cart');
add_action('wp_ajax_nopriv_ayra_add_to_cart', 'ayra_ajax_add_to_cart');

// ─── AJAX: Update cart item quantity ────────────────────
function ayra_ajax_update_cart_qty()
{
    check_ajax_referer('ayra_nonce', 'nonce');

    $cart_key = sanitize_text_field($_POST['cart_key']);
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {
        WC()->cart->remove_cart_item($cart_key);
    } else {
        WC()->cart->set_quantity($cart_key, $quantity);
    }

    // Get updated cart
    ob_start();
    ayra_render_mini_cart();
    $mini_cart_html = ob_get_clean();

    wp_send_json_success([
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total(),
        'mini_cart' => $mini_cart_html,
    ]);
}
add_action('wp_ajax_ayra_update_cart_qty', 'ayra_ajax_update_cart_qty');
add_action('wp_ajax_nopriv_ayra_update_cart_qty', 'ayra_ajax_update_cart_qty');

// ─── AJAX: Remove cart item ─────────────────────────────
function ayra_ajax_remove_cart_item()
{
    check_ajax_referer('ayra_nonce', 'nonce');

    $cart_key = sanitize_text_field($_POST['cart_key']);
    WC()->cart->remove_cart_item($cart_key);

    ob_start();
    ayra_render_mini_cart();
    $mini_cart_html = ob_get_clean();

    wp_send_json_success([
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total(),
        'mini_cart' => $mini_cart_html,
    ]);
}
add_action('wp_ajax_ayra_remove_cart_item', 'ayra_ajax_remove_cart_item');
add_action('wp_ajax_nopriv_ayra_remove_cart_item', 'ayra_ajax_remove_cart_item');


// ─── AJAX: Search products ──────────────────────────────
function ayra_ajax_search_products()
{
    $query = sanitize_text_field($_POST['query'] ?? '');
    if (strlen($query) < 2) {
        wp_send_json_success(['products' => []]);
    }

    $results = [];
    $found_ids = [];

    // 1. If numeric, check if it's a direct product ID
    if (is_numeric($query)) {
        $prod_id = intval($query);
        $product = wc_get_product($prod_id);
        if ($product && $product->get_status() === 'publish') {
            $thumb = get_the_post_thumbnail_url($product->get_id(), 'thumbnail');
            $results[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'permalink' => get_permalink($product->get_id()),
                'image' => $thumb ?: '',
            ];
            $found_ids[] = $product->get_id();
        }
    }

    // 2. Search by SKU
    $sku_products = wc_get_products([
        'status' => 'publish',
        'limit' => 8,
        'sku' => $query,
    ]);
    if (!empty($sku_products)) {
        foreach ($sku_products as $product) {
            $pid = $product->get_id();
            if (in_array($pid, $found_ids)) continue;
            
            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
            $results[] = [
                'id' => $pid,
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'permalink' => get_permalink($pid),
                'image' => $thumb ?: '',
            ];
            $found_ids[] = $pid;
        }
    }

    // 3. Search by title / description / meta using WP_Query
    $search_query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        's'              => $query,
    ]);

    if ($search_query->have_posts()) {
        foreach ($search_query->posts as $post) {
            $pid = $post->ID;
            if (in_array($pid, $found_ids)) continue;
            
            $product = wc_get_product($pid);
            if ($product) {
                $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
                $results[] = [
                    'id' => $pid,
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'permalink' => get_permalink($pid),
                    'image' => $thumb ?: '',
                ];
                $found_ids[] = $pid;
            }
        }
    }
    wp_reset_postdata();

    // 4. Fallback search: if we still have nothing and query is numeric, search for titles containing the query
    if (empty($results) && is_numeric($query)) {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($query) . '%';
        $pids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' AND post_title LIKE %s LIMIT 8",
            $like
        ));
        if (!empty($pids)) {
            foreach ($pids as $pid) {
                if (in_array($pid, $found_ids)) continue;
                $product = wc_get_product($pid);
                if ($product) {
                    $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
                    $results[] = [
                        'id' => $pid,
                        'name' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'permalink' => get_permalink($pid),
                        'image' => $thumb ?: '',
                    ];
                    $found_ids[] = $pid;
                }
            }
        }
    }

    wp_send_json_success(['products' => array_slice($results, 0, 8)]);
}
add_action('wp_ajax_ayra_search_products', 'ayra_ajax_search_products');
add_action('wp_ajax_nopriv_ayra_search_products', 'ayra_ajax_search_products');

// ─── AJAX: Empty cart (checkout delete button) ──────────
function ayra_ajax_empty_cart()
{
    WC()->cart->empty_cart();
    wp_send_json_success();
}
add_action('wp_ajax_ayra_empty_cart', 'ayra_ajax_empty_cart');
add_action('wp_ajax_nopriv_ayra_empty_cart', 'ayra_ajax_empty_cart');
