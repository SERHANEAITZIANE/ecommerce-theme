<?php
/**
 * AYRA Homewear - ZR Express Admin Settings & Integration
 *
 * Settings page, manual push button, admin metabox for ZR Express.
 * Orders are ONLY pushed to ZR Express manually via the admin panel
 * and ONLY when the order status is "completed".
 *
 * @package AyraHomewear
 */
defined('ABSPATH') || exit;

/* ═══════════════════════════════════════════════════════════
   1. ADMIN MENU & SETTINGS PAGE
   ═══════════════════════════════════════════════════════════ */

function ayra_zr_admin_menu()
{
    add_menu_page(
        'ZR Express',
        'ZR Express',
        'manage_woocommerce',
        'ayra-zr-express',
        'ayra_zr_settings_page',
        'dashicons-airplane',
        56
    );
}
add_action('admin_menu', 'ayra_zr_admin_menu');

function ayra_zr_register_settings()
{
    register_setting('ayra_zr_settings', 'ayra_zr_tenant_id', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('ayra_zr_settings', 'ayra_zr_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('ayra_zr_settings', 'ayra_free_shipping_enabled', [
        'sanitize_callback' => function ($v) { return $v === 'yes' ? 'yes' : 'no'; },
        'default' => 'no',
    ]);
    register_setting('ayra_zr_settings', 'ayra_free_shipping_min', [
        'sanitize_callback' => 'absint',
        'default' => 15000,
    ]);
}
add_action('admin_init', 'ayra_zr_register_settings');

