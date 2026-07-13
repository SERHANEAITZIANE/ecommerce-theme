<?php
/**
 * AYRA Homewear - Product Creator Script
 * Run once via URL: https://ayrahomewear.com/?ayra_create_products=1&key=ayra2026
 * Then DELETE this file
 */

// Hook into WordPress init
add_action('init', 'ayra_create_products_action');

function ayra_create_products_action() {
    if (!isset($_GET['ayra_create_products']) || $_GET['key'] !== 'ayra2026') {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Not authorized');
    }
    
    // Prevent timeout
    set_time_limit(300);
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>AYRA Product Creator</h1>';
    echo '<pre>';
    
    // --- Step 1: Clean up junk products ---
    $all_products = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending'],
        'numberposts' => -1,
        'fields' => 'ids',
    ]);
    
    foreach ($all_products as $pid) {
        $title = get_the_title($pid);
        if (strpos($title, 'javascript:') !== false || strpos($title, 'fetch(') !== false) {
            wp_delete_post($pid, true);
            echo "Deleted junk product: $pid\n";
        }
    }
    
    // --- Step 2: Create/get categories ---
    $categories = [
        'PYJAMA' => 0,
        'ROBE DE CHAMBRE' => 0,
        'ENSEMBLE POLAIRE' => 0,
        'ENSEMBLE VELOURS' => 0,
    ];
    
    foreach ($categories as $cat_name => &$cat_id) {
        $term = get_term_by('name', $cat_name, 'product_cat');
        if ($term) {
            $cat_id = $term->term_id;
            echo "Category exists: $cat_name (ID: $cat_id)\n";
        } else {
            $result = wp_insert_term($cat_name, 'product_cat');
            if (!is_wp_error($result)) {
                $cat_id = $result['term_id'];
                echo "Created category: $cat_name (ID: $cat_id)\n";
            } else {
                echo "Error creating category $cat_name: " . $result->get_error_message() . "\n";
            }
        }
    }
    unset($cat_id);
    
    // --- Step 3: Ensure SIZE attribute exists ---
    $attribute_name = 'size';
    $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
    
    if (!$attribute_id) {
        $attribute_id = wc_create_attribute([
            'name' => 'SIZE',
            'slug' => 'size',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);
        echo "Created SIZE attribute (ID: $attribute_id)\n";
    } else {
        echo "SIZE attribute exists (ID: $attribute_id)\n";
    }
    
    // Ensure taxonomy is registered
    $taxonomy = 'pa_size';
    if (!taxonomy_exists($taxonomy)) {
        register_taxonomy($taxonomy, 'product', [
            'hierarchical' => false,
            'label' => 'SIZE',
            'query_var' => true,
            'rewrite' => ['slug' => 'size'],
        ]);
    }
    
    // Ensure all size terms exist
    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];
    foreach ($sizes as $size) {
        if (!term_exists($size, $taxonomy)) {
            wp_insert_term($size, $taxonomy, ['slug' => strtolower($size)]);
            echo "Created size term: $size\n";
        }
    }
    
    // --- Step 4: Define products ---
    $products_data = [
        [
            'name' => 'Pyjama Satin Rose',
            'description' => 'Pyjama en satin de haute qualite, doux et elegant. Ensemble compose d\'un haut a manches longues avec boutons et un pantalon assorti. Tissu satine qui offre un confort incomparable pour des nuits luxueuses.',
            'short_description' => 'Pyjama satin luxueux - Rose poudre',
            'category' => 'PYJAMA',
            'price' => '3500',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'stock_per_size' => [5, 8, 10, 7, 6, 4],
        ],
        [
            'name' => 'Ensemble Polaire Bleu Nuit',
            'description' => 'Ensemble polaire premium en bleu nuit avec details dores elegants. Pullover a manches longues et pantalon jogger assorti. Tissu polaire ultra-doux, parfait pour les soirees fraiches d\'hiver.',
            'short_description' => 'Ensemble polaire premium - Bleu nuit & Or',
            'category' => 'ENSEMBLE POLAIRE',
            'price' => '4500',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'],
            'stock_per_size' => [4, 7, 9, 8, 6, 5, 3],
        ],
        [
            'name' => 'Robe de Chambre Soie Bordeaux',
            'description' => 'Robe de chambre elegante en soie-look bordeaux avec dentelle delicate. Ceinture a nouer et coupe fluide. Un vetement d\'interieur raffine qui allie confort et sophistication.',
            'short_description' => 'Robe de chambre elegante - Bordeaux',
            'category' => 'ROBE DE CHAMBRE',
            'price' => '5000',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'stock_per_size' => [3, 6, 8, 7, 5, 4],
        ],
        [
            'name' => 'Pyjama Coton Fleuri Vert',
            'description' => 'Pyjama en coton naturel avec imprime floral delicat sur fond vert sauge. Haut a manches courtes et short assorti. Tissu 100% coton respirant, ideal pour l\'ete.',
            'short_description' => 'Pyjama coton fleuri - Vert sauge',
            'category' => 'PYJAMA',
            'price' => '2800',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'stock_per_size' => [6, 9, 10, 8, 5, 3],
        ],
        [
            'name' => 'Ensemble Velours Noir Chic',
            'description' => 'Ensemble velours noir avec accents dores. Top zippe ajuste et pantalon large elegant. Velours premium ultra-doux pour un look sophistique a la maison.',
            'short_description' => 'Ensemble velours chic - Noir & Or',
            'category' => 'ENSEMBLE VELOURS',
            'price' => '5500',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'],
            'stock_per_size' => [4, 7, 9, 8, 6, 5, 3, 2],
        ],
        [
            'name' => 'Pyjama Leopard Soyeux',
            'description' => 'Pyjama a imprime leopard en tissu soyeux et fluide. Chemise a manches longues avec boutons et pantalon assorti. Un motif audacieux pour des nuits stylees.',
            'short_description' => 'Pyjama soyeux imprime leopard',
            'category' => 'PYJAMA',
            'price' => '3800',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'stock_per_size' => [5, 8, 10, 7, 5, 3],
        ],
    ];
    
    // --- Step 5: Create or update products ---
    foreach ($products_data as $pdata) {
        // Check if product already exists
        $existing = get_posts([
            'post_type' => 'product',
            'title' => $pdata['name'],
            'post_status' => ['publish', 'draft'],
            'numberposts' => 1,
        ]);
        
        if (!empty($existing)) {
            $product_id = $existing[0]->ID;
            echo "\nProduct exists: {$pdata['name']} (ID: $product_id) - Updating...\n";
            $product = wc_get_product($product_id);
            if ($product) {
                // Delete all existing variations first
                foreach ($product->get_children() as $child_id) {
                    wp_delete_post($child_id, true);
                }
            }
        } else {
            $product_id = wp_insert_post([
                'post_title' => $pdata['name'],
                'post_content' => $pdata['description'],
                'post_excerpt' => $pdata['short_description'],
                'post_status' => 'publish',
                'post_type' => 'product',
            ]);
            echo "\nCreated product: {$pdata['name']} (ID: $product_id)\n";
        }
        
        if (is_wp_error($product_id) || !$product_id) {
            echo "ERROR creating product: {$pdata['name']}\n";
            continue;
        }
        
        // Update post content/excerpt if existing
        wp_update_post([
            'ID' => $product_id,
            'post_content' => $pdata['description'],
            'post_excerpt' => $pdata['short_description'],
            'post_status' => 'publish',
        ]);
        
        // Set product type to variable
        wp_set_object_terms($product_id, 'variable', 'product_type');
        
        // Set category
        $cat_id = $categories[$pdata['category']];
        wp_set_object_terms($product_id, [$cat_id], 'product_cat');
        
        // Set the SIZE attribute on the product
        $size_slugs = array_map('strtolower', $pdata['sizes']);
        wp_set_object_terms($product_id, $size_slugs, $taxonomy);
        
        // Set product attributes meta
        $product_attributes = [];
        $product_attributes[$taxonomy] = [
            'name' => $taxonomy,
            'value' => '',
            'position' => 0,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 1,
        ];
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        
        // Create variations
        foreach ($pdata['sizes'] as $i => $size) {
            $variation_id = wp_insert_post([
                'post_title' => $pdata['name'] . ' - ' . $size,
                'post_status' => 'publish',
                'post_type' => 'product_variation',
                'post_parent' => $product_id,
            ]);
            
            if ($variation_id && !is_wp_error($variation_id)) {
                update_post_meta($variation_id, '_regular_price', $pdata['price']);
                update_post_meta($variation_id, '_price', $pdata['price']);
                update_post_meta($variation_id, '_manage_stock', 'yes');
                update_post_meta($variation_id, '_stock', $pdata['stock_per_size'][$i]);
                update_post_meta($variation_id, '_stock_status', 'instock');
                update_post_meta($variation_id, 'attribute_' . $taxonomy, strtolower($size));
                
                echo "  Created variation: $size (ID: $variation_id, Stock: {$pdata['stock_per_size'][$i]})\n";
            }
        }
        
        // Update parent product stock status
        update_post_meta($product_id, '_stock_status', 'instock');
        
        // Sync the product to update min/max prices
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_stock_status('instock');
            $product->save();
            
            // Manually sync variable product data
            WC_Product_Variable::sync($product_id);
        }
        
        echo "  Product fully configured!\n";
    }
    
    // Clear WooCommerce transients
    wc_delete_product_transients();
    delete_transient('wc_products_onsale');
    
    echo "\n\n=== ALL PRODUCTS CREATED SUCCESSFULLY ===\n";
    echo "Total products: " . count($products_data) . "\n";
    echo "\nDon't forget to:\n";
    echo "1. Upload product images via Media Library\n";
    echo "2. Delete this create-products.php file\n";
    echo '</pre>';
    exit;
}
