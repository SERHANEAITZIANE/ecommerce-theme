<?php
require_once dirname(__FILE__) . '/../../../wp-load.php';

$output = [];

$all_variations = wc_get_products(['status' => 'publish', 'type' => 'variation', 'limit' => 5]);
foreach ($all_variations as $var) {
    if ($var->is_in_stock() && $var->get_stock_quantity() > 0) {
        $output['variations'][$var->get_id()] = [
            'attributes' => $var->get_attributes(),
            'extracted_size' => ayra_extract_size_from_variation($var)
        ];
    }
}

$categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
foreach ($categories as $cat) {
    $output['categories'][] = $cat->slug;
}

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);
exit;
