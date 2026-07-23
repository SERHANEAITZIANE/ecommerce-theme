<?php
/**
 * AYRA Homewear - WooCommerce Setup & Hooks
 */
defined('ABSPATH') || exit;

// ─── Custom checkout fields ─────────────────────────────
function ayra_override_checkout_fields($fields) {
    // Remove all default billing fields
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_email']);
    unset($fields['billing']['billing_last_name']);
    
    // Remove shipping fields
    $fields['shipping'] = [];
    
    // Remove order comments
    unset($fields['order']['order_comments']);
    
    // Customize remaining fields
    $fields['billing']['billing_first_name'] = [
        'type'        => 'text',
        'label'       => 'الاسم الكامل',
        'placeholder' => 'أدخل/ي الاسم الكامل',
        'required'    => true,
        'class'       => ['form-row-first', 'ayra-field'],
        'priority'    => 10,
    ];
    
    $fields['billing']['billing_phone'] = [
        'type'        => 'tel',
        'label'       => 'رقم الهاتف',
        'placeholder' => '0778 XX XX XX',
        'required'    => true,
        'class'       => ['form-row-last', 'ayra-field'],
        'priority'    => 20,
    ];
    
    // Delivery type radio cards (rendered by ayra_render_delivery_type_cards)
    $fields['billing']['billing_delivery_type'] = [
        'type'        => 'radio',
        'label'       => 'طريقة التوصيل',
        'required'    => true,
        'class'       => ['form-row-wide', 'ayra-field'],
        'options'     => [
            'home' => 'توصيل إلى المنزل',
            'desk' => 'توصيل إلى المكتب (StopDesk)'
        ],
        'default'     => 'home',
        'priority'    => 30,
    ];
    
    // Wilaya dropdown
    $fields['billing']['billing_wilaya'] = [
        'type'        => 'select',
        'label'       => 'الولاية',
        'required'    => true,
        'class'       => ['form-row-wide', 'ayra-field'],
        'options'     => ayra_get_wilayas_dropdown(),
        'priority'    => 40,
    ];
    
    // Commune dropdown
    $fields['billing']['billing_commune'] = [
        'type'        => 'select',
        'label'       => 'البلدية',
        'required'    => true,
        'class'       => ['form-row-wide', 'ayra-field'],
        'options'     => ['' => 'اختر/ي الولاية أولاً'],
        'priority'    => 50,
    ];
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'ayra_override_checkout_fields');

// ─── Render delivery type as tappable radio cards ───────
// Same field name (billing_delivery_type) and values (home/desk),
// so order meta, ZR Express parcels and Sheets sync are untouched.
function ayra_render_delivery_type_cards($field, $key, $args, $value) {
    if ($key !== 'billing_delivery_type') return $field;
    if (empty($value)) $value = 'home';

    $icons = [
        'home' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'desk' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><line x1="9" y1="10" x2="9.01" y2="10"/><line x1="15" y1="10" x2="15.01" y2="10"/><line x1="9" y1="14" x2="9.01" y2="14"/><line x1="15" y1="14" x2="15.01" y2="14"/></svg>',
    ];
    $times = [
        'home' => 'التوصيل حتى باب المنزل',
        'desk' => 'استلام من مكتب ZR Express',
    ];

    $html  = '<p class="form-row form-row-wide ayra-field ayra-dlv-field" id="billing_delivery_type_field" data-priority="' . esc_attr($args['priority']) . '">';
    $html .= '<label class="ayra-dlv-group-label">' . esc_html($args['label']) . '&nbsp;<abbr class="required" title="مطلوب">*</abbr></label>';
    $html .= '<span class="ayra-delivery-cards" id="ayra-delivery-cards">';
    foreach ($args['options'] as $opt_val => $opt_label) {
        $active = ($value === $opt_val) ? ' active' : '';
        $html .= '<label class="ayra-dlv-card' . $active . '" data-type="' . esc_attr($opt_val) . '">';
        $html .= '<input type="radio" class="ayra-dlv-radio" name="' . esc_attr($key) . '" id="' . esc_attr($key . '_' . $opt_val) . '" value="' . esc_attr($opt_val) . '" ' . checked($value, $opt_val, false) . '>';
        $html .= '<span class="ayra-dlv-icon">' . (isset($icons[$opt_val]) ? $icons[$opt_val] : '') . '</span>';
        $html .= '<span class="ayra-dlv-info">';
        $html .= '<span class="ayra-dlv-name">' . esc_html($opt_label) . '</span>';
        $html .= '<span class="ayra-dlv-time">' . (isset($times[$opt_val]) ? esc_html($times[$opt_val]) : '') . '</span>';
        $html .= '<span class="ayra-dlv-price" data-type="' . esc_attr($opt_val) . '"></span>';
        $html .= '</span>';
        $html .= '<span class="ayra-dlv-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';
        $html .= '</label>';
    }
    $html .= '</span></p>';

    return $html;
}
add_filter('woocommerce_form_field', 'ayra_render_delivery_type_cards', 10, 4);