function ayra_zr_settings_page()
{
    $token = get_option('ayra_zr_tenant_id', '');
    $key = get_option('ayra_zr_api_key', '');
    ?>
    <div class="wrap">
        <h1>⚡ ZR Express — إعدادات التوصيل</h1>
        <p style="color:#6b7280;">API: <code>api.zrexpress.app</code> — أدخل بيانات الاعتماد من لوحة تحكم ZR Express.
            <br>🔗 <a href="https://app.zrexpress.app" target="_blank"
                style="color:#2563eb;text-decoration:underline;">لوحة تحكم ZR Express</a>
        </p>

        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin:12px 0 20px;">
            <p style="margin:0;color:#1e40af;font-size:14px;">
                <strong>ℹ️ طريقة العمل:</strong> الطلبات لا تُرسل تلقائياً إلى ZR Express.
                عندما يكون الطلب بحالة <strong>"مكتمل"</strong>، سيظهر زر <strong>"📤 إرسال إلى ZR Express"</strong>
                في صفحة الطلب. يمكنك أيضاً استخدام الإجراء المجمّع في قائمة الطلبات.
            </p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('ayra_zr_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="ayra_zr_tenant_id">X-Tenant</label></th>
                    <td>
                        <input type="text" id="ayra_zr_tenant_id" name="ayra_zr_tenant_id"
                            value="<?php echo esc_attr($token); ?>" class="regular-text" autocomplete="off"
                            placeholder="Tenant ID">
                        <p class="description">X-Tenant من إعدادات API في ZR Express</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ayra_zr_api_key">X-Api-Key</label></th>
                    <td>
                        <input type="password" id="ayra_zr_api_key" name="ayra_zr_api_key"
                            value="<?php echo esc_attr($key); ?>" class="regular-text" autocomplete="off"
                            placeholder="API Key">
                        <p class="description">X-Api-Key من إعدادات API في ZR Express</p>
                    </td>
                </tr>
            </table>

            <hr>
            <h2>🚚 التوصيل المجاني (Livraison gratuite)</h2>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 18px;margin:12px 0 8px;">
                <p style="margin:0;color:#166534;font-size:14px;">
                    عند التفعيل، يصبح التوصيل <strong>مجانياً</strong> (منزل + مكتب StopDesk) لكل طلب يبلغ الحد الأدنى.
                    يمكنك تفعيله أثناء العروض وإيقافه في أي وقت — يُطبَّق فوراً على صفحة الدفع.
                </p>
            </div>
            <table class="form-table">
                <tr>
                    <th><label for="ayra_free_shipping_enabled">تفعيل التوصيل المجاني</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="ayra_free_shipping_enabled" name="ayra_free_shipping_enabled"
                                value="yes" <?php checked(get_option('ayra_free_shipping_enabled', 'no'), 'yes'); ?>>
                            مفعّل
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="ayra_free_shipping_min">الحد الأدنى للطلب (دج)</label></th>
                    <td>
                        <input type="number" min="0" step="1" id="ayra_free_shipping_min" name="ayra_free_shipping_min"
                            value="<?php echo esc_attr(get_option('ayra_free_shipping_min', 15000)); ?>" class="regular-text"
                            placeholder="15000">
                        <p class="description">مثال: 15000 دج — الطلبات (قيمة المنتجات) التي تبلغ هذا المبلغ تحصل على توصيل مجاني.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('حفظ الإعدادات'); ?>
        </form>

        <hr>
        <h2>اختبار الاتصال</h2>
        <button type="button" id="ayra-zr-test" class="button button-secondary">🔌 اختبار الاتصال</button>
        <span id="ayra-zr-test-result" style="margin-right:12px;font-weight:600;"></span>
        <p class="description">يتحقق من صحة البيانات عبر <code>api.zrexpress.app</code></p>

        <hr>
        <h2>🔄 تحديث البيانات من ZR Express</h2>
        <div style="background:#fdf4ff;border:1px solid #fbcfe8;border-radius:8px;padding:14px 18px;margin:12px 0 20px;">
            <p style="margin:0;color:#86198f;font-size:14px;">
                <strong>جلب الولايات والبلديات ومكاتب StopDesk:</strong> هذا الخيار يقوم بجلب وتحديث جميع بيانات التوصيل من واجهة برمجة تطبيقات ZR Express، بحيث يتم عرضها في صفحة الدفع مباشرة.
            </p>
        </div>
        <button type="button" id="ayra-zr-sync-territories" class="button button-primary" style="background:#c026d3;border-color:#a21caf;">🔄 تحديث الولايات والبلديات الآن</button>
        <span id="ayra-zr-sync-result" style="margin-right:12px;font-weight:600;"></span>

        <hr>
        <h2>🔍 تشخيص المشاكل</h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" id="ayra-zr-debug-errors" class="button button-secondary">📋 عرض آخر الأخطاء</button>
            <button type="button" id="ayra-zr-test-endpoints" class="button button-secondary">🧪 اختبار API Endpoints</button>
        </div>
        <pre id="ayra-zr-debug-output" style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;margin-top:12px;max-height:400px;overflow:auto;font-size:13px;display:none;white-space:pre-wrap;word-break:break-all;direction:ltr;text-align:left;"></pre>

        <script>
            jQuery(function ($) {
                var nonce = '<?php echo wp_create_nonce("ayra_zr_admin"); ?>';

                // Test connection
                $('#ayra-zr-test').on('click', function () {
                    var $b = $(this), $r = $('#ayra-zr-test-result');
                    $b.prop('disabled', true);
                    $r.text('جاري...');
                    $.post(ajaxurl, { action: 'ayra_zr_test_connection', _wpnonce: nonce }, function (r) {
                        $b.prop('disabled', false);
                        $r.text(r.success ? '✅ ' + (r.data.message || 'نجاح') : '❌ ' + (r.data || 'فشل'));
                        $r.css('color', r.success ? '#16a34a' : '#dc2626');
                    }).fail(function () {
                        $b.prop('disabled', false);
                        $r.text('❌ خطأ').css('color', '#dc2626');
                    });
                });

                // Sync Territories
                $('#ayra-zr-sync-territories').on('click', function () {
                    var $b = $(this), $r = $('#ayra-zr-sync-result');
                    $b.prop('disabled', true);
                    $r.text('⏳ يتم الآن جلب البيانات من ZR Express... يرجى الانتظار');
                    $r.css('color', '#475569');
                    $.post(ajaxurl, { action: 'ayra_zr_sync_territories', _wpnonce: nonce }, function (r) {
                        $b.prop('disabled', false);
                        $r.text(r.success ? '✅ ' + r.data.message : '❌ ' + (r.data || 'فشل التحديث'));
                        $r.css('color', r.success ? '#16a34a' : '#dc2626');
                    }).fail(function () {
                        $b.prop('disabled', false);
                        $r.text('❌ خطأ في الاتصال بالخادم').css('color', '#dc2626');
                    });
                });

                // Debug: show last errors
                function debugBtn(btnId, action) {
                    $(btnId).on('click', function () {
                        var $b = $(this), $out = $('#ayra-zr-debug-output');
                        $b.prop('disabled', true);
                        $out.show().text('⏳ جاري...');
                        $.post(ajaxurl, { action: action, _wpnonce: nonce }, function (r) {
                            $b.prop('disabled', false);
                            $out.text(r.success ? r.data.message : '❌ ' + (r.data || 'خطأ'));
                        }).fail(function () {
                            $b.prop('disabled', false);
                            $out.text('❌ خطأ في الاتصال');
                        });
                    });
                }
                debugBtn('#ayra-zr-debug-errors', 'ayra_zr_debug_last_error');
                debugBtn('#ayra-zr-test-endpoints', 'ayra_zr_test_parcel');
            });
        </script>
    </div>
    <?php
}

/* ═══════════════════════════════════════════════════════════
   2. AJAX — TEST CONNECTION + DEBUG
   ═══════════════════════════════════════════════════════════ */

function ayra_zr_ajax_test_connection()
{
    check_ajax_referer('ayra_zr_admin', '_wpnonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorized');

    $api = new Ayra_ZR_Express_API();
    $result = $api->test_credentials();

    if ($result === true) {
        wp_send_json_success(['message' => 'متصل بنجاح ✅']);
    } else {
        wp_send_json_error($result ?: 'فشل الاتصال');
    }
}
add_action('wp_ajax_ayra_zr_test_connection', 'ayra_zr_ajax_test_connection');

// Debug: Find last ZR push error from any order
function ayra_zr_ajax_debug_last_error()
{
    check_ajax_referer('ayra_zr_admin', '_wpnonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorized');

    global $wpdb;

    // Find orders with ZR push errors
    $errors = $wpdb->get_results("
        SELECT pm.post_id AS order_id, pm.meta_value AS error
        FROM {$wpdb->postmeta} pm
        WHERE pm.meta_key = '_zr_push_error'
        AND pm.meta_value != ''
        ORDER BY pm.meta_id DESC
        LIMIT 5
    ");

    if (empty($errors)) {
        wp_send_json_success(['message' => 'لا توجد أخطاء مسجلة ✅']);
    }

    $output = [];
    foreach ($errors as $e) {
        $line = '#' . $e->order_id . ': ' . $e->error;
        // Show stored desk data for diagnosis
        $dt = get_post_meta($e->order_id, '_billing_delivery_type', true);
        if ($dt === 'desk') {
            $hub = get_post_meta($e->order_id, '_billing_hub_id', true);
            $dist = get_post_meta($e->order_id, '_billing_desk_district_id', true);
            $city = get_post_meta($e->order_id, '_billing_desk_city_id', true);
            $line .= "\n  → Desk meta: hub=" . ($hub ?: '(empty)') . ", district=" . ($dist ?: '(empty)') . ", city=" . ($city ?: '(empty)');
        }
        $output[] = $line;
    }

    wp_send_json_success(['message' => implode("\n\n", $output)]);
}
add_action('wp_ajax_ayra_zr_debug_last_error', 'ayra_zr_ajax_debug_last_error');

// Debug: Test API — auto-detect tenant, test POST /parcels
function ayra_zr_ajax_test_parcel()
{
    check_ajax_referer('ayra_zr_admin', '_wpnonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorized');

    $saved_tenant = get_option('ayra_zr_tenant_id', '');
    $key = get_option('ayra_zr_api_key', '');

    if (empty($saved_tenant) || empty($key)) {
        wp_send_json_error('أدخل بيانات API أولاً');
    }

    $results = [];
    $base = 'https://api.zrexpress.app/api/v1';
    $headers_base = [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json',
        'X-Api-Key'    => $key,
    ];

    // ── Step 1: Get profile to find correct tenant ID ──
    $results[] = "══ Step 1: Checking profile ══";
    $profile_resp = wp_remote_get($base . '/users/profile', [
        'timeout' => 15,
        'headers' => array_merge($headers_base, ['X-Tenant' => $saved_tenant]),
    ]);

    $correct_tenant = $saved_tenant;
    if (!is_wp_error($profile_resp) && wp_remote_retrieve_response_code($profile_resp) === 200) {
        $profile = json_decode(wp_remote_retrieve_body($profile_resp), true);
        $results[] = "Name: " . ($profile['firstName'] ?? '') . " " . ($profile['lastName'] ?? '');

        // Find the correct tenant ID from memberships
        if (!empty($profile['memberships'])) {
            foreach ($profile['memberships'] as $m) {
                $results[] = "Membership: " . ($m['tenantName'] ?? '?') . " | type: " . ($m['type'] ?? '?') . " | tenantId: " . ($m['tenantId'] ?? '?');
                if (!empty($m['tenantId'])) {
                    $correct_tenant = $m['tenantId'];
                }
            }
        }

        if ($correct_tenant !== $saved_tenant) {
            $results[] = "\n⚠️ MISMATCH! Your saved X-Tenant: " . $saved_tenant;
            $results[] = "✅ Correct tenant from profile: " . $correct_tenant;
            $results[] = "→ Auto-fixing...";
            update_option('ayra_zr_tenant_id', $correct_tenant);
        } else {
            $results[] = "✅ X-Tenant matches profile";
        }
    } else {
        $results[] = "❌ Profile failed";
    }

    // ── Step 2: Test Territories (Alger) ──
    $results[] = "\n══ Step 2: Testing Territories (Alger) ══";
    $api = new Ayra_ZR_Express_API($correct_tenant, $key);
    $territory = $api->resolve_territories('16', 'Alger Centre');
    if (empty($territory['city_id'])) {
        $results[] = "❌ Failed to resolve Wilaya Alger (16).";
    } else {
        $results[] = "✅ Resolved Alger -> City ID: " . $territory['city_id'];
    }

    // ── Step 3: Test Customer Creation ──
    $results[] = "\n══ Step 3: Testing Customer Creation ══";
    $customer_id = $api->find_or_create_customer('Test AYRA', '+213555000000', '', 'Test Address');
    if (is_wp_error($customer_id)) {
        $results[] = "❌ Customer Error: " . $customer_id->get_error_message();
    } else {
        $results[] = "✅ Customer ID: " . $customer_id;
    }

    // ── Step 4: Test POST /parcels with correct tenant ──
    $results[] = "\n══ Step 4: Testing POST /parcels ══";
    if (!empty($territory['city_id']) && !is_wp_error($customer_id)) {
        $test_payload = [
            'customer' => [
                'customerId' => $customer_id,
                'name' => 'Test AYRA',
                'phone' => ['number1' => '+213555000000', 'number2' => '']
            ],
            'deliveryAddress' => [
                'address' => 'Test Address, Alger',
                'cityTerritoryId' => $territory['city_id'],
                'districtTerritoryId' => $territory['district_id'] ?? null,
            ],
            'orderedProducts' => [
                ['productName' => 'Test Product', 'quantity' => 1, 'unitPrice' => 100, 'stockType' => 'none'],
            ],
            'deliveryType' => 'home',
            'description' => 'TEST - DO NOT SHIP',
            'amount' => 100,
            'externalId' => 'TEST-DELETE-ME-' . time(),
        ];

        $parcel_resp = wp_remote_post($base . '/parcels', [
            'timeout' => 15,
            'headers' => array_merge($headers_base, ['X-Tenant' => $correct_tenant]),
            'body'    => wp_json_encode($test_payload, JSON_UNESCAPED_UNICODE),
        ]);

        if (is_wp_error($parcel_resp)) {
            $results[] = "❌ HTTP Error: " . $parcel_resp->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($parcel_resp);
            $body = wp_remote_retrieve_body($parcel_resp);
            $results[] = "HTTP " . $code . ":\n" . $body;

            if ($code >= 200 && $code < 300) {
                $results[] = "\n✅ POST /parcels WORKS! Parcel creation is supported.";
            } elseif ($code === 403) {
                $results[] = "\n❌ 403 Forbidden — Your account might not have API parcel creation enabled.";
            } elseif ($code === 422 || $code === 400) {
                $results[] = "\n⚠️ Validation error — But the endpoint IS accessible! Just need to fix the payload.";
            }
        }
    } else {
        $results[] = "⏭️ Skipped Parcel Test because Territory or Customer failed.";
    }

    // ── Step 5: Show what's saved ──
    $results[] = "\n══ Step 3: Current Settings ══";
    $results[] = "X-Tenant (saved): " . get_option('ayra_zr_tenant_id', '(empty)');
    $results[] = "X-Api-Key: " . (empty($key) ? '(empty)' : substr($key, 0, 8) . '...');

    wp_send_json_success(['message' => implode("\n", $results)]);
}
add_action('wp_ajax_ayra_zr_test_parcel', 'ayra_zr_ajax_test_parcel');

/* ═══════════════════════════════════════════════════════════
   3. NO AUTO-PUSH — Manual only for completed orders
   ═══════════════════════════════════════════════════════════ */

// (Removed: No automatic push on woocommerce_checkout_order_processed)

/* ═══════════════════════════════════════════════════════════
   4. ADMIN METABOX — ORDER PAGE (Completed orders only)
   ═══════════════════════════════════════════════════════════ */

function ayra_zr_add_order_metabox()
{
    add_meta_box('ayra_zr_metabox', '📦 ZR Express', 'ayra_zr_render_metabox', 'shop_order', 'side', 'high');
    if (function_exists('wc_get_page_screen_id')) {
        add_meta_box('ayra_zr_metabox', '📦 ZR Express', 'ayra_zr_render_metabox', wc_get_page_screen_id('shop-order'), 'side', 'high');
    }
}
add_action('add_meta_boxes', 'ayra_zr_add_order_metabox');

function ayra_zr_render_metabox($post_or_order)
{
    $order_id = is_a($post_or_order, 'WC_Order')
        ? $post_or_order->get_id()
        : (is_object($post_or_order) ? $post_or_order->ID : 0);
    if (!$order_id)
        return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $status = $order->get_status();
    $pushed = get_post_meta($order_id, '_zr_pushed', true);
    $tracking = get_post_meta($order_id, '_zr_tracking_number', true);
    $parcel_id = get_post_meta($order_id, '_zr_parcel_id', true);
    $error = get_post_meta($order_id, '_zr_push_error', true);
    $nonce = wp_create_nonce('ayra_zr_order_' . $order_id);

    echo '<div id="ayra-zr-box" style="font-size:13px;">';

    if ($pushed) {
        // ── Already sent to ZR Express ──
        echo '<p style="color:#16a34a;font-weight:600;">✅ تم الإرسال إلى ZR Express</p>';
        if ($tracking)
            echo '<p><strong>التتبع:</strong><br><code>' . esc_html($tracking) . '</code></p>';
        if ($parcel_id)
            echo '<p><strong>Parcel ID:</strong><br><code style="font-size:11px;word-break:break-all;">' . esc_html($parcel_id) . '</code></p>';
        echo '<div style="margin-top:8px;display:flex;flex-direction:column;gap:6px;">';
        echo '<button type="button" class="button ayra-zr-action" data-action="resend" data-order="' . $order_id . '" data-nonce="' . $nonce . '">🔁 إعادة الإرسال</button>';
        echo '</div>';

    } elseif ($status === 'completed') {
        // ── Order is completed but NOT yet sent ──
        if ($error)
            echo '<p style="color:#dc2626;">❌ ' . esc_html($error) . '</p>';

        echo '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px;text-align:center;">';
        echo '<p style="margin:0 0 10px;font-weight:600;color:#166534;">✓ الطلب مكتمل — جاهز للإرسال</p>';
        echo '<button type="button" class="button button-primary ayra-zr-action" data-action="push" data-order="' . $order_id . '" data-nonce="' . $nonce . '" '
            . 'style="background:#6366f1;border-color:#4f46e5;font-size:14px;padding:6px 20px;width:100%;">'
            . '📤 إرسال إلى ZR Express</button>';
        echo '</div>';

    } else {
        // ── Order is not completed yet ──
        $status_labels = [
            'pending'    => 'قيد الانتظار',
            'processing' => 'قيد المعالجة',
            'on-hold'    => 'معلّق',
            'cancelled'  => 'ملغى',
            'refunded'   => 'مسترجع',
            'failed'     => 'فاشل',
        ];
        $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;

        echo '<div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px;text-align:center;">';
        echo '<p style="margin:0 0 4px;font-weight:600;color:#854d0e;">⏳ الحالة: ' . esc_html($label) . '</p>';
        echo '<p style="margin:0;font-size:12px;color:#92400e;">يجب تغيير حالة الطلب إلى <strong>"مكتمل"</strong> قبل الإرسال إلى ZR Express.</p>';
        echo '</div>';

        if ($error) {
            echo '<p style="color:#dc2626;margin-top:8px;font-size:12px;">❌ خطأ سابق: ' . esc_html($error) . '</p>';
        }
    }

    echo '<div id="ayra-zr-status-result" style="margin-top:8px;"></div>';
    echo '</div>';
    ?>
    <script>
        jQuery(function ($) {
            $('.ayra-zr-action').on('click', function () {
                var $b = $(this), $r = $('#ayra-zr-status-result');
                $b.prop('disabled', true); $r.text('جاري الإرسال...');
                $.post(ajaxurl, { action: 'ayra_zr_order_action', zr_action: $b.data('action'), order_id: $b.data('order'), _wpnonce: $b.data('nonce') }, function (r) {
                    $b.prop('disabled', false);
                    if (r.success) { $r.html('<span style="color:#16a34a">' + r.data.message + '</span>'); if (r.data.reload) location.reload(); }
                    else $r.html('<span style="color:#dc2626">❌ ' + (r.data || 'خطأ') + '</span>');
                }).fail(function () { $b.prop('disabled', false); $r.text('❌ خطأ'); });
            });
        });
    </script>
    <?php
}

/* ═══════════════════════════════════════════════════════════
   5. AJAX — ORDER ACTIONS (Push / Resend)
   ═══════════════════════════════════════════════════════════ */

function ayra_zr_ajax_order_action()
{
    $order_id = intval($_POST['order_id'] ?? 0);
    $zr_action = sanitize_text_field($_POST['zr_action'] ?? '');
    check_ajax_referer('ayra_zr_order_' . $order_id, '_wpnonce');
    if (!current_user_can('manage_woocommerce') || !$order_id)
        wp_send_json_error('Unauthorized');

    $order = wc_get_order($order_id);
    if (!$order)
        wp_send_json_error('Order not found');

    // Only allow push for completed orders
    if ($order->get_status() !== 'completed' && $zr_action === 'push') {
        wp_send_json_error('الطلب يجب أن يكون بحالة "مكتمل" قبل الإرسال');
    }

    if ($zr_action === 'push' || $zr_action === 'resend') {
        delete_post_meta($order_id, '_zr_pushed');
        delete_post_meta($order_id, '_zr_push_error');

        $api = new Ayra_ZR_Express_API();
        $result = $api->create_parcel_from_order($order);

        if (is_wp_error($result)) {
            $err_msg = $result->get_error_message();
            $err_data = $result->get_error_data();
            $full_error = $err_msg;
            if (!empty($err_data['raw'])) {
                $full_error .= ' | Raw: ' . $err_data['raw'];
            }
            update_post_meta($order_id, '_zr_push_error', $full_error);
            $order->add_order_note('⚠️ ZR Express: فشل — ' . $full_error);
            wp_send_json_error($err_msg);
        }

        $parcel_id = $result['id'] ?? $result['parcelId'] ?? $result['Tracking'] ?? '';
        $tracking = $result['trackingNumber'] ?? $result['tracking'] ?? $result['Tracking'] ?? $parcel_id;

        update_post_meta($order_id, '_zr_pushed', 1);
        update_post_meta($order_id, '_zr_parcel_id', $parcel_id);
        update_post_meta($order_id, '_zr_tracking_number', $tracking);
        update_post_meta($order_id, '_zr_push_response', $result);
        delete_post_meta($order_id, '_zr_push_error');

        $order->add_order_note('📦 ZR Express: تم الإرسال' . ($tracking ? ' — ' . $tracking : ''));
        wp_send_json_success(['message' => 'تم الإرسال بنجاح ✅', 'reload' => true]);
    }

    wp_send_json_error('Unknown action');
}
add_action('wp_ajax_ayra_zr_order_action', 'ayra_zr_ajax_order_action');

/* ═══════════════════════════════════════════════════════════
   6. BULK ACTION — Transfer completed orders to ZR Express
   ═══════════════════════════════════════════════════════════ */

// Add bulk action to orders list
function ayra_zr_bulk_action_options($actions)
{
    $actions['zr_express_push'] = '📤 إرسال إلى ZR Express';
    return $actions;
}
add_filter('bulk_actions-edit-shop_order', 'ayra_zr_bulk_action_options');
// HPOS compatibility
add_filter('bulk_actions-woocommerce_page_wc-orders', 'ayra_zr_bulk_action_options');

// Handle bulk action
function ayra_zr_handle_bulk_action($redirect_to, $action, $order_ids)
{
    if ($action !== 'zr_express_push') return $redirect_to;

    $pushed = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) { $skipped++; continue; }

        // Only push completed orders
        if ($order->get_status() !== 'completed') {
            $skipped++;
            continue;
        }

        // Skip if already pushed
        if (get_post_meta($order_id, '_zr_pushed', true)) {
            $skipped++;
            continue;
        }

        // Check API credentials
        if (!get_option('ayra_zr_tenant_id') || !get_option('ayra_zr_api_key')) {
            $failed++;
            continue;
        }

        $api = new Ayra_ZR_Express_API();
        $result = $api->create_parcel_from_order($order);

        if (is_wp_error($result)) {
            $err_msg = $result->get_error_message();
            $err_data = $result->get_error_data();
            $full_error = $err_msg;
            if (!empty($err_data['raw'])) {
                $full_error .= ' | Raw: ' . $err_data['raw'];
            }
            $order->add_order_note('⚠️ ZR Express: فشل — ' . $full_error);
            update_post_meta($order_id, '_zr_push_error', $full_error);
            $failed++;
            continue;
        }

        $parcel_id = $result['id'] ?? $result['parcelId'] ?? $result['Tracking'] ?? '';
        $tracking = $result['trackingNumber'] ?? $result['tracking'] ?? $result['Tracking'] ?? $parcel_id;

        update_post_meta($order_id, '_zr_pushed', 1);
        update_post_meta($order_id, '_zr_parcel_id', $parcel_id);
        update_post_meta($order_id, '_zr_tracking_number', $tracking);
        update_post_meta($order_id, '_zr_push_response', $result);
        delete_post_meta($order_id, '_zr_push_error');

        $order->add_order_note('📦 ZR Express: تم الإرسال' . ($tracking ? ' — ' . $tracking : ''));
        $pushed++;
    }

    $redirect_to = add_query_arg([
        'zr_pushed'  => $pushed,
        'zr_skipped' => $skipped,
        'zr_failed'  => $failed,
    ], $redirect_to);

    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-shop_order', 'ayra_zr_handle_bulk_action', 10, 3);
// HPOS compatibility
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', 'ayra_zr_handle_bulk_action', 10, 3);

// Show admin notice after bulk action
function ayra_zr_bulk_action_notice()
{
    if (empty($_REQUEST['zr_pushed']) && empty($_REQUEST['zr_skipped']) && empty($_REQUEST['zr_failed'])) return;

    $pushed  = intval($_REQUEST['zr_pushed'] ?? 0);
    $skipped = intval($_REQUEST['zr_skipped'] ?? 0);
    $failed  = intval($_REQUEST['zr_failed'] ?? 0);

    $messages = [];
    if ($pushed > 0) $messages[] = '✅ تم إرسال <strong>' . $pushed . '</strong> طلب';
    if ($skipped > 0) $messages[] = '⏭️ تم تخطي <strong>' . $skipped . '</strong> طلب (غير مكتمل أو مُرسل سابقاً)';
    if ($failed > 0) $messages[] = '❌ فشل إرسال <strong>' . $failed . '</strong> طلب';

    $type = ($failed > 0 && $pushed === 0) ? 'error' : ($pushed > 0 ? 'success' : 'warning');

    echo '<div class="notice notice-' . $type . ' is-dismissible"><p>📦 ZR Express: ' . implode(' | ', $messages) . '</p></div>';
}
add_action('admin_notices', 'ayra_zr_bulk_action_notice');

/* ═══════════════════════════════════════════════════════════
   7. ZR STATUS COLUMN in Orders List
   ═══════════════════════════════════════════════════════════ */

// Add column
function ayra_zr_orders_column($columns)
{
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'order_status') {
            $new['zr_express'] = 'ZR Express';
        }
    }
    return $new;
}
add_filter('manage_edit-shop_order_columns', 'ayra_zr_orders_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'ayra_zr_orders_column');

// Render column content
function ayra_zr_orders_column_content($column, $post_id_or_order)
{
    if ($column !== 'zr_express') return;

    $order_id = is_a($post_id_or_order, 'WC_Order') ? $post_id_or_order->get_id() : $post_id_or_order;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $pushed = get_post_meta($order_id, '_zr_pushed', true);
    $error = get_post_meta($order_id, '_zr_push_error', true);

    if ($pushed) {
        $tracking = get_post_meta($order_id, '_zr_tracking_number', true);
        echo '<span style="color:#16a34a;font-weight:600;" title="' . esc_attr($tracking) . '">✅ مُرسل</span>';
    } elseif ($order->get_status() === 'completed') {
        echo '<span style="color:#d97706;font-weight:600;">🟡 جاهز</span>';
    } elseif ($error) {
        echo '<span style="color:#dc2626;" title="' . esc_attr($error) . '">❌ فشل</span>';
    } else {
        echo '<span style="color:#9ca3af;">—</span>';
    }
}
add_action('manage_shop_order_posts_custom_column', 'ayra_zr_orders_column_content', 10, 2);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'ayra_zr_orders_column_content', 10, 2);

