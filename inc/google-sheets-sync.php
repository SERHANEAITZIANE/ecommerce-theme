<?php
/**
 * AYRA Homewear — Google Sheets Order Sync
 * 
 * Automatically sends new WooCommerce orders to Google Sheets
 * via Google Apps Script Web App webhook.
 * 
 * @package AyraHomewear
 */
defined('ABSPATH') || exit;

// ─── Settings Page for Google Sheets URL ─────────────────
function ayra_sheets_settings_menu() {
    add_submenu_page(
        'woocommerce',
        'Google Sheets Sync',
        '📊 Google Sheets',
        'manage_woocommerce',
        'ayra-sheets-sync',
        'ayra_sheets_settings_page'
    );
}
add_action('admin_menu', 'ayra_sheets_settings_menu');

function ayra_sheets_register_settings() {
    register_setting('ayra_sheets_settings', 'ayra_sheets_webhook_url');
    register_setting('ayra_sheets_settings', 'ayra_sheets_sync_secret');
}
add_action('admin_init', 'ayra_sheets_register_settings');

function ayra_sheets_settings_page() {
    $webhook_url = get_option('ayra_sheets_webhook_url', '');
    $sync_secret = get_option('ayra_sheets_sync_secret', '');
    $last_sync = get_option('ayra_sheets_last_sync', '');
    $last_error = get_option('ayra_sheets_last_error', '');
    ?>
    <div class="wrap">
        <h1>📊 Google Sheets Sync — AYRA Homewear</h1>
        <p style="font-size:14px; color:#666;">ربط الطلبات تلقائياً مع Google Sheets للتأكيد عبر الهاتف</p>
        
        <form method="post" action="options.php">
            <?php settings_fields('ayra_sheets_settings'); ?>
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="ayra_sheets_webhook_url">🔗 رابط Google Apps Script</label>
                    </th>
                    <td>
                        <input type="url" id="ayra_sheets_webhook_url" name="ayra_sheets_webhook_url" 
                               value="<?php echo esc_attr($webhook_url); ?>" 
                               class="regular-text" style="width:100%; max-width:600px; direction:ltr;"
                               placeholder="https://script.google.com/macros/s/XXXXXXX/exec">
                        <p class="description">الرابط الذي تحصل عليه بعد نشر Apps Script كـ Web App</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ayra_sheets_sync_secret">🔑 مفتاح الأمان (اختياري)</label>
                    </th>
                    <td>
                        <input type="text" id="ayra_sheets_sync_secret" name="ayra_sheets_sync_secret" 
                               value="<?php echo esc_attr($sync_secret); ?>" 
                               class="regular-text" style="max-width:400px; direction:ltr;"
                               placeholder="اختياري — لحماية إضافية">
                        <p class="description">نفس المفتاح يجب أن يكون في خصائص Apps Script</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('💾 حفظ الإعدادات'); ?>
        </form>
        
        <hr>
        <h2>📊 حالة المزامنة</h2>
        <table class="widefat" style="max-width:600px;">
            <tr>
                <td><strong>آخر مزامنة ناجحة</strong></td>
                <td><?php echo $last_sync ? esc_html($last_sync) : '—'; ?></td>
            </tr>
            <tr>
                <td><strong>آخر خطأ</strong></td>
                <td style="color:<?php echo $last_error ? '#d93025' : '#1e8e3e'; ?>">
                    <?php echo $last_error ? esc_html($last_error) : '✅ لا أخطاء'; ?>
                </td>
            </tr>
            <tr>
                <td><strong>حالة الرابط</strong></td>
                <td>
                    <?php if ($webhook_url): ?>
                        <span style="color:#1e8e3e;">✅ مُعدّ</span>
                    <?php else: ?>
                        <span style="color:#d93025;">❌ غير مُعدّ</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <?php if ($webhook_url): ?>
        <p style="margin-top:20px;">
            <button type="button" id="ayra-test-sync" class="button button-secondary" 
                    onclick="ayraTestSync()" style="font-size:14px; padding:6px 20px;">
                🧪 اختبار الاتصال
            </button>
            <span id="ayra-test-result" style="margin-right:10px;"></span>
        </p>
        <script>
        function ayraTestSync() {
            var btn = document.getElementById('ayra-test-sync');
            var result = document.getElementById('ayra-test-result');
            btn.disabled = true; btn.textContent = '⏳ جاري الاختبار...';
            result.innerHTML = '';
            
            fetch('<?php echo esc_url($webhook_url); ?>')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                result.innerHTML = '<span style="color:#1e8e3e;">✅ ' + (d.message || 'متصل') + '</span>';
            })
            .catch(function(e) {
                result.innerHTML = '<span style="color:#d93025;">❌ فشل الاتصال: ' + e.message + '</span>';
            })
            .finally(function() {
                btn.disabled = false; btn.textContent = '🧪 اختبار الاتصال';
            });
        }
        </script>
        <?php endif; ?>
    </div>
    <?php
}