// ─── Save custom checkout fields ────────────────────────
function ayra_save_custom_fields($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    if (!empty($_POST['billing_wilaya'])) {
        $wilaya_code = sanitize_text_field($_POST['billing_wilaya']);
        update_post_meta($order_id, '_billing_wilaya', $wilaya_code);

        // Map to WooCommerce native billing_state so POS / admin can see it
        $wilayas     = ayra_get_wilayas_data();
        $wilaya_name = isset($wilayas[$wilaya_code]) ? $wilayas[$wilaya_code]['name'] : $wilaya_code;
        $order->set_billing_state($wilaya_name);
    }

    if (!empty($_POST['billing_commune'])) {
        $commune = sanitize_text_field($_POST['billing_commune']);
        // For desk orders, commune value has format "District__hub__N" — strip the suffix
        if (strpos($commune, '__hub__') !== false) {
            $commune = explode('__hub__', $commune)[0];
        }
        update_post_meta($order_id, '_billing_commune', $commune);

        // Map to WooCommerce native billing_city so POS / admin can see it
        $order->set_billing_city($commune);
    }

    if (!empty($_POST['billing_delivery_type'])) {
        $delivery_type = sanitize_text_field($_POST['billing_delivery_type']);
        update_post_meta($order_id, '_billing_delivery_type', $delivery_type);

        // Store delivery type in order address_2 as a readable label
        $delivery_label = $delivery_type === 'home' ? 'توصيل إلى المنزل' : 'توصيل إلى المكتب';
        $order->set_billing_address_2($delivery_label);
    }

    // Save StopDesk hub data for parcel creation
    if (!empty($_POST['billing_hub_id'])) {
        update_post_meta($order_id, '_billing_hub_id', sanitize_text_field($_POST['billing_hub_id']));
    }
    if (!empty($_POST['billing_desk_district_id'])) {
        update_post_meta($order_id, '_billing_desk_district_id', sanitize_text_field($_POST['billing_desk_district_id']));
    }
    if (!empty($_POST['billing_desk_city_id'])) {
        update_post_meta($order_id, '_billing_desk_city_id', sanitize_text_field($_POST['billing_desk_city_id']));
    }

    // ── Format phone number: 0xxx → +213xxx ──
    $phone = $order->get_billing_phone();
    if ($phone) {
        // Remove spaces, dashes, dots
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        // Convert local 0x to international +213x
        if (preg_match('/^0(\d{9})$/', $phone, $m)) {
            $phone = '+213' . $m[1];
        }
        // If they typed 213xxx without +, add it
        elseif (preg_match('/^213(\d{9})$/', $phone, $m)) {
            $phone = '+213' . $m[1];
        }
        $order->set_billing_phone($phone);
    }

    $order->save();
}
add_action('woocommerce_checkout_update_order_meta', 'ayra_save_custom_fields');

