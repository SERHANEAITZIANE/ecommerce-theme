<?php

/**
 * AYRA Homewear Theme Functions
 * 
 * @package AyraHomewear
 */

defined('ABSPATH') || exit;

define('AYRA_VERSION', '1.0.0');
define('AYRA_DIR', get_template_directory());
define('AYRA_URI', get_template_directory_uri());

// ─── Theme Setup ─────────────────────────────────────────
function ayra_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => __('Primary Menu', 'ayra-homewear'),
    ]);

    // Image sizes
    add_image_size('ayra-product-card', 400, 500, true);
    add_image_size('ayra-product-large', 800, 1000, true);
}
add_action('after_setup_theme', 'ayra_setup');

// ─── Flush Rewrite Rules (fixes product 404s) ───────────
function ayra_flush_rewrite_rules_on_activation()
{
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'ayra_flush_rewrite_rules_on_activation');

// One-time flush if not already done (catches cases where theme was already active)
function ayra_maybe_flush_rewrite_rules()
{
    if (!get_option('ayra_rewrite_rules_flushed_v2')) {
        flush_rewrite_rules();
        update_option('ayra_rewrite_rules_flushed_v2', true);
    }
}
// add_action('init', 'ayra_maybe_flush_rewrite_rules', 99); // Temporarily disabled to prevent 10s load time

// ─── Enqueue Assets ──────────────────────────────────────
function ayra_enqueue_assets()
{
    // Google Fonts
    wp_enqueue_style('ayra-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap', [], null);

    // Theme CSS with dynamic version to prevent browser caching
    $theme_css_ver = file_exists(AYRA_DIR . '/css/theme.css') ? filemtime(AYRA_DIR . '/css/theme.css') : AYRA_VERSION;
    wp_enqueue_style('ayra-theme', AYRA_URI . '/css/theme.css', [], $theme_css_ver);

    // Theme JS with dynamic version to prevent browser caching
    $theme_js_ver = file_exists(AYRA_DIR . '/js/main.js') ? filemtime(AYRA_DIR . '/js/main.js') : AYRA_VERSION;
    wp_enqueue_script('ayra-main', AYRA_URI . '/js/main.js', ['jquery'], $theme_js_ver, true);

    // Localize script with AJAX URL and other data
    wp_localize_script('ayra-main', 'ayra_ajax', [
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ayra_nonce'),
        'cart_url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/cart/',
        'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout/',
        'shop_url' => function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : '/shop/',
        'whatsapp' => '+213778769250',
        'currency' => 'DA',
    ]);
}
add_action('wp_enqueue_scripts', 'ayra_enqueue_assets');

// ─── Include Components ──────────────────────────────────
require_once AYRA_DIR . '/inc/wilayas-data.php';
require_once AYRA_DIR . '/inc/woocommerce-setup.php';
require_once AYRA_DIR . '/inc/ajax-handlers.php';
require_once AYRA_DIR . '/inc/delivery-pricing.php';
require_once AYRA_DIR . '/inc/zr-express-api.php';
require_once AYRA_DIR . '/inc/zr-express-settings.php';
require_once AYRA_DIR . '/inc/zr-data/zr-checkout-data.php'; // ZR JSON data provider
require_once AYRA_DIR . '/inc/google-sheets-sync.php';       // Google Sheets order sync
require_once AYRA_DIR . '/inc/random-daily-sort.php';        // Daily random product sorting
require_once AYRA_DIR . '/inc/announcement-banner.php';       // Scrolling announcement banner
require_once AYRA_DIR . '/inc/reviews-system.php';            // Voice & text reviews

