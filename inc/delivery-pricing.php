<?php
/**
 * AYRA Homewear - Delivery Pricing
 *
 * Detailed pricing for all 58 wilayas of Algeria
 */
defined('ABSPATH') || exit;

function ayra_get_delivery_price($wilaya_code, $delivery_type)
{
    // Get pricing from WordPress option (updated from zr_pricing.json)
    $detailed_prices = get_option('ayra_delivery_prices', []);

    // If option is not set or empty, use fallback hardcoded prices (this shouldn't happen if update-delivery-pricing.php was run)
    if (empty($detailed_prices)) {
        $detailed_prices = [
            // Wilaya Code => [to_home => price, to_desk => price, return_cost => price]
            '01' => ['to_home' => 900, 'to_desk' => 450, 'return' => 200],
            '02' => ['to_home' => 600, 'to_desk' => 450, 'return' => 200],
            '03' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '04' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '05' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '06' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '07' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '08' => ['to_home' => 1150, 'to_desk' => 650, 'return' => 200],
            '09' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '10' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],
            '11' => ['to_home' => 1650, 'to_desk' => 1050, 'return' => 250],
            '12' => ['to_home' => 900, 'to_desk' => 450, 'return' => 200],
            '13' => ['to_home' => 900, 'to_desk' => 500, 'return' => 200],
            '14' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '15' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '16' => ['to_home' => 650, 'to_desk' => 350, 'return' => 200],
            '17' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '18' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '19' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '20' => ['to_home' => 800, 'to_desk' => 500, 'return' => 200],
            '21' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '22' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],
            '23' => ['to_home' => 900, 'to_desk' => 450, 'return' => 200],
            '24' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '25' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '26' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],
            '27' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '28' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '29' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '30' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '31' => ['to_home' => 650, 'to_desk' => 350, 'return' => 200],
            '32' => ['to_home' => 1100, 'to_desk' => 600, 'return' => 200],
            '33' => ['to_home' => 1650, 'to_desk' => 1050, 'return' => 250],  // إليزي - Illizi (south)
            '34' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],
            '35' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '36' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '37' => ['to_home' => 1650, 'to_desk' => 1650, 'return' => 250],  // تندوف - Tindouf (south, no desk)
            '38' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],
            '39' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '40' => ['to_home' => 1100, 'to_desk' => 600, 'return' => 250],
            '41' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '42' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '43' => ['to_home' => 850, 'to_desk' => 450, 'return' => 200],
            '44' => ['to_home' => 500, 'to_desk' => 450, 'return' => 200],  // عين الدفلى - Aïn Defla (ville=200, autres=500)
            '45' => ['to_home' => 750, 'to_desk' => 450, 'return' => 200],
            '46' => ['to_home' => 800, 'to_desk' => 450, 'return' => 200],  // عين تموشنت - Aïn Témouchent (north)
            '47' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '48' => ['to_home' => 1000, 'to_desk' => 450, 'return' => 200],
            '49' => ['to_home' => 1450, 'to_desk' => 900, 'return' => 200],
            '50' => ['to_home' => 1050, 'to_desk' => 670, 'return' => 200],  // المنيعة - El Menia (south)
            '51' => ['to_home' => 1000, 'to_desk' => 550, 'return' => 200],
            '52' => ['to_home' => 1200, 'to_desk' => 900, 'return' => 200],
            '53' => ['to_home' => 1200, 'to_desk' => 900, 'return' => 200],
            '54' => ['to_home' => 1450, 'to_desk' => 900, 'return' => 250],
            '55' => ['to_home' => 1000, 'to_desk' => 600, 'return' => 200],
            '56' => ['to_home' => 1650, 'to_desk' => 1050, 'return' => 250],  // جانت - Djanet (south)
            '57' => ['to_home' => 1650, 'to_desk' => 1120, 'return' => 250],
            '58' => ['to_home' => 1650, 'to_desk' => 1650, 'return' => 250],
        ];
    }

    // Check if the wilaya code exists in our detailed pricing
    if (!isset($detailed_prices[$wilaya_code])) {
        return 0; // Return 0 if the wilaya code is not found
    }

    // Return the appropriate price based on delivery type
    if ($delivery_type === 'to_home') {
        return $detailed_prices[$wilaya_code]['to_home'];
    } elseif ($delivery_type === 'to_desk') {
        return $detailed_prices[$wilaya_code]['to_desk'];
    } elseif ($delivery_type === 'return') {
        return $detailed_prices[$wilaya_code]['return'];
    }

    return 0; // Default return if delivery type is not recognized
}

/**
 * Get delivery options for a specific wilaya
 */
function ayra_get_delivery_options($wilaya_code)
{
    $price_to_home = ayra_get_delivery_price($wilaya_code, 'to_home');
    $price_to_desk = ayra_get_delivery_price($wilaya_code, 'to_desk');

    $options = [];

    // Always include delivery to home
    if ($price_to_home > 0) {
        $options['to_home'] = [
            'label' => 'إلى المنزل',
            'price' => $price_to_home,
            'estimated_days' => '4-6 أيام',
            'return_cost' => ayra_get_delivery_price($wilaya_code, 'return')
        ];
    }

    // Include delivery to desk if available (price is not 0)
    if ($price_to_desk > 0) {
        $options['to_desk'] = [
            'label' => 'إلى نقطة البيع (Stop Desk)',
            'price' => $price_to_desk,
            'estimated_days' => '3-4 أيام',
            'return_cost' => ayra_get_delivery_price($wilaya_code, 'return')
        ];
    }

    return $options;
}

/**
 * Get the return cost for a wilaya
 */
function ayra_get_return_cost($wilaya_code)
{
    return ayra_get_delivery_price($wilaya_code, 'return');
}
?>