// ─── Display custom fields in admin ─────────────────────
function ayra_display_custom_fields_admin($order) {
    $order_id = $order->get_id();
    $wilaya = get_post_meta($order_id, '_billing_wilaya', true);
    $commune = get_post_meta($order_id, '_billing_commune', true);
    $delivery_type = get_post_meta($order_id, '_billing_delivery_type', true);
    
    echo '<p><strong>الولاية:</strong> ' . esc_html($wilaya) . '</p>';
    echo '<p><strong>البلدية:</strong> ' . esc_html($commune) . '</p>';
    echo '<p><strong>نوع التوصيل:</strong> ' . ($delivery_type === 'home' ? 'إلى المنزل' : 'إلى المكتب') . '</p>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'ayra_display_custom_fields_admin');

// ─── Free delivery threshold (activable in ZR settings) ─
function ayra_is_free_shipping_active() {
    if (get_option('ayra_free_shipping_enabled') !== 'yes') return false;
    $min = (float) get_option('ayra_free_shipping_min', 15000);
    if ($min <= 0) return false;
    if (!function_exists('WC') || !WC()->cart) return false;
    // Products subtotal only — fees excluded, so the check can't feed back on itself
    return (float) WC()->cart->get_cart_contents_total() >= $min;
}

// ─── Show "التوصيل: مجاني" row in the order summary ─────
function ayra_free_shipping_review_row() {
    if (!ayra_is_free_shipping_active()) return;
    echo '<tr class="fee ayra-free-shipping-row"><th>التوصيل</th><td data-title="التوصيل">مجاني ✓</td></tr>';
}
add_action('woocommerce_review_order_before_order_total', 'ayra_free_shipping_review_row');

// ─── Add delivery fee to cart (uses ZR Express live pricing) ─
function ayra_add_delivery_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_cart_calculate_fees') >= 2) return;

    $wilaya_code   = '';
    $delivery_type = '';

    if (!empty($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
        $wilaya_code   = isset($post_data['billing_wilaya'])        ? sanitize_text_field($post_data['billing_wilaya'])        : '';
        $delivery_type = isset($post_data['billing_delivery_type']) ? sanitize_text_field($post_data['billing_delivery_type']) : '';
    } else {
        $wilaya_code   = WC()->session->get('ayra_billing_wilaya');
        $delivery_type = WC()->session->get('ayra_billing_delivery_type');
    }

    if ($wilaya_code && $delivery_type) {
        $wcode = intval($wilaya_code);

        // ── Special pricing for Ain Defla (wilaya 44) ──
        // Ain Defla ville (commune) = 200 DA, other communes = 500 DA
        $commune_name = '';
        if (!empty($_POST['post_data'])) {
            parse_str($_POST['post_data'], $pd);
            $commune_name = isset($pd['billing_commune']) ? sanitize_text_field($pd['billing_commune']) : '';
        } else {
            $commune_name = WC()->session->get('ayra_billing_commune', '');
        }
        // Strip hub suffix for desk orders (format: "District__hub__N")
        if (strpos($commune_name, '__hub__') !== false) {
            $commune_name = explode('__hub__', $commune_name)[0];
        }
        // Save commune to session for consistent access
        if ($commune_name) {
            WC()->session->set('ayra_billing_commune', $commune_name);
        }

        if ($wcode === 44 && $delivery_type === 'home') {
            // Ain Defla ville = 200 DA, other communes = 500 DA
            // ZR data uses "Ain-Defla" (hyphenated)
            $commune_lower = mb_strtolower(trim($commune_name));
            // Normalize: remove hyphens and extra spaces for matching
            $commune_normalized = str_replace(['-', '  '], [' ', ' '], $commune_lower);
            $commune_normalized = trim($commune_normalized);
            if ($commune_normalized === 'ain defla' || $commune_lower === 'ain-defla' || $commune_lower === 'aïn defla' || $commune_lower === 'aïn-defla' || $commune_lower === 'عين الدفلى') {
                $delivery_fee = 200;
            } else {
                $delivery_fee = 500;
            }
        }
        // Try ZR JSON pricing first (most accurate)
        elseif (function_exists('ayra_zr_get_price')) {
            $zr_type      = ($delivery_type === 'home') ? 'home' : 'desk';
            $delivery_fee = ayra_zr_get_price($wcode, $zr_type);
        } else {
            // Fallback: legacy pricing table (zero-padded codes)
            $padded       = str_pad($wilaya_code, 2, '0', STR_PAD_LEFT);
            $type_key     = ($delivery_type === 'home') ? 'to_home' : 'to_desk';
            $delivery_fee = ayra_get_delivery_price($padded, $type_key);
        }

        $delivery_label = ($delivery_type === 'home') ? 'توصيل للمنزل' : 'توصيل للمكتب (StopDesk)';


        // Free delivery promo: order total reached the configured threshold
        if (ayra_is_free_shipping_active()) {
            $delivery_fee = 0;
        }

        if ($delivery_fee > 0) {
            $cart->add_fee($delivery_label, $delivery_fee);
        }

        WC()->session->set('ayra_billing_wilaya', $wilaya_code);
        WC()->session->set('ayra_billing_delivery_type', $delivery_type);
    }
}
add_action('woocommerce_cart_calculate_fees', 'ayra_add_delivery_fee');

// ─── Disable shipping methods (we handle it custom) ─────
add_filter('woocommerce_shipping_methods', function($methods) {
    return [];
});
add_filter('woocommerce_cart_needs_shipping', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

// ─── Set Algeria as default country ─────────────────────
add_filter('default_checkout_billing_country', function() {
    return 'DZ';
});

// ─── Remove coupon form from checkout ───────────────────
remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

// ─── Customize "Place Order" button text ────────────────
add_filter('woocommerce_order_button_text', function() {
    return 'تأكيد الطلب ✓';
});

// ─── Extract size name from variation attributes ──────────
// ─── Extract size name from variation attributes ──────────
function ayra_extract_size_from_variation($variation) {
    if (!$variation || !is_a($variation, 'WC_Product_Variation')) return false;

    // Check all attributes for a size match
    foreach ($variation->get_attributes() as $key => $val) {
        if (!$val) continue;
        $clean_key = str_replace('attribute_', '', $key);
        $term = get_term_by('slug', $val, $clean_key);
        $attr_label = ($term && !is_wp_error($term)) ? strtoupper($term->name) : strtoupper(urldecode($val));
        
        // Is it the ONLY attribute?
        if (count($variation->get_attributes()) === 1) return $attr_label;

        // Does the attribute name hint it's a size?
        if (preg_match('/size|taille|مقاس|قياس|حجم/iu', $clean_key)) return $attr_label;

        // Does the value look like a standard clothing size?
        if (preg_match('/^(XXS|XS|S|M|L|XL|XXL|3XL|4XL|5XL|6XL)$/i', $attr_label)) return $attr_label;
    }
    
    // Total fallback - just return the very first attribute value
    $attrs = $variation->get_attributes();
    if (!empty($attrs)) {
        $first_key = array_key_first($attrs);
        $val = $attrs[$first_key];
        $clean_key = str_replace('attribute_', '', $first_key);
        $term = get_term_by('slug', $val, $clean_key);
        return ($term && !is_wp_error($term)) ? strtoupper($term->name) : strtoupper(urldecode($val));
    }

    return false;
}

// ─── Get sizes with stock count (for front page) ────────
function ayra_get_sizes_with_stock() {
    $sizes_count = [];
    $all_data = ayra_get_all_product_filter_data_cached();
    
    foreach ($all_data as $p) {
        foreach ($p['sizes'] as $size_upper => $size_data) {
            if (!isset($sizes_count[$size_upper])) {
                $sizes_count[$size_upper] = 0;
            }
            $sizes_count[$size_upper]++;
        }
    }
    
    return $sizes_count;
}

// ─── Get products by size (Bulletproof matching) ─────────
function ayra_get_products_by_size($size) {
    if (empty($size)) return [];
    $product_ids = [];
    $target_size = strtoupper(urldecode($size));
    
    $all_data = ayra_get_all_product_filter_data_cached();
    
    foreach ($all_data as $p) {
        if (isset($p['sizes'][$target_size])) {
            $product_ids[] = $p['product_id'];
        }
    }
    
    return array_unique($product_ids);
}

// ─── Get variation ID by size (Bulletproof matching) ─────
function ayra_get_variation_id_by_size($product, $size) {
    if (!$product || empty($size)) return false;
    $target_size = strtoupper(urldecode($size));
    
    $all_data = ayra_get_all_product_filter_data_cached();
    $pid = $product->get_id();
    
    foreach ($all_data as $p) {
        if ($p['product_id'] == $pid) {
            if (isset($p['sizes'][$target_size])) {
                return $p['sizes'][$target_size]['variation_id'];
            }
            break;
        }
    }
    return false;
}

// ─── Update Cart Quantity on Checkout ───────────────────
add_action('wp_ajax_ayra_update_checkout_qty', 'ayra_update_checkout_qty');
add_action('wp_ajax_nopriv_ayra_update_checkout_qty', 'ayra_update_checkout_qty');
function ayra_update_checkout_qty() {
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    if ($qty < 1) $qty = 1;
    
    $cart = WC()->cart;
    if ($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $cart->set_quantity($cart_item_key, $qty, false);
        }
        $cart->calculate_totals();
    }
    wp_send_json_success();
}

// ─── Filter main query by size via GET parameter ─────────
// Using pre_get_posts instead of woocommerce_product_query for universal compatibility
add_action('pre_get_posts', 'ayra_apply_size_filter_to_main_query', 20);
function ayra_apply_size_filter_to_main_query($q) {
    // Only run on frontend, main query
    if (is_admin() || !$q->is_main_query()) return;
    
    // Only if filter_size is set
    if (!isset($_GET['filter_size']) || empty($_GET['filter_size'])) return;
    
    // Check if this is a product-related query
    $is_product_query = false;
    $post_type = $q->get('post_type');
    if ($post_type === 'product') $is_product_query = true;
    if (is_array($post_type) && in_array('product', $post_type)) $is_product_query = true;
    if ($q->is_post_type_archive('product')) $is_product_query = true;
    if (function_exists('is_shop') && is_shop()) $is_product_query = true;
    if (function_exists('is_product_category') && is_product_category()) $is_product_query = true;
    
    if (!$is_product_query) return;
    
    $selected_size = sanitize_text_field(wp_unslash($_GET['filter_size']));
    $product_ids = ayra_get_products_by_size_cached($selected_size);
    if (!empty($product_ids)) {
        $q->set('post__in', $product_ids);
    } else {
        $q->set('post__in', [0]); // Force empty result if size matches no products
    }
}

// ═══════════════════════════════════════════════════════════
// TRANSIENT CACHING — Avoid heavy DB queries on every page load
// ═══════════════════════════════════════════════════════════

// ─── Cached: Get sizes with stock (front page) ──────────
function ayra_get_sizes_with_stock_cached() {
    $cached = get_transient('ayra_sizes_stock');
    if ($cached !== false) return $cached;
    
    $result = ayra_get_sizes_with_stock();
    set_transient('ayra_sizes_stock', $result, 6 * HOUR_IN_SECONDS);
    return $result;
}

// ─── Cached: Get size→stock map (shop filter bar) ───────
function ayra_get_size_stock_map_cached() {
    $cached = get_transient('ayra_size_stock_map');
    if ($cached !== false) return $cached;
    
    $size_stock_map = [];
    $all_data = ayra_get_all_product_filter_data_cached();
    
    foreach ($all_data as $p) {
        foreach ($p['sizes'] as $size_str => $size_data) {
            if (!isset($size_stock_map[$size_str])) $size_stock_map[$size_str] = 0;
            $size_stock_map[$size_str] += $size_data['stock'];
        }
    }
    
    set_transient('ayra_size_stock_map', $size_stock_map, 6 * HOUR_IN_SECONDS);
    return $size_stock_map;
}

// ─── Cached: Get products by size (shop filter) ─────────
function ayra_get_products_by_size_cached($size) {
    if (empty($size)) return [];
    $cache_key = 'ayra_prods_' . md5(strtoupper(urldecode($size)));
    
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;
    
    $result = ayra_get_products_by_size($size);
    set_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);
    return $result;
}

