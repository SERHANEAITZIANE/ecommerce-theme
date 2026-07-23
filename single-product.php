<?php
/**
 * AYRA Homewear - Single Product Page
 *
 * @package WooCommerce\Templates
 * @version 1.6.4
 */
defined('ABSPATH') || exit;

get_header();

while (have_posts()): the_post();
    global $product;
    $product_id = $product->get_id();
    $selected_size = isset($_GET['selected_size']) ? sanitize_text_field($_GET['selected_size']) : '';
    
    // Get product images
    $main_image = get_the_post_thumbnail_url($product_id, 'ayra-product-large');
    $gallery_ids = $product->get_gallery_image_ids();
    
    // Get variations data
    $variations_data = [];
    $available_sizes = [];
    if ($product->is_type('variable')) {
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
            $var_product = wc_get_product($variation['variation_id']);
            $stock = $var_product ? $var_product->get_stock_quantity() : 0;
            $in_stock = $var_product && $var_product->is_in_stock() && $stock > 0;
            
            $size_vals = [];
            foreach ($variation['attributes'] as $attr_key => $attr_val) {
                if ($attr_val) {
                    $clean_key = str_replace('attribute_', '', $attr_key);
                    $term = get_term_by('slug', $attr_val, $clean_key);
                    $size_vals[] = ($term && !is_wp_error($term)) ? $term->name : strtoupper(urldecode($attr_val));
                }
            }
            $size_val = !empty($size_vals) ? implode(' - ', $size_vals) : '';
            
            if ($size_val) {
                $variations_data[$size_val] = [
                    'variation_id' => $variation['variation_id'],
                    'in_stock'     => $in_stock,
                    'stock_qty'    => $stock,
                    'price'        => $var_product ? $var_product->get_price() : 0,
                    'price_html'   => $var_product ? $var_product->get_price_html() : '',
                ];
                if ($in_stock) {
                    $available_sizes[] = $size_val;
                }
            }
        }
    }
    
    $all_sizes = array_unique(array_keys($variations_data));
    $size_order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, '3XL' => 7, '4XL' => 8, '5XL' => 9, '6XL' => 10];
    usort($all_sizes, function($a, $b) use ($size_order) {
        $weight_a = isset($size_order[strtoupper($a)]) ? $size_order[strtoupper($a)] : 99;
        $weight_b = isset($size_order[strtoupper($b)]) ? $size_order[strtoupper($b)] : 99;
        return $weight_a === $weight_b ? strcmp($a, $b) : $weight_a - $weight_b;
    });
?>

