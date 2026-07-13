<?php
/**
 * AYRA Homewear - ZR Express API Client
 *
 * ZR Express API (api.zrexpress.app/api/v1)
 * Auth: X-Tenant + X-Api-Key headers
 *
 * @package AyraHomewear
 */
defined('ABSPATH') || exit;

class Ayra_ZR_Express_API
{
    private $tenant_id;
    private $api_key;
    private $base_url = 'https://api.zrexpress.app/api/v1';
    private $timeout = 30;

    public function __construct($tenant_id = null, $api_key = null)
    {
        $this->tenant_id = $tenant_id ?: get_option('ayra_zr_tenant_id', '');
        $this->api_key = $api_key ?: get_option('ayra_zr_api_key', '');
    }

    /**
     * Test credentials via GET /users/profile
     */
    public function test_credentials()
    {
        if (empty($this->tenant_id) || empty($this->api_key)) {
            return 'Tenant ID أو API Key فارغ';
        }
        $result = $this->request('GET', '/users/profile');
        if (is_wp_error($result)) {
            return $result->get_error_message();
        }
        return true;
    }

    /**
     * Create a parcel from a WooCommerce order.
     * 
     * Flow: 1) Find/create customer → 2) Resolve territories → 3) POST /parcels
     *
     * @param WC_Order $order
     * @return array|WP_Error
     */
    public function create_parcel_from_order($order)
    {
        $order_id = $order->get_id();

        // ── Gather order data ──
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $client_name = trim($first_name . ' ' . $last_name);
        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();
        $address = $order->get_billing_address_1();
        $commune = get_post_meta($order_id, '_billing_commune', true) ?: $order->get_billing_city();
        $wilaya_code = get_post_meta($order_id, '_billing_wilaya', true);
        $dt_raw = get_post_meta($order_id, '_billing_delivery_type', true);
        $total = floatval($order->get_total());
        $note = $order->get_customer_note();

        // ── Format phone to international (+213) ──
        $intl_phone = $this->format_phone_international($phone);

        // ── Products ──
        $products = [];
        $product_desc = [];
        foreach ($order->get_items() as $item) {
            $qty = $item->get_quantity();
            $products[] = [
                'productName' => $item->get_name(),
                'quantity' => $qty,
                'unitPrice' => floatval($item->get_total() / max($qty, 1)),
                'stockType' => 'none',
            ];
            $product_desc[] = $item->get_name() . ($qty > 1 ? ' x' . $qty : '');
        }

        // ── Delivery type ──
        $deliveryType = 'home';
        if ($dt_raw === 'desk' || $dt_raw === 'to_desk' || $dt_raw === 'stopdesk') {
            $deliveryType = 'pickup-point';
        }

        // ── Resolve territory IDs ──
        // For desk orders, use the stored hub data directly (saved at checkout)
        $stored_hub_id = get_post_meta($order_id, '_billing_hub_id', true);
        $stored_district_id = get_post_meta($order_id, '_billing_desk_district_id', true);
        $stored_city_id = get_post_meta($order_id, '_billing_desk_city_id', true);

        $this->log("Order #{$order_id} desk debug: type={$deliveryType}, hub={$stored_hub_id}, district={$stored_district_id}, city={$stored_city_id}, wilaya={$wilaya_code}, commune={$commune}");

        if ($deliveryType === 'pickup-point' && $stored_hub_id && $stored_district_id) {
            // Use stored data directly — no need to resolve territories
            $city_id = $stored_city_id ?: null;
            $district_id = $stored_district_id;

            // If city_id wasn't stored, resolve ONLY the wilaya (city) level
            // Don't use commune name here — for desk orders it's the district name
            // which won't match ZR's commune naming
            if (empty($city_id)) {
                $territory = $this->resolve_territories($wilaya_code, '');
                $city_id = $territory['city_id'] ?? null;
            }
        } elseif ($deliveryType === 'pickup-point') {
            // Desk order but NO stored hub data — try to resolve and find hub via fallback
            $territory = $this->resolve_territories($wilaya_code, $commune);
            $city_id = $territory['city_id'] ?? null;
            $district_id = $territory['district_id'] ?? null;

            // Try to find hub from hubs_map
            if (empty($stored_hub_id) && $district_id) {
                $hubs_map = get_option('ayra_zr_hubs_map', []);
                if (!empty($hubs_map[$district_id])) {
                    $stored_hub_id = $hubs_map[$district_id];
                }
            }
        } else {
            // Home delivery — resolve normally
            $territory = $this->resolve_territories($wilaya_code, $commune);
            $city_id = $territory['city_id'] ?? null;
            $district_id = $territory['district_id'] ?? null;
        }

        if (empty($city_id)) {
            return new \WP_Error('zr_territory_error', "تعذر تحديد الولاية ({$wilaya_code}). يرجى التحقق من العنوان واختيار ولاية صحيحة.");
        }

        // ── Full address ──
        $full_address = $address ?: $commune;

        // ── Find or Create Customer ──
        $customer_id = $this->find_or_create_customer($client_name, $intl_phone, $email, $full_address);
        if (is_wp_error($customer_id)) {
            return $customer_id;
        }

        // ── Description formatting for ZR Express (2 - 250 characters) ──
        $description = $note ?: implode(', ', $product_desc);
        if (mb_strlen($description) > 250) {
            $description = mb_substr($description, 0, 247) . '...';
        }
        if (mb_strlen($description) < 2) {
            $description = 'AYRA';
        }

        // ── Build payload (customer inline, no separate customer API) ──
        $payload = [
            'customer' => [
                'customerId' => $customer_id,
                'name' => $client_name,
                'phone' => [
                    'number1' => $intl_phone,
                    'number2' => '',
                ],
            ],
            'deliveryAddress' => [
                'address' => $full_address,
                'cityTerritoryId' => $city_id,
                'districtTerritoryId' => $district_id,
            ],
            'deliveryType' => $deliveryType,
            'orderedProducts' => $products,
            'description' => $description,
            'amount' => $total,
            'externalId' => 'AYRA-' . $order_id,
        ];

        if ($deliveryType === 'pickup-point') {
            if ($stored_hub_id) {
                $hub_id_final = $stored_hub_id;
            } else {
                // Fallback: try hubs_map (legacy)
                $hubs_map = get_option('ayra_zr_hubs_map', []);
                $hub_id_final = $hubs_map[$district_id] ?? null;
            }

            if ($hub_id_final) {
                // ZR Express API expects hubId at TOP LEVEL for pickup-point
                $payload['hubId'] = $hub_id_final;
                // Also include in deliveryAddress for compatibility
                $payload['deliveryAddress']['hubId'] = $hub_id_final;
            } else {
                return new \WP_Error('zr_hub_error', "لا يوجد مكتب StopDesk متاح في هذه البلدية.");
            }
        }

        $this->log('Payload for order #' . $order_id . ': ' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE));