// ─── Get all product filter data (for cross-filtered counts) ──
// Optimized: uses direct SQL instead of loading each variation object individually
function ayra_get_all_product_filter_data() {
    global $wpdb;
    
    // Step 1: Get all published variable product IDs
    $product_ids = wc_get_products([
        'status' => 'publish',
        'type'   => 'variable',
        'limit'  => -1,
        'return' => 'ids',
    ]);
    
    if (empty($product_ids)) return [];
    $ids_placeholder = implode(',', array_map('intval', $product_ids));
    
    // Step 2: Bulk load stock using ONE optimized SQL query (No slow LIKE JOIN)
    $variations = $wpdb->get_results("
        SELECT 
            v.ID as variation_id,
            v.post_parent as product_id,
            stock_meta.meta_value as stock_qty,
            stock_status.meta_value as stock_status,
            manage_stock.meta_value as manage_stock
        FROM {$wpdb->posts} v
        LEFT JOIN {$wpdb->postmeta} stock_meta ON v.ID = stock_meta.post_id AND stock_meta.meta_key = '_stock'
        LEFT JOIN {$wpdb->postmeta} stock_status ON v.ID = stock_status.post_id AND stock_status.meta_key = '_stock_status'
        LEFT JOIN {$wpdb->postmeta} manage_stock ON v.ID = manage_stock.post_id AND manage_stock.meta_key = '_manage_stock'
        WHERE v.post_type = 'product_variation'
        AND v.post_status = 'publish'
        AND v.post_parent IN ({$ids_placeholder})
        AND (stock_status.meta_value = 'instock' OR stock_status.meta_value IS NULL)
    ");
    
    if (empty($variations)) return [];
    
    // Step 2.5: Fetch attributes separately to avoid massive JOIN with LIKE
    $variation_ids = [];
    foreach ($variations as $v) $variation_ids[] = (int) $v->variation_id;
    $vids_placeholder = implode(',', $variation_ids);
    
    $attr_results = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ({$vids_placeholder}) 
        AND meta_key LIKE 'attribute_%'
    ");
    
    $var_attrs = [];
    foreach ($attr_results as $row) {
        $var_attrs[$row->post_id] = [
            'key' => $row->meta_key,
            'val' => $row->meta_value
        ];
    }
    
    // Fetch term map to convert slugs (like 's') to names (like 'S')
    $term_map = [];
    $terms = $wpdb->get_results("SELECT t.slug, t.name, tt.taxonomy FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy LIKE 'pa_%'");
    foreach ($terms as $t) {
        $term_map[$t->taxonomy . '_' . $t->slug] = strtoupper($t->name);
    }
    
    // Step 3: Aggregate variations into products
    $variation_sizes = [];
    $var_stock_cache = [];
    foreach ($variations as $var) {
        $managing = ($var->manage_stock === 'yes');
        $stock = (int) $var->stock_qty;
        
        // Skip if managing stock and out of stock
        if ($managing && $stock <= 0) continue;
        
        $vid = $var->variation_id;
        $pid = (int) $var->product_id;
        
        // If we haven't processed this variation's size yet
        if (!isset($var_stock_cache[$vid]) && isset($var_attrs[$vid])) {
            $attr_val = $var_attrs[$vid]['val'];
            $taxonomy = str_replace('attribute_', '', $var_attrs[$vid]['key']);
            
            $map_key = $taxonomy . '_' . $attr_val;
            $size_str = isset($term_map[$map_key]) ? $term_map[$map_key] : strtoupper(urldecode($attr_val));
            
            if (!isset($variation_sizes[$pid])) $variation_sizes[$pid] = [];
            if (!isset($variation_sizes[$pid][$size_str])) {
                $variation_sizes[$pid][$size_str] = [
                    'stock' => 0,
                    'variation_id' => $vid
                ];
            }
            $variation_sizes[$pid][$size_str]['stock'] += ($managing ? $stock : 1);
            
            $var_stock_cache[$vid] = true; // Mark variation as processed
        }
    }
    
    // Step 4: Get categories for ALL products in ONE query (No get_the_terms loop!)
    // Includes ANCESTOR categories, so selecting a parent category (e.g. "Packs")
    // also matches products assigned only to its sub-categories.
    $cat_slug_by_id   = [];
    $cat_parent_by_id = [];
    $cat_terms = $wpdb->get_results("
        SELECT t.term_id, t.slug, tt.parent
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'product_cat'
    ");
    foreach ($cat_terms as $ct) {
        $cat_slug_by_id[(int) $ct->term_id]   = $ct->slug;
        $cat_parent_by_id[(int) $ct->term_id] = (int) $ct->parent;
    }

    $cat_map = [];
    $terms_results = $wpdb->get_results("
        SELECT tr.object_id, tt.term_id
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = 'product_cat'
        AND tr.object_id IN ({$ids_placeholder})
    ");
    foreach ($terms_results as $row) {
        $pid = (int) $row->object_id;
        if (!isset($cat_map[$pid])) $cat_map[$pid] = [];
        // Add the assigned term slug, then walk up its ancestors
        $tid   = (int) $row->term_id;
        $guard = 0;
        while ($tid && isset($cat_slug_by_id[$tid]) && $guard < 10) {
            $cat_map[$pid][] = $cat_slug_by_id[$tid];
            $tid = isset($cat_parent_by_id[$tid]) ? $cat_parent_by_id[$tid] : 0;
            $guard++;
        }
    }
    foreach ($cat_map as $pid => $slugs) {
        $cat_map[$pid] = array_values(array_unique($slugs));
    }
    
    // Step 5: Final output assembly
    $data = [];
    foreach ($product_ids as $product_id) {
        $product_id = (int) $product_id;
        if (!isset($variation_sizes[$product_id]) || empty($variation_sizes[$product_id])) continue;
        
        $data[] = [
            'product_id' => $product_id,
            'cat_slugs'  => isset($cat_map[$product_id]) ? $cat_map[$product_id] : [],
            'sizes'      => $variation_sizes[$product_id],
        ];
    }
    
    return $data;
}

// ─── Cached: Get all product filter data ────────────────
function ayra_get_all_product_filter_data_cached() {
    $cached = get_transient('ayra_product_filter_data');
    if ($cached !== false) return $cached;
    
    $result = ayra_get_all_product_filter_data();
    set_transient('ayra_product_filter_data', $result, 6 * HOUR_IN_SECONDS);
    return $result;
}

// ─── Clear ALL size caches when stock/products change ───
function ayra_clear_size_caches() {
    delete_transient('ayra_sizes_stock');
    delete_transient('ayra_size_stock_map');
    delete_transient('ayra_product_filter_data');
    
    // Clear per-size caches
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ayra_prods_%' OR option_name LIKE '_transient_timeout_ayra_prods_%'");
}

// Auto-clear when stock changes
add_action('woocommerce_product_set_stock', 'ayra_clear_size_caches');
add_action('woocommerce_variation_set_stock', 'ayra_clear_size_caches');
add_action('save_post_product', 'ayra_clear_size_caches');
add_action('woocommerce_update_product', 'ayra_clear_size_caches');
add_action('woocommerce_product_set_stock_status', 'ayra_clear_size_caches');
add_action('woocommerce_variation_set_stock_status', 'ayra_clear_size_caches');
// Category hierarchy changes must also refresh the cached filter data
add_action('created_product_cat', 'ayra_clear_size_caches');
add_action('edited_product_cat', 'ayra_clear_size_caches');
add_action('delete_product_cat', 'ayra_clear_size_caches');

// ─── Hide out-of-stock products from shop/catalog ────────
// If ALL size variations of a product are out of stock, hide it
function ayra_hide_outofstock_from_catalog($q) {
    if (is_admin()) return;
    if (!$q->is_main_query()) return;
    
    $post_type = $q->get('post_type');
    $is_product_query = ($post_type === 'product') || 
                        (is_array($post_type) && in_array('product', $post_type)) ||
                        (function_exists('is_shop') && is_shop()) ||
                        (function_exists('is_product_category') && is_product_category());
    
    if (!$is_product_query) return;
    
    // Only apply when no size filter is active (size filter already handles this)
    if (!empty($_GET['filter_size'])) return;
    
    $meta_query = $q->get('meta_query') ?: [];
    $meta_query[] = [
        'key'     => '_stock_status',
        'value'   => 'instock',
        'compare' => '=',
    ];
    $q->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'ayra_hide_outofstock_from_catalog', 15);