// ─── AJAX: Sync Territories ──────────────────────────────────────────
function ayra_zr_ajax_sync_territories() {
    check_ajax_referer('ayra_zr_admin', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('غير مصرح لك');

    if (function_exists('set_time_limit')) {
        @set_time_limit(120);
    }

    try {
        $api = new Ayra_ZR_Express_API();

        // 1. Fetch Wilayas
        $wilayas_res = $api->request('POST', '/territories/search', [
            'advancedFilter' => ['logic' => 'and', 'filters' => [['field' => 'level', 'operator' => 'eq', 'value' => 'Wilaya']]],
            'pageNumber' => 1, 'pageSize' => 200
        ]);
        if (is_wp_error($wilayas_res)) wp_send_json_error('فشل في جلب الولايات: ' . $wilayas_res->get_error_message());
        $api_wilayas = $wilayas_res['items'] ?? $wilayas_res['data'] ?? [];

        // 2. Fetch Communes (Pagination required)
        $api_communes = [];
        $page = 1;
        while (true) {
            $communes_res = $api->request('POST', '/territories/search', [
                'advancedFilter' => ['logic' => 'and', 'filters' => [['field' => 'level', 'operator' => 'eq', 'value' => 'Commune']]],
                'pageNumber' => $page, 'pageSize' => 1000
            ]);
            if (is_wp_error($communes_res)) {
                if ($page === 1) wp_send_json_error('فشل في جلب البلديات: ' . $communes_res->get_error_message());
                break;
            }
            $items = $communes_res['items'] ?? $communes_res['data'] ?? [];
            if (empty($items)) break;
            
            $api_communes = array_merge($api_communes, $items);
            if (count($items) < 1000) break; // Reached last page
            $page++;
        }

        // 3. Fetch Hubs (Desks)
        $hubs_res = $api->request('POST', '/hubs/search', [
            'pageNumber' => 1, 'pageSize' => 500
        ]);
        if (is_wp_error($hubs_res)) wp_send_json_error('فشل في جلب المكاتب: ' . $hubs_res->get_error_message());
        $api_hubs = $hubs_res['items'] ?? $hubs_res['data'] ?? [];

        // Organize Wilayas & Communes FIRST (we need this for hub cross-referencing)
        $processed = [];
        $static_data = function_exists('ayra_get_wilayas_data_static') ? ayra_get_wilayas_data_static() : (function_exists('ayra_get_wilayas_data') ? ayra_get_wilayas_data() : []);

        foreach ($api_wilayas as $w) {
            $code = str_pad($w['code'] ?? '', 2, '0', STR_PAD_LEFT);
            $w_name = (!empty($w['nameArabic']) ? trim($w['nameArabic']) . ' - ' : '') . trim($w['name'] ?? '');
            $w_id = $w['id'] ?? '';
            if ($w_id) {
                $processed[$w_id] = [
                    'id' => $w_id,
                    'code' => $code,
                    'name' => $w_name,
                    'communes' => [],
                    'communes_map' => []
                ];
            }
        }

        // Build a reverse lookup: commune_id → wilaya_id
        $commune_to_wilaya = [];
        foreach ($api_communes as $c) {
            $pid = $c['parentId'] ?? '';
            $c_id = $c['id'] ?? '';
            if ($pid && $c_id && isset($processed[$pid])) {
                $c_name = trim(!empty($c['nameArabic']) ? $c['nameArabic'] : ($c['name'] ?? ''));
                if ($c_name) {
                    $processed[$pid]['communes'][] = $c_name;
                    $processed[$pid]['communes_map'][$c_name] = $c_id;
                    $commune_to_wilaya[$c_id] = [
                        'wilaya_id' => $pid,
                        'wilaya_code' => $processed[$pid]['code'],
                        'commune_name' => $c_name,
                    ];
                }
            }
        }

        // Now process Hubs - build structured desks_by_wilaya
        $hubs_map = []; // Maps district_id → hub_id (for parcel creation)
        $desks_by_wilaya = []; // Maps wilaya_code → array of desk objects
        $total_desks = 0;

        foreach ($api_hubs as $hub) {
            if (empty($hub['isPickupPoint'])) continue;

            $hub_id = $hub['id'] ?? '';
            $dist_id = $hub['address']['districtTerritoryId'] ?? null;
            $hub_name = trim($hub['name'] ?? '');
            $hub_address = trim($hub['address']['address'] ?? '');
            $hub_district = trim($hub['address']['district'] ?? '');

            if (!$hub_id || !$dist_id) continue;

            // Map district_id → hub_id for parcel creation
            $hubs_map[$dist_id] = $hub_id;

            // Cross-reference: find which wilaya this desk belongs to
            if (isset($commune_to_wilaya[$dist_id])) {
                $w_code = $commune_to_wilaya[$dist_id]['wilaya_code'];
                $commune_name = $commune_to_wilaya[$dist_id]['commune_name'];
            } else {
                // Fallback: try matching by district name against all communes
                $found = false;
                $hub_dist_lower = mb_strtolower($hub_district);
                foreach ($processed as $w_id => $w_data) {
                    foreach ($w_data['communes_map'] as $c_name => $c_id) {
                        $c_lower = mb_strtolower($c_name);
                        if ($c_lower === $hub_dist_lower || strpos($c_lower, $hub_dist_lower) !== false || strpos($hub_dist_lower, $c_lower) !== false) {
                            $w_code = $w_data['code'];
                            $commune_name = $c_name;
                            $found = true;
                            break 2;
                        }
                    }
                }
                if (!$found) continue; // Skip desk if we can't map it to a wilaya
            }

            if (!isset($desks_by_wilaya[$w_code])) {
                $desks_by_wilaya[$w_code] = [];
            }

            $desks_by_wilaya[$w_code][] = [
                'commune' => $commune_name,
                'commune_id' => $dist_id,
                'hub_id' => $hub_id,
                'name' => $hub_name,
                'address' => $hub_address,
            ];
            $total_desks++;
        }

        // Sort desks within each wilaya by commune name
        foreach ($desks_by_wilaya as &$desks) {
            usort($desks, function($a, $b) { return strcmp($a['commune'], $b['commune']); });
        }
        unset($desks);
        ksort($desks_by_wilaya);

        update_option('ayra_zr_hubs_map', $hubs_map, false);
        update_option('ayra_zr_desks_by_wilaya', $desks_by_wilaya, false);

        // Convert to Wilaya Code array format matching `ayra_get_wilayas_data`
        $final_data = [];
        foreach ($processed as $w) {
            $code = $w['code'];
            $communes_unique = array_unique($w['communes']);
            sort($communes_unique);
            $final_data[$code] = [
                'id' => $w['id'],
                'name' => $w['name'],
                'communes' => array_values($communes_unique),
                'communes_map' => $w['communes_map'],
                'zone' => isset($static_data[$code]) ? $static_data[$code]['zone'] : 'north'
            ];
        }

        ksort($final_data);
        update_option('ayra_zr_api_territories', $final_data, false);

        // Build summary of desks per wilaya for the success message
        $desk_summary = [];
        foreach ($desks_by_wilaya as $wc => $d_list) {
            $desk_summary[] = $wc . ': ' . count($d_list) . ' مكتب';
        }

        wp_send_json_success(['message' => 'تمت المزامنة بنجاح! ✅' . "\n"
            . count($final_data) . ' ولاية | ' . count($api_communes) . ' بلدية | '
            . $total_desks . ' مكتب StopDesk في ' . count($desks_by_wilaya) . ' ولاية' . "\n"
            . 'التفاصيل: ' . implode(' | ', $desk_summary)
        ]);
    } catch (\Exception $e) {
        wp_send_json_error('حدث خطأ أثناء المزامنة: ' . $e->getMessage());
    }
}
add_action('wp_ajax_ayra_zr_sync_territories', 'ayra_zr_ajax_sync_territories');