// ─── Cart Fragments for AJAX cart update ─────────────────
function ayra_cart_count_fragment($fragments)
{
    $fragments['.ayra-cart-count'] = '<span class="ayra-cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
    $fragments['.ayra-cart-total'] = '<span class="ayra-cart-total">' . WC()->cart->get_cart_total() . '</span>';

    // Mini cart HTML
    ob_start();
    ayra_render_mini_cart();
    $fragments['.ayra-mini-cart-items'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'ayra_cart_count_fragment');

// ─── Render Mini Cart ────────────────────────────────────
function ayra_render_mini_cart()
{
    echo '<div class="ayra-mini-cart-items">';
    if (WC()->cart->is_empty()) {
        echo '<div class="ayra-mini-cart-empty">';
        echo '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>';
        echo '<p>سلة التسوق فارغة</p>';
        echo '</div>';
    } else {
        foreach (WC()->cart->get_cart() as $cart_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            $thumbnail = get_the_post_thumbnail_url($product_id, 'thumbnail');
            $name = $product->get_name();
            $price = WC()->cart->get_product_price($product);
            $subtotal = WC()->cart->get_product_subtotal($product, $quantity);

            // Get variation attributes
            $variation_info = '';
            if (!empty($cart_item['variation'])) {
                $attrs = [];
                foreach ($cart_item['variation'] as $attr_name => $attr_value) {
                    $clean_name = str_replace('attribute_pa_', '', $attr_name);
                    $clean_name = str_replace('attribute_', '', $clean_name);
                    $attrs[] = ucfirst($attr_value);
                }
                $variation_info = implode(' / ', $attrs);
            }

            echo '<div class="ayra-mini-cart-item" data-cart-key="' . esc_attr($cart_key) . '">';
            echo '<div class="ayra-mini-cart-item-img">';
            if ($thumbnail) {
                echo '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr($name) . '">';
            }
            echo '</div>';
            echo '<div class="ayra-mini-cart-item-info">';
            echo '<h4>' . esc_html($name) . '</h4>';
            if ($variation_info) {
                echo '<span class="ayra-mini-cart-item-variation">' . esc_html($variation_info) . '</span>';
            }
            echo '<div class="ayra-mini-cart-item-qty">';
            echo '<button class="ayra-qty-btn minus" data-cart-key="' . esc_attr($cart_key) . '">−</button>';
            echo '<span class="ayra-qty-value">' . $quantity . '</span>';
            echo '<button class="ayra-qty-btn plus" data-cart-key="' . esc_attr($cart_key) . '">+</button>';
            echo '</div>';
            echo '<span class="ayra-mini-cart-item-price">' . $subtotal . '</span>';
            echo '</div>';
            echo '<button class="ayra-mini-cart-remove" data-cart-key="' . esc_attr($cart_key) . '">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';
            echo '</button>';
            echo '</div>';
        }
    }
    echo '</div>';
}

// ─── Disable default WooCommerce styles ──────────────────
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

// ─── Add body classes ────────────────────────────────────
function ayra_body_classes($classes)
{
    if (is_front_page()) {
        $classes[] = 'ayra-size-selector-page';
    }
    if (function_exists('is_shop') && (is_shop() || is_product_category())) {
        $classes[] = 'ayra-products-page';
    }
    return $classes;
}
add_filter('body_class', 'ayra_body_classes');

// ─── Ensure WooCommerce pages exist ─────────────────────
function ayra_ensure_woo_pages()
{
    if (!function_exists('wc_get_page_id')) {
        return;
    }

    // Check and create shop page
    $shop_id = wc_get_page_id('shop');
    if (!$shop_id || $shop_id == -1 || get_post_status($shop_id) !== 'publish') {
        $page = get_page_by_path('shop');
        if ($page && $page->post_status === 'publish') {
            update_option('woocommerce_shop_page_id', $page->ID);
        } elseif ($page) {
            wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            update_option('woocommerce_shop_page_id', $page->ID);
        } else {
            $page_id = wp_insert_post([
                'post_title' => 'Shop',
                'post_name' => 'shop',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
            if ($page_id && !is_wp_error($page_id)) {
                update_option('woocommerce_shop_page_id', $page_id);
            }
        }
    }

    // Check and create checkout page
    $checkout_id = wc_get_page_id('checkout');
    if (!$checkout_id || $checkout_id == -1 || get_post_status($checkout_id) !== 'publish') {
        $page = get_page_by_path('checkout');
        if ($page && $page->post_status === 'publish') {
            update_option('woocommerce_checkout_page_id', $page->ID);
        } elseif ($page) {
            wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            update_option('woocommerce_checkout_page_id', $page->ID);
        } else {
            $page_id = wp_insert_post([
                'post_title' => 'Checkout',
                'post_name' => 'checkout',
                'post_content' => '[woocommerce_checkout]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
            if ($page_id && !is_wp_error($page_id)) {
                update_option('woocommerce_checkout_page_id', $page_id);
            }
        }
    }

    // Check and create cart page
    $cart_id = wc_get_page_id('cart');
    if (!$cart_id || $cart_id == -1 || get_post_status($cart_id) !== 'publish') {
        $page = get_page_by_path('cart');
        if ($page && $page->post_status === 'publish') {
            update_option('woocommerce_cart_page_id', $page->ID);
        } elseif ($page) {
            wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            update_option('woocommerce_cart_page_id', $page->ID);
        } else {
            $page_id = wp_insert_post([
                'post_title' => 'Cart',
                'post_name' => 'cart',
                'post_content' => '[woocommerce_cart]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
            if ($page_id && !is_wp_error($page_id)) {
                update_option('woocommerce_cart_page_id', $page_id);
            }
        }
    }
}
// add_action('init', 'ayra_ensure_woo_pages', 20); // Temporarily disabled to prevent 10s load time

// ─── Admin: Easy Stock & Price Manager for Size Variations ──
function ayra_add_stock_manager_menu()
{
    add_menu_page(
        'إدارة المخزون والأسعار',
        'مخزون المقاسات',
        'manage_woocommerce',
        'ayra-stock-manager',
        'ayra_stock_manager_page',
        'dashicons-archive',
        56
    );
}
add_action('admin_menu', 'ayra_add_stock_manager_menu');

function ayra_stock_manager_page()
{
    $products = wc_get_products(['status' => 'publish', 'type' => 'variable', 'limit' => -1]);
    ?>
    <div class="wrap">
        <h1>📦 إدارة المخزون والأسعار حسب المقاس</h1>
        <p style="font-size:14px; color:#666;">اضغط "حفظ" بجانب كل منتج، أو "حفظ الكل" لحفظ جميع التعديلات دفعة واحدة.</p>

        <?php
 if (empty($products)): ?>
            <div class="notice notice-warning">
                <p>لا توجد منتجات متغيرة.</p>
            </div>
        <?php
 endif; ?>

        <?php
 if (!empty($products)): ?>
            <p
                style="position:sticky; top:32px; z-index:100; background:#fff; padding:12px 16px; border:2px solid #C9A87C; border-radius:8px; margin:10px 0 20px; text-align:center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <button type="button" id="ayra-save-all-btn" class="button button-primary button-hero"
                    style="background:#C9A87C; border-color:#b8956b; font-size:16px; padding:8px 40px;">
                    💾 حفظ جميع التعديلات
                </button>
                <span id="ayra-save-all-progress" style="display:none; margin-right:15px; font-size:14px; color:#666;"></span>
            </p>
        <?php
 endif; ?>

        <?php
 foreach ($products as $product):
            $ppid = $product->get_id(); ?>
            <div class="ayra-sm-block" data-product-id="<?php
 echo $ppid; ?>"
                style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; margin:15px 0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div
                    style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <h2 style="margin:0; display:flex; align-items:center; gap:10px; font-size:16px;">
                        <?php
 $thumb = get_the_post_thumbnail_url($ppid, 'thumbnail');
                        if ($thumb): ?>
                            <img src="<?php
 echo esc_url($thumb); ?>"
                                style="width:40px; height:40px; object-fit:cover; border-radius:6px;">
                        <?php
 endif; ?>
                        <?php
 echo esc_html($product->get_name()); ?>
                        <small style="color:#999; font-weight:normal;">#<?php
 echo $ppid; ?></small>
                    </h2>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="ayra-sm-status" style="font-size:13px;"></span>
                        <button type="button" class="button button-primary ayra-sm-save" data-product-id="<?php
 echo $ppid; ?>"
                            style="background:#C9A87C; border-color:#b8956b;">💾 حفظ</button>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped" style="border-radius:6px; overflow:hidden;">
                    <thead>
                        <tr>
                            <th style="width:120px;">المقاس</th>
                            <th style="width:150px;">السعر (دج)</th>
                            <th style="width:120px;">المخزون</th>
                            <th style="width:100px;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
 foreach ($product->get_children() as $vid):
                            $v = wc_get_product($vid);
                            if (!$v)
                                continue;
                            $attrs = $v->get_attributes();
                            $labels = [];
                            foreach ($attrs as $k => $val) {
                                if (!$val)
                                    continue;
                                $t = get_term_by('slug', $val, $k);
                                $labels[] = ($t && !is_wp_error($t)) ? $t->name : strtoupper(urldecode($val));
                            }
                            $sz = $labels ? implode(' - ', $labels) : 'N/A';
                            $stk = $v->get_stock_quantity();
                            $prc = $v->get_regular_price();
                            $ok = $v->is_in_stock() && $stk > 0;
                            ?>
                            <tr data-vid="<?php
 echo $vid; ?>">
                                <td><strong class="ayra-sz-badge"
                                        style="font-size:15px; padding:3px 10px; background:<?php
 echo $ok ? '#e6f4ea' : '#fce8e6'; ?>; border-radius:4px; display:inline-block;"><?php
 echo esc_html($sz); ?></strong>
                                </td>
                                <td><input type="number" class="ayra-v-price" value="<?php
 echo esc_attr($prc); ?>" step="any"
                                        style="width:110px; padding:5px 8px; border:1px solid #ccc; border-radius:4px;"> دج</td>
                                <td><input type="number" class="ayra-v-stock"
                                        value="<?php
 echo esc_attr($stk !== null ? $stk : 0); ?>" step="1"
                                        style="width:90px; padding:5px 8px; border:1px solid #ccc; border-radius:4px;"></td>
                                <td><span class="ayra-v-status"
                                        style="font-weight:600; color:<?php
 echo $ok ? '#1e8e3e' : '#d93025'; ?>;"><?php
 echo $ok ? '✓ متوفر' : '✗ نفذ'; ?></span>
                                </td>
                            </tr>
                        <?php
 endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
 endforeach; ?>
    </div>
    <script>
        jQuery(function ($) {
            var nonce = '<?php
 echo wp_create_nonce("ayra_sm_nonce"); ?>';
            function saveBlock($b) {
                return new Promise(function (resolve) {
                    var pid = $b.data('product-id'), vars = {}, $btn = $b.find('.ayra-sm-save'), $st = $b.find('.ayra-sm-status');
                    $b.find('tr[data-vid]').each(function () { vars[$(this).data('vid')] = { price: $(this).find('.ayra-v-price').val(), stock: $(this).find('.ayra-v-stock').val() }; });
                    $btn.prop('disabled', true).text('⏳...');
                    $.post(ajaxurl, { action: 'ayra_sm_save', nonce: nonce, product_id: pid, vars: vars }, function (r) {
                        if (r.success) {
                            $st.html('<span style="color:#1e8e3e">✅ ' + r.data.message + '</span>');
                            $b.find('tr[data-vid]').each(function () { var s = parseInt($(this).find('.ayra-v-stock').val()); $(this).find('.ayra-sz-badge').css('background', s > 0 ? '#e6f4ea' : '#fce8e6'); $(this).find('.ayra-v-status').css('color', s > 0 ? '#1e8e3e' : '#d93025').text(s > 0 ? '✓ متوفر' : '✗ نفذ'); });
                        } else { $st.html('<span style="color:#d93025">❌ ' + (r.data ? r.data.message : 'خطأ') + '</span>'); }
                        $btn.prop('disabled', false).text('💾 حفظ'); resolve(!!r.success);
                    }).fail(function () { $btn.prop('disabled', false).text('💾 حفظ'); $st.html('<span style="color:#d93025">❌ خطأ اتصال</span>'); resolve(false); });
                });
            }
            $(document).on('click', '.ayra-sm-save', function () { saveBlock($(this).closest('.ayra-sm-block')); });
            $('#ayra-save-all-btn').on('click', async function () {
                var $btn = $(this), $prog = $('#ayra-save-all-progress'), $blocks = $('.ayra-sm-block'), total = $blocks.length, done = 0, fail = 0;
                $btn.prop('disabled', true).text('⏳ جاري الحفظ...'); $prog.show();
                for (var i = 0; i < total; i++) { $prog.text((i + 1) + ' / ' + total + '...'); if (await saveBlock($blocks.eq(i))) done++; else fail++; }
                $prog.text('✅ تم حفظ ' + done + ' منتج' + (fail ? ' | ❌ فشل ' + fail : ''));
                $btn.prop('disabled', false).text('💾 حفظ جميع التعديلات');
                setTimeout(function () { $prog.fadeOut(); }, 5000);
            });
        });
    </script>
    <?php

}

// ─── AJAX: Save stock for one product ────────────────────
add_action('wp_ajax_ayra_sm_save', 'ayra_sm_save_handler');

// Note: Delivery fee is handled in woocommerce-setup.php via ayra_add_delivery_fee()

// ─── AJAX: Save delivery choice ──────────────────────────
function ayra_sm_save_handler()
{
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error(['message' => 'صلاحيات غير كافية']);
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ayra_sm_nonce'))
        wp_send_json_error(['message' => 'خطأ أمني']);
    $product_id = intval($_POST['product_id'] ?? 0);
    $vars = $_POST['vars'] ?? [];
    if (!$product_id || empty($vars))
        wp_send_json_error(['message' => 'بيانات ناقصة']);
    $n = 0;
    foreach ($vars as $vid => $d) {
        $vid = intval($vid);
        if ($vid <= 0)
            continue;
        $v = wc_get_product($vid);
        if (!$v)
            continue;
        if (isset($d['price']) && $d['price'] !== '') {
            $p = floatval($d['price']);
            $v->set_regular_price($p);
            $v->set_price($p);
            update_post_meta($vid, '_regular_price', $p);
            update_post_meta($vid, '_price', $p);
        }
        if (isset($d['stock']) && $d['stock'] !== '') {
            $s = intval($d['stock']);
            $v->set_manage_stock(true);
            $v->set_stock_quantity($s);
            $v->set_stock_status($s > 0 ? 'instock' : 'outofstock');
            update_post_meta($vid, '_stock', $s);
            update_post_meta($vid, '_stock_status', $s > 0 ? 'instock' : 'outofstock');
            update_post_meta($vid, '_manage_stock', 'yes');
        }
        $v->save();
        $n++;
    }
    $parent = wc_get_product($product_id);
    if ($parent && $parent->is_type('variable')) {
        $any = false;
        foreach ($parent->get_children() as $cid) {
            if (intval(get_post_meta($cid, '_stock', true)) > 0) {
                $any = true;
                break;
            }
        }
        $parent->set_stock_status($any ? 'instock' : 'outofstock');
        $parent->save();
        wc_delete_product_transients($product_id);
    }
    ayra_clear_size_caches();
    wp_send_json_success(['message' => 'تم تحديث ' . $n . ' مقاس', 'updated' => $n]);
}

// ─── WP Rocket Cache Optimizations ───────────────────────
// Ignore tracking query parameters so they don't bypass the cache
add_filter( 'rocket_cache_ignored_parameters', function( $parameters ) {
    $parameters['ttclid'] = 1;
    $parameters['ayrahomewear_com'] = 1;
    return $parameters;
} );

// Cache size- and category-filtered shop pages separately
add_filter( 'rocket_cache_query_strings', function( $query_strings ) {
    $query_strings[] = 'filter_size';
    $query_strings[] = 'post_type';
    $query_strings[] = 'product_cat';
    return $query_strings;
} );