<section class="ayra-single-product" id="ayra-single-product">
    <!-- Breadcrumb -->
    <div class="ayra-breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">الرئيسية</a>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">المنتجات</a>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        <span><?php echo esc_html($product->get_name()); ?></span>
    </div>

    <div class="ayra-product-layout">
        <!-- Image Carousel (Swipeable) -->
        <div class="ayra-product-gallery" id="ayra-product-gallery">
            <?php
            // Collect all images: main + gallery
            $all_images = [];
            if ($main_image) {
                $all_images[] = ['url' => $main_image, 'thumb' => $main_image];
            }
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'ayra-product-large');
                $thumb_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                if ($img_url) {
                    $all_images[] = ['url' => $img_url, 'thumb' => $thumb_url ?: $img_url];
                }
            }
            $total_slides = count($all_images);
            ?>
            <div class="ayra-carousel-wrap">
                <?php if ($product->is_on_sale()): ?>
                    <span class="ayra-product-badge">تخفيض</span>
                <?php endif; ?>
                <div class="ayra-carousel-track" id="ayra-carousel-track">
                    <?php foreach ($all_images as $i => $img): ?>
                    <div class="ayra-carousel-slide">
                        <img src="<?php echo esc_url($img['url']); ?>" 
                             alt="<?php echo esc_attr($product->get_name()); ?>"
                             loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($total_slides > 1): ?>
                <div class="ayra-carousel-dots" id="ayra-carousel-dots">
                    <?php for ($i = 0; $i < $total_slides; $i++): ?>
                    <button class="ayra-carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>" 
                            data-slide="<?php echo $i; ?>"></button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($total_slides > 1): ?>
            <div class="ayra-carousel-thumbs" id="ayra-carousel-thumbs">
                <?php foreach ($all_images as $i => $img): ?>
                <button class="ayra-carousel-thumb<?php echo $i === 0 ? ' active' : ''; ?>" 
                        data-slide="<?php echo $i; ?>">
                    <img src="<?php echo esc_url($img['thumb']); ?>" alt="">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Details -->
        <div class="ayra-product-details">
            <h1 class="ayra-product-title"><?php echo esc_html($product->get_name()); ?></h1>
            
            <div class="ayra-product-price" id="ayra-product-price">
                <?php echo $product->get_price_html(); ?>
            </div>

            <?php if ($product->get_short_description()): ?>
            <div class="ayra-product-desc">
                <?php echo wp_kses_post($product->get_short_description()); ?>
            </div>
            <?php endif; ?>

            <!-- Size Selector -->
            <?php if ($product->is_type('variable') && !empty($variations_data)): ?>
            <div class="ayra-product-sizes" id="ayra-product-sizes">
                <label class="ayra-field-label">اختاري المقاس</label>
                <div class="ayra-size-options">
                    <?php foreach ($all_sizes as $size):
                        $has_variation = isset($variations_data[$size]);
                        if (!$has_variation) continue; // Hide sizes that don't exist for this product
                        $in_stock = $variations_data[$size]['in_stock'];
                        if (!$in_stock) continue; // Hide out-of-stock sizes
                        $stock_qty = $variations_data[$size]['stock_qty'];
                        $is_selected = strtoupper($selected_size) === $size;
                    ?>
                    <button class="ayra-size-option available <?php echo $is_selected ? 'selected' : ''; ?>"
                            data-size="<?php echo esc_attr($size); ?>"
                            data-variation-id="<?php echo $variations_data[$size]['variation_id']; ?>"
                            data-stock="<?php echo $stock_qty; ?>"
                            data-price-html="<?php echo esc_attr($variations_data[$size]['price_html']); ?>">
                        <span class="ayra-size-name"><?php echo esc_html($size); ?></span>
                        <?php if ($stock_qty > 0 && $stock_qty <= 3): ?>
                        <span class="ayra-size-stock-badge low">كمية محدودة</span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="ayra-stock-info" id="ayra-stock-info" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="ayra-stock-text"></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quantity -->
            <div class="ayra-product-quantity">
                <label class="ayra-field-label">الكمية</label>
                <div class="ayra-qty-selector">
                    <button class="ayra-qty-btn" id="ayra-qty-minus">−</button>
                    <input type="number" id="ayra-qty-input" value="1" min="1" max="10" class="ayra-qty-input">
                    <button class="ayra-qty-btn" id="ayra-qty-plus">+</button>
                </div>
            </div>

            <!-- Add to Cart -->
            <div class="ayra-product-actions">
                <button class="ayra-btn ayra-btn-primary ayra-add-to-cart-main" 
                        id="ayra-add-to-cart-main"
                        data-product-id="<?php echo $product_id; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    أضيفي للسلة
                </button>

            </div>

            <!-- Product Meta -->
            <div class="ayra-product-meta">
                <?php 
                $categories = wc_get_product_category_list($product_id, ', ');
                if ($categories): ?>
                <div class="ayra-meta-item">
                    <span class="ayra-meta-label">الفئة:</span>
                    <span class="ayra-meta-value"><?php echo $categories; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    <?php if ($product->get_description()): ?>
    <div class="ayra-product-full-desc">
        <h3>وصف المنتج</h3>
        <div class="ayra-desc-content">
            <?php echo wp_kses_post($product->get_description()); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews Section -->
    <?php if (function_exists('ayra_render_reviews_section')): ?>
        <?php ayra_render_reviews_section($product->get_id()); ?>
    <?php endif; ?>
</section>

<?php endwhile; ?>

<?php get_footer(); ?>