        // ── Create parcel ──
        $result = $this->request('POST', '/parcels', $payload);

        if (is_wp_error($result)) {
            $this->log('Failed order #' . $order_id . ': ' . $result->get_error_message(), 'error');
        } else {
            $this->log('Success order #' . $order_id . ': ' . wp_json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * Find or create a customer in ZR Express.
     * POST /customers/search then POST /customers/individual if not found.
     */
    public function find_or_create_customer($name, $phone, $email, $address)
    {
        // Try to find existing customer by phone
        $search = $this->request('POST', '/customers/search', [
            'advancedFilter' => [
                'logic' => 'and',
                'filters' => [
                    ['field' => 'phoneNumber', 'operator' => 'contains', 'value' => $phone],
                ],
            ],
            'pageNumber' => 1,
            'pageSize' => 1,
        ]);

        // If found, return the ID
        if (!is_wp_error($search)) {
            $items = $search['items'] ?? $search['data'] ?? [];
            if (!empty($items) && !empty($items[0]['id'])) {
                $this->log('Found existing customer: ' . $items[0]['id']);
                return $items[0]['id'];
            }
        }

        // Create new customer
        $this->log('Creating new customer: ' . $name . ' / ' . $phone);
        $create = $this->request('POST', '/customers/individual', [
            'name' => $name,
            'phone' => [
                'number1' => $phone,
                'number2' => '',
            ],
        ]);

        if (is_wp_error($create)) {
            return new \WP_Error('zr_customer_error', 'فشل إنشاء العميل: ' . $create->get_error_message());
        }

        $cid = $create['id'] ?? $create['customerId'] ?? '';
        if (empty($cid)) {
            return new \WP_Error('zr_customer_error', 'لم يتم الحصول على ID العميل: ' . wp_json_encode($create));
        }

        return $cid;
    }

    /**
     * Resolve wilaya code + commune name → territory UUIDs.
     * Uses POST /territories/search
     */
    public function resolve_territories($wilaya_code, $commune_name)
    {
        $result = ['city_id' => null, 'district_id' => null];
        $wilaya_int = intval($wilaya_code);
        $code_padded = str_pad($wilaya_int, 2, '0', STR_PAD_LEFT);
        $search_name = mb_strtolower(trim($commune_name));

        // New wilayas fallback mapping
        $fallback_map = [
            '49' => '39',
            '50' => '47',
            '51' => '07',
            '52' => '01',
            '53' => '08',
            '54' => '01',
            '55' => '30',
            '56' => '33',
            '57' => '11',
            '58' => '11'
        ];

        // 1. Check cache first
        $cache = get_option('ayra_zr_territory_cache', []);
        $cache_key = $wilaya_int . '_' . sanitize_title($commune_name);
        if (!empty($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        // 2. Check Synced Territories (Direct or Fallback)
        $synced = get_option('ayra_zr_api_territories', []);
        $codes_to_check = [$code_padded];
        if (isset($fallback_map[$code_padded])) {
            $codes_to_check[] = str_pad($fallback_map[$code_padded], 2, '0', STR_PAD_LEFT);
        }

        foreach ($codes_to_check as $check_code) {
            if (!empty($synced[$check_code]) && !empty($synced[$check_code]['id'])) {
                $result['city_id'] = $synced[$check_code]['id'];

                // Attempt commune lookup from synced map
                if (!empty($synced[$check_code]['communes_map'])) {
                    foreach ($synced[$check_code]['communes_map'] as $c_name => $c_id) {
                        $c_name_lower = mb_strtolower(trim($c_name));
                        if ($c_name_lower === $search_name || strpos($c_name_lower, $search_name) !== false || strpos($search_name, $c_name_lower) !== false) {
                            $result['district_id'] = $c_id;
                            $cache[$cache_key] = $result;
                            update_option('ayra_zr_territory_cache', $cache, false);
                            return $result;
                        }
                    }
                }
                // If wilaya found but commune not matched in synced data, keep city_id and continue to API search for commune
                if ($result['city_id'])
                    break;
            }
        }

        // 3. API Search for Wilaya if not found in synced
        if (empty($result['city_id'])) {
            $search_codes = $codes_to_check;
            foreach ($search_codes as $s_code) {
                $wilaya_search = $this->request('POST', '/territories/search', [
                    'advancedFilter' => [
                        'logic' => 'and',
                        'filters' => [
                            ['field' => 'level', 'operator' => 'eq', 'value' => 'Wilaya'],
                            ['field' => 'code', 'operator' => 'eq', 'value' => intval($s_code)],
                        ],
                    ],
                    'pageNumber' => 1,
                    'pageSize' => 1,
                ]);

                if (!is_wp_error($wilaya_search)) {
                    $items = $wilaya_search['items'] ?? $wilaya_search['data'] ?? $wilaya_search;
                    if (!empty($items[0]['id'])) {
                        $result['city_id'] = $items[0]['id'];
                        break;
                    }
                }
            }
        }

        // 4. API Search for Commune if Wilaya found
        if ($result['city_id']) {
            $wilaya_id = $result['city_id'];
            $commune_search = $this->request('POST', '/territories/search', [
                'advancedFilter' => [
                    'logic' => 'and',
                    'filters' => [
                        ['field' => 'level', 'operator' => 'eq', 'value' => 'Commune'],
                        ['field' => 'parentId', 'operator' => 'eq', 'value' => $wilaya_id],
                    ],
                ],
                'pageNumber' => 1,
                'pageSize' => 100,
            ]);

            if (!is_wp_error($commune_search)) {
                $communes = $commune_search['items'] ?? $commune_search['data'] ?? $commune_search;
                if (is_array($communes)) {
                    foreach ($communes as $c) {
                        $c_name = mb_strtolower(trim($c['name'] ?? ''));
                        if ($c_name === $search_name || strpos($c_name, $search_name) !== false || strpos($search_name, $c_name) !== false) {
                            $result['district_id'] = $c['id'];
                            break;
                        }
                    }
                    // Final fallback: use first commune if no match
                    if (!$result['district_id'] && !empty($communes[0]['id'])) {
                        $result['district_id'] = $communes[0]['id'];
                    }
                }
            }
        }

        // Cache the final result if we found at least the city
        if ($result['city_id']) {
            $cache[$cache_key] = $result;
            update_option('ayra_zr_territory_cache', $cache, false);
        }

        return $result;
    }

    /**
     * Format phone number to international format (+213...)
     */
    private function format_phone_international($phone)
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Already has country code
        if (substr($clean, 0, 3) === '213') {
            return '+' . $clean;
        }

        // Starts with 0 — replace with +213
        if (substr($clean, 0, 1) === '0') {
            return '+213' . substr($clean, 1);
        }

        // Just digits — assume Algerian
        return '+213' . $clean;
    }

    /* ── HTTP Layer ──────────────────────────────────────── */

    public function request($method, $endpoint, $body = [])
    {
        $url = $this->base_url . $endpoint;
        $args = [
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Tenant' => $this->tenant_id,
                'X-Api-Key' => $this->api_key,
            ],
        ];

        if ($method === 'POST' || $method === 'PATCH') {
            $args['method'] = $method;
            $args['body'] = wp_json_encode($body, JSON_UNESCAPED_UNICODE);
            $response = wp_remote_request($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            $this->log('HTTP error: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        $this->log("[{$method} {$endpoint}] HTTP {$code}: " . substr($raw, 0, 800));

        if ($code < 200 || $code >= 300) {
            $msg = 'HTTP ' . $code;
            if (is_array($data)) {
                // Extract validation errors
                if (!empty($data['errors']) && is_array($data['errors'])) {
                    $errs = [];
                    foreach ($data['errors'] as $e) {
                        $errs[] = $e['description'] ?? $e['message'] ?? wp_json_encode($e);
                    }
                    $msg .= ': ' . implode(' | ', $errs);
                } elseif (!empty($data['message'])) {
                    $msg .= ': ' . $data['message'];
                } elseif (!empty($data['title'])) {
                    $msg .= ': ' . $data['title'];
                } else {
                    $msg .= ': ' . substr($raw, 0, 300);
                }
            } else {
                $msg .= ': ' . substr($raw, 0, 300);
            }
            return new \WP_Error('zr_api_error', $msg, ['status' => $code, 'raw' => substr($raw, 0, 2000)]);
        }

        return is_array($data) ? $data : [];
    }

    private function log($message, $level = 'info')
    {
        error_log("[AYRA ZR Express][{$level}] {$message}");
    }
}