// ─── Sync Order to Google Sheets on new order ────────────
function ayra_sheets_sync_order($order_id) {
    // Prevent duplicate syncs — check both synced flag and lock
    if (get_post_meta($order_id, '_ayra_sheets_synced', true)) {
        return;
    }
    
    // Lock to prevent race condition between multiple hooks
    $lock_key = '_ayra_sheets_syncing';
    if (get_post_meta($order_id, $lock_key, true)) {
        return; // Another process is already syncing this order
    }
    update_post_meta($order_id, $lock_key, time());
    
    $webhook_url = get_option('ayra_sheets_webhook_url', '');
    if (empty($webhook_url)) {
        return; // Not configured
    }
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Only sync processing (en cours) orders
    if ($order->get_status() !== 'processing') {
        delete_post_meta($order_id, $lock_key);
        return;
    }
    
    // ── Collect order data ──
    $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $phone = $order->get_billing_phone();
    
    // Products with size info, truncated for ZR
    $products_arr = [];
    $total_qty = 0;
    foreach ($order->get_items() as $item) {
        $qty = $item->get_quantity();
        $total_qty += $qty;
        $product_name = $item->get_name();
        
        $products_arr[] = $product_name . ($qty > 1 ? ' × ' . $qty : '');
    }
    $products_str = implode(' | ', $products_arr);
    
    // Get custom fields
    $wilaya_code = get_post_meta($order_id, '_billing_wilaya', true);
    $commune = get_post_meta($order_id, '_billing_commune', true);
    $delivery_type = get_post_meta($order_id, '_billing_delivery_type', true);
    
    // If it's a desk order, resolve the desk name for the Google Sheet so it displays correctly in the commune column
    if ($delivery_type === 'desk') {
        $hub_id = get_post_meta($order_id, '_billing_hub_id', true);
        if ($hub_id && function_exists('ayra_zr_get_desks')) {
            $desks = ayra_zr_get_desks();
            foreach ($desks as $d) {
                if (strval($d['id'] ?? '') === strval($hub_id)) {
                    $commune = ($d['name'] ?? '') . ' (' . ($d['district'] ?? '') . ')';
                    break;
                }
            }
        }
    }
    
    // Calculate products price (without delivery fee)
    $products_price = floatval($order->get_subtotal());
    
    // ── Build payload ──
    $payload = [
        'order_id'       => $order_id,
        'date'           => $order->get_date_created()->date('Y-m-d H:i:s'),
        'name'           => $name,
        'phone'          => $phone,
        'products'       => $products_str,
        'qty'            => $total_qty,
        'products_price' => $products_price,
        'delivery_type'  => $delivery_type ?: 'home',
        'wilaya_code'    => intval($wilaya_code),
        'commune'        => $commune ?: '',
        'total'          => floatval($order->get_total()),
        'secret'         => get_option('ayra_sheets_sync_secret', ''),
    ];
    
    // ── Send to Google Sheets ──
    $response = wp_remote_post($webhook_url, [
        'timeout' => 30,
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        update_option('ayra_sheets_last_error', $error_msg . ' (' . date('Y-m-d H:i') . ')');
        error_log('[AYRA Sheets] Sync failed for order #' . $order_id . ': ' . $error_msg);
        
        // Schedule retry in 5 minutes
        wp_schedule_single_event(time() + 300, 'ayra_sheets_retry_sync', [$order_id]);
        return;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($code >= 200 && $code < 400 && !empty($body['success'])) {
        // Success (or duplicate — both count as synced)
        update_post_meta($order_id, '_ayra_sheets_synced', 'yes');
        update_post_meta($order_id, '_ayra_sheets_synced_at', current_time('mysql'));
        delete_post_meta($order_id, '_ayra_sheets_syncing');
        update_option('ayra_sheets_last_sync', 'طلب #' . $order_id . ' — ' . date('Y-m-d H:i'));
        update_option('ayra_sheets_last_error', ''); // Clear last error
        $is_dup = !empty($body['duplicate']) ? ' (duplicate skipped)' : '';
        error_log('[AYRA Sheets] Synced order #' . $order_id . ' successfully' . $is_dup);
    } else {
        $error = isset($body['error']) ? $body['error'] : 'HTTP ' . $code;
        delete_post_meta($order_id, '_ayra_sheets_syncing');
        update_option('ayra_sheets_last_error', 'طلب #' . $order_id . ': ' . $error . ' (' . date('Y-m-d H:i') . ')');
        error_log('[AYRA Sheets] Sync error for order #' . $order_id . ': ' . $error);
        
        // Schedule retry only if not already synced
        if (!get_post_meta($order_id, '_ayra_sheets_synced', true)) {
            wp_schedule_single_event(time() + 300, 'ayra_sheets_retry_sync', [$order_id]);
        }
    }
}

// Hook into order creation — fires ONCE when checkout is processed (not on page refresh)
add_action('woocommerce_checkout_order_processed', 'ayra_sheets_sync_order', 20);

// Backup: also sync when order status changes to processing (in case first hook missed)
add_action('woocommerce_order_status_processing', 'ayra_sheets_sync_order', 20);

// ─── Retry mechanism ────────────────────────────────────
add_action('ayra_sheets_retry_sync', 'ayra_sheets_retry_sync_handler');
function ayra_sheets_retry_sync_handler($order_id) {
    // Only retry if not already synced
    if (get_post_meta($order_id, '_ayra_sheets_synced', true)) {
        return;
    }
    delete_post_meta($order_id, '_ayra_sheets_syncing'); // Clear lock
    ayra_sheets_sync_order($order_id);
}

// ─── AJAX endpoint for fetching all desks list ───────────
add_action('wp_ajax_ayra_sheets_get_desks', 'ayra_sheets_get_desks');
add_action('wp_ajax_nopriv_ayra_sheets_get_desks', 'ayra_sheets_get_desks');

function ayra_sheets_get_desks() {
    $secret = get_option('ayra_sheets_sync_secret', '');
    $provided_secret = isset($_GET['secret']) ? sanitize_text_field($_GET['secret']) : '';
    
    if ($secret && $provided_secret !== $secret) {
        wp_send_json_error(['error' => 'Invalid secret']);
        return;
    }
    
    $desks = get_option('ayra_zr_desks_by_wilaya', []);
    wp_send_json_success(['desks' => $desks]);
}

// ─── AJAX endpoint for fetching all communes list (grouped by wilaya code) ───
add_action('wp_ajax_ayra_sheets_get_communes', 'ayra_sheets_get_communes');
add_action('wp_ajax_nopriv_ayra_sheets_get_communes', 'ayra_sheets_get_communes');

function ayra_sheets_get_communes() {
    $secret = get_option('ayra_sheets_sync_secret', '');
    $provided_secret = isset($_GET['secret']) ? sanitize_text_field($_GET['secret']) : '';
    
    if ($secret && $provided_secret !== $secret) {
        wp_send_json_error(['error' => 'Invalid secret']);
        return;
    }
    
    if (function_exists('ayra_zr_get_communes')) {
        $communes = ayra_zr_get_communes();
    } else {
        $communes = [];
    }
    
    $communes_by_wilaya = [];
    foreach ($communes as $c) {
        $wcode = intval($c['wilaya_code'] ?? 0);
        if ($wcode > 0) {
            $communes_by_wilaya[$wcode][] = $c['name'] ?? '';
        }
    }
    
    wp_send_json_success(['communes' => $communes_by_wilaya]);
}

// ─── AJAX endpoint for ZR send from Google Sheets ───────
add_action('wp_ajax_ayra_sheets_send_to_zr', 'ayra_sheets_send_to_zr');
add_action('wp_ajax_nopriv_ayra_sheets_send_to_zr', 'ayra_sheets_send_to_zr');

function ayra_sheets_send_to_zr() {
    // Verify secret
    $secret = get_option('ayra_sheets_sync_secret', '');
    $provided_secret = isset($_POST['secret']) ? sanitize_text_field($_POST['secret']) : '';
    
    if ($secret && $provided_secret !== $secret) {
        wp_send_json_error(['error' => 'Invalid secret']);
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['error' => 'Missing order_id']);
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['error' => 'Order not found']);
        return;
    }
    
    // ── Update order meta with changes from Google Sheets ──
    $delivery_type_raw = isset($_POST['delivery_type']) ? sanitize_text_field($_POST['delivery_type']) : '';
    $wilaya_name_raw = isset($_POST['wilaya']) ? sanitize_text_field($_POST['wilaya']) : '';
    $commune = isset($_POST['commune']) ? sanitize_text_field($_POST['commune']) : '';
    $desk_id = isset($_POST['desk_id']) ? sanitize_text_field($_POST['desk_id']) : '';

    // Extract wilaya code from e.g. "16 - Alger"
    $wilaya_code = 0;
    if ($wilaya_name_raw && preg_match('/^(\d+)/', $wilaya_name_raw, $m)) {
        $wilaya_code = intval($m[1]);
    }

    if ($delivery_type_raw) {
        // Map Arabic label to internal code
        $delivery_type = (strpos($delivery_type_raw, 'مكتب') !== false) ? 'desk' : 'home';
        update_post_meta($order_id, '_billing_delivery_type', $delivery_type);
        
        $delivery_label = ($delivery_type === 'home') ? 'توصيل إلى المنزل' : 'توصيل إلى المكتب';
        $order->set_billing_address_2($delivery_label);
        
        if ($delivery_type === 'home') {
            // Clear desk/hub metadata to ensure home delivery is parsed cleanly
            delete_post_meta($order_id, '_billing_hub_id');
            delete_post_meta($order_id, '_billing_desk_district_id');
            delete_post_meta($order_id, '_billing_desk_city_id');
        }
    }

    if ($wilaya_code) {
        update_post_meta($order_id, '_billing_wilaya', $wilaya_code);
    }

    if (isset($delivery_type) && $delivery_type === 'desk') {
        if ($desk_id) {
            update_post_meta($order_id, '_billing_hub_id', $desk_id);
            
            // Look up the desk in our database to find district ID and city ID
            if (function_exists('ayra_zr_get_desks')) {
                $desks = ayra_zr_get_desks();
                foreach ($desks as $d) {
                    if (strval($d['id'] ?? '') === strval($desk_id)) {
                        update_post_meta($order_id, '_billing_desk_district_id', $d['district_territory_id'] ?? '');
                        update_post_meta($order_id, '_billing_desk_city_id', $d['city_territory_id'] ?? '');
                        
                        // Save street address
                        if (isset($d['street'])) {
                            update_post_meta($order_id, '_shipping_address_1', $d['street']);
                        }
                        
                        // Overwrite commune with desk details
                        $commune = ($d['name'] ?? '') . ' (' . ($d['district'] ?? '') . ')';
                        break;
                    }
                }
            }
        }
    }

    if ($commune) {
        update_post_meta($order_id, '_billing_commune', $commune);
    }

    // Save WC order changes
    $order->save();
    
    // Use existing ZR Express API
    $zr = new Ayra_ZR_Express_API();
    $result = $zr->create_parcel_from_order($order);
    
    if (is_wp_error($result)) {
        wp_send_json(['success' => false, 'error' => $result->get_error_message()]);
        return;
    }
    
    // Extract tracking/barcode
    $tracking = '';
    if (isset($result['barcode'])) $tracking = $result['barcode'];
    elseif (isset($result['trackingCode'])) $tracking = $result['trackingCode'];
    elseif (isset($result['id'])) $tracking = $result['id'];
    
    // Save tracking to order
    update_post_meta($order_id, '_zr_tracking', $tracking);
    update_post_meta($order_id, '_zr_parcel_id', $result['id'] ?? '');
    
    // Update order status
    $order->update_status('processing', 'تم إرسال الطرد إلى ZR Express — ' . $tracking);
    
    wp_send_json(['success' => true, 'tracking' => $tracking, 'barcode' => $tracking]);
}
