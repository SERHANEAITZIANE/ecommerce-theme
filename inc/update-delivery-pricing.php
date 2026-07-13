<?php
/**
 * Update delivery pricing from zr_pricing.json file
 *
 * This script loads the pricing data from the zr_pricing.json file
 * and updates the dynamic pricing in the system.
 */

define('ABSPATH') || exit;

class Ayra_Update_Delivery_Pricing
{
    private $data_dir;

    public function __construct()
    {
        $this->data_dir = plugin_dir_path(__FILE__) . 'zr-data/';
    }

    public function update_pricing()
    {
        $file = $this->data_dir . 'zr_pricing.json';
        if (!file_exists($file)) {
            error_log('Pricing JSON file not found: ' . $file);
            return false;
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON error in zr_pricing.json: ' . json_last_error_msg());
            return false;
        }

        // Create a new pricing array that matches the structure used in ayra_get_delivery_price
        $detailed_prices = [];

        foreach ($json as $pricing) {
            $code_padded = str_pad($pricing['territory_code'], 2, '0', STR_PAD_LEFT);

            $detailed_prices[$code_padded] = [
                'to_home' => $pricing['price_home'] ?? 0,
                'to_desk' => $pricing['price_stopdesk'] > 0 ? $pricing['price_stopdesk'] : 0,
                'return' => $pricing['price_return'] ?? 0
            ];
        }

        // Update WordPress option with the new pricing
        update_option('ayra_delivery_prices', $detailed_prices, false);
        error_log('ayra_delivery_prices updated with pricing for ' . count($detailed_prices) . ' wilayas');

        return $detailed_prices;
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $updater = new Ayra_Update_Delivery_Pricing();
    $updater->update_pricing();
    echo 'Delivery pricing updated from zr_pricing.json.';
}
?>