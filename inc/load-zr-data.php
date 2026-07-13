<?php
/**
 * Load ZR Express data from JSON files and update WordPress options
 *
 * This script loads the delivery data from the JSON files and updates
 * the WordPress options that the checkout system uses.
 */

define('ABSPATH') || exit;

class Ayra_Load_ZR_Data
{
    private $data_dir;

    public function __construct()
    {
        $this->data_dir = plugin_dir_path(__FILE__) . 'zr-data/';
    }

    public function load_all_data()
    {
        // Load wilayas data
        $wilayas = $this->load_wilayas();
        if ($wilayas) {
            update_option('ayra_zr_api_territories', $wilayas, false);
            error_log('ayra_zr_api_territories updated with ' . count($wilayas) . ' wilayas');
        }

        // Load communes data
        $communes = $this->load_communes();
        if ($communes) {
            update_option('ayra_zr_desk_communes_static', $communes, false);
            error_log('ayra_zr_desk_communes_static updated with ' . count($communes) . ' communes');
        }

        // Load pickup desks data
        $desks = $this->load_pickup_desks();
        if ($desks) {
            update_option('ayra_zr_desks_by_wilaya', $desks, false);
            error_log('ayra_zr_desks_by_wilaya updated with data for ' . count($desks) . ' wilayas');
        }
    }

    private function load_wilayas()
    {
        $file = $this->data_dir . 'zr_wilayas.json';
        if (!file_exists($file)) {
            error_log('Wilayas JSON file not found: ' . $file);
            return false;
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON error in zr_wilayas.json: ' . json_last_error_msg());
            return false;
        }

        $wilayas = [];
        foreach ($json as $wilaya) {
            $code_padded = str_pad($wilaya['code'], 2, '0', STR_PAD_LEFT);

            $wilayas[$code_padded] = [
                'id' => $wilaya['id'],
                'name' => $wilaya['name'] . ' - ' . $wilaya['name'],
                'has_home_delivery' => $wilaya['has_home_delivery'],
                'has_pickup_point' => $wilaya['has_pickup_point'],
                'can_send' => $wilaya['can_send'],
                'price_home' => $wilaya['price_home'],
                'price_stopdesk' => $wilaya['price_stopdesk'],
                'price_return' => $wilaya['price_return'],
                'communes' => [] // Will be populated from zr_communes.json
            ];
        }

        return $wilayas;
    }

    private function load_communes()
    {
        $file = $this->data_dir . 'zr_communes.json';
        if (!file_exists($file)) {
            error_log('Communes JSON file not found: ' . $file);
            return false;
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON error in zr_communes.json: ' . json_last_error_msg());
            return false;
        }

        $communes = [];
        foreach ($json as $commune) {
            $code_padded = str_pad($commune['wilaya_code'], 2, '0', STR_PAD_LEFT);
            $communes[] = mb_strtolower(trim($commune['name']));
        }

        // Remove duplicates
        $communes = array_unique($communes);

        return array_values($communes);
    }

    private function load_pickup_desks()
    {
        $file = $this->data_dir . 'zr_pickup_desks.json';
        if (!file_exists($file)) {
            error_log('Pickup desks JSON file not found: ' . $file);
            return false;
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON error in zr_pickup_desks.json: ' . json_last_error_msg());
            return false;
        }

        $desks_by_wilaya = [];
        foreach ($json as $desk) {
            // Extract wilaya code from the city_territory_id
            // First, find the corresponding wilaya from zr_wilayas.json
            $wilayas_file = $this->data_dir . 'zr_wilayas.json';
            $wilayas_content = file_get_contents($wilayas_file);
            $wilayas_json = json_decode($wilayas_content, true);

            foreach ($wilayas_json as $wilaya) {
                if ($wilaya['id'] === $desk['city_territory_id']) {
                    $code_padded = str_pad($wilaya['code'], 2, '0', STR_PAD_LEFT);

                    if (!isset($desks_by_wilaya[$code_padded])) {
                        $desks_by_wilaya[$code_padded] = [];
                    }

                    $desks_by_wilaya[$code_padded][] = [
                        'id' => $desk['id'],
                        'name' => $desk['name'],
                        'commune' => $desk['district'],
                        'address' => $desk['street'],
                        'city' => $desk['city'],
                        'postal_code' => $desk['postal_code'],
                        'phone1' => $desk['phone1'],
                        'phone2' => $desk['phone2'],
                        'opening_hours' => $desk['opening_hours']
                    ];

                    break;
                }
            }
        }

        return $desks_by_wilaya;
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $loader = new Ayra_Load_ZR_Data();
    $loader->load_all_data();
    echo 'ZR Express data loaded from JSON files and updated in WordPress options.';
}
?>