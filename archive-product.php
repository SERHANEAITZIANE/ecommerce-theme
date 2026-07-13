<?php
/**
 * AYRA Homewear - Archive Product (Shop Page)
 * 
 * Premium product filter with sizes and stock availability.
 * Displays only products in stock for the selected size.
 *
 * @package WooCommerce\Templates
 * @version 8.6.0
 */
defined('ABSPATH') || exit;

get_header();

$selected_size = isset($_GET['filter_size']) ? sanitize_text_field(wp_unslash($_GET['filter_size'])) : '';
$selected_cat  = isset($_GET['product_cat']) ? sanitize_text_field(wp_unslash($_GET['product_cat'])) : '';

// Also detect category from taxonomy archive URL (when navigating to /product-category/slug/)
if (!$selected_cat && is_product_category()) {
    $queried = get_queried_object();
    if ($queried && isset($queried->slug)) {
        $selected_cat = $queried->slug;
    }
}

// Get TOP-LEVEL product categories (sub-categories show as a second row)
$categories = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'parent'     => 0,
]);

// Resolve the selected category and its top-level ancestor so the parent
// pill stays active and its sub-categories row is displayed
$selected_cat_term = $selected_cat ? get_term_by('slug', $selected_cat, 'product_cat') : false;
$active_top_id = 0;
if ($selected_cat_term && !is_wp_error($selected_cat_term)) {
    $cat_ancestors = get_ancestors($selected_cat_term->term_id, 'product_cat');
    $active_top_id = empty($cat_ancestors) ? $selected_cat_term->term_id : (int) end($cat_ancestors);
}
$sub_categories = $active_top_id ? get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'parent'     => $active_top_id,
]) : [];
if (is_wp_error($sub_categories)) $sub_categories = [];
$active_top_term = $active_top_id ? get_term($active_top_id, 'product_cat') : false;

// Build base URL for filter links — must point to the product ARCHIVE, not the homepage
// Force the correct product archive URL that actually loads archive-product.php
$shop_page_url = home_url('/?post_type=product');

// ─── Build cross-filtered counts ─────────────────────────
// We need: products that are in stock, optionally filtered by size and/or category
// This gives us accurate counts for both size pills and category pills

// Step 1: Get ALL products with their in-stock size variations and categories
$all_product_data = ayra_get_all_product_filter_data_cached();

// Index by product_id for fast O(1) lookups in the product loop
$product_data_index = [];
foreach ($all_product_data as $p) {
    $product_data_index[$p['product_id']] = $p;
}

// Step 2: Compute size counts (filtered by selected category if any)
$size_stock_map = [];
foreach ($all_product_data as $p) {
    // If a category is selected, skip products not in that category
    if ($selected_cat && !in_array($selected_cat, $p['cat_slugs'])) continue;
    foreach ($p['sizes'] as $size_name => $size_data) {
        $stock = is_array($size_data) ? $size_data['stock'] : $size_data;
        if (!isset($size_stock_map[$size_name])) $size_stock_map[$size_name] = 0;
        $size_stock_map[$size_name] += $stock;
    }
}

// Step 3: Compute category counts (filtered by selected size if any)
$cat_counts = [];
foreach ($all_product_data as $p) {
    // If a size is selected, skip products that don't have that size in stock
    if ($selected_size && !isset($p['sizes'][strtoupper($selected_size)])) continue;
    foreach ($p['cat_slugs'] as $slug) {
        if (!isset($cat_counts[$slug])) $cat_counts[$slug] = 0;
        $cat_counts[$slug]++;
    }
}

$all_sizes = array_keys($size_stock_map);
$size_order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, '3XL' => 7, '4XL' => 8, '5XL' => 9, '6XL' => 10];
usort($all_sizes, function($a, $b) use ($size_order) {
    $weight_a = isset($size_order[strtoupper($a)]) ? $size_order[strtoupper($a)] : 99;
    $weight_b = isset($size_order[strtoupper($b)]) ? $size_order[strtoupper($b)] : 99;
    return $weight_a === $weight_b ? strcmp($a, $b) : $weight_a - $weight_b;
});
?>

<section class="ayra-shop" id="ayra-shop">

    <!-- ═══ Premium Filter Bar ═══ -->
    <div class="ayra-filter-bar" id="ayra-filter-bar">
        <div class="ayra-filter-bar-inner">
            
            <?php
            // Compute result count based on active filters
            $total = 0;
            foreach ($all_product_data as $p) {
                $match_size = true;
                $match_cat = true;
                if ($selected_size && !isset($p['sizes'][strtoupper($selected_size)])) $match_size = false;
                if ($selected_cat && !in_array($selected_cat, $p['cat_slugs'])) $match_cat = false;
                if ($match_size && $match_cat) $total++;
            }
            ?>

            <!-- Mobile Toggle Button -->
            <button class="ayra-filter-toggle-btn" id="ayra-filter-toggle" type="button" aria-expanded="false">
                <span class="ayra-filter-toggle-left">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <span class="ayra-filter-toggle-label">تصفية المنتجات</span>
                    <span class="ayra-result-count">
                        <span class="ayra-result-number"><?php echo $total; ?></span> منتج
                    </span>
                </span>
                <svg class="ayra-filter-toggle-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>

            <!-- Collapsible Wrapper (collapsed on mobile, always visible on desktop) -->
            <div class="ayra-filter-collapsible">
            <div class="ayra-filter-collapsible-inner">

            <!-- Filter Header with Result Count (desktop only) -->
            <div class="ayra-filter-header">
                <div class="ayra-filter-title-row">
                    <h2 class="ayra-filter-main-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        تصفية المنتجات
                    </h2>
                    <span class="ayra-result-count">
                        <span class="ayra-result-number"><?php echo $total; ?></span> منتج
                    </span>
                </div>
            </div>

            <!-- Size Filter -->
            <div class="ayra-filter-section" id="ayra-size-filter">
                <div class="ayra-filter-section-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                    المقاس
                </div>
                <div class="ayra-size-filter-pills">
                    <?php 
                    // "All" pill — keeps category filter if active
                    $all_url = $selected_cat ? add_query_arg('product_cat', $selected_cat, $shop_page_url) : $shop_page_url;
                    ?>
                    <a href="<?php echo esc_url($all_url); ?>" 
                       class="ayra-filter-pill <?php echo !$selected_size ? 'active' : ''; ?>"
                       data-size="">
                        <span class="ayra-pill-text">الكل</span>
                    </a>
                    <?php 
                    // Also show sizes that exist globally but may have 0 for current category
                    $global_sizes = array_keys(ayra_get_size_stock_map_cached());
                    $merged_sizes = array_unique(array_merge($all_sizes, $global_sizes));
                    usort($merged_sizes, function($a, $b) use ($size_order) {
                        $weight_a = isset($size_order[strtoupper($a)]) ? $size_order[strtoupper($a)] : 99;
                        $weight_b = isset($size_order[strtoupper($b)]) ? $size_order[strtoupper($b)] : 99;
                        return $weight_a === $weight_b ? strcmp($a, $b) : $weight_a - $weight_b;
                    });
                    foreach ($merged_sizes as $size): 
                        $url = add_query_arg('filter_size', strtolower($size), $shop_page_url);
                        if ($selected_cat) $url = add_query_arg('product_cat', $selected_cat, $url);
                        $stock_count = isset($size_stock_map[$size]) ? $size_stock_map[$size] : 0;
                        $is_active = strtolower($selected_size) === strtolower($size);
                        // Skip sizes with 0 stock (no products have this size in stock for current filters)
                        if ($stock_count <= 0 && !$is_active) continue;
                    ?>
                    <a href="<?php echo esc_url($url); ?>" 
                       class="ayra-filter-pill <?php echo $is_active ? 'active' : ''; ?> <?php echo $stock_count <= 0 ? 'disabled' : ''; ?>"
                       data-size="<?php echo esc_attr(strtolower($size)); ?>">
                        <span class="ayra-pill-text"><?php echo esc_html($size); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Filter -->
            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
            <div class="ayra-filter-section" id="ayra-cat-filter">
                <div class="ayra-filter-section-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    الفئة
                </div>
                <div class="ayra-cat-filter-pills">
                    <?php 
                    $cat_all_url = $selected_size ? add_query_arg('filter_size', $selected_size, $shop_page_url) : $shop_page_url;
                    ?>
                    <a href="<?php echo esc_url($cat_all_url); ?>" 
                       class="ayra-filter-pill <?php echo !$selected_cat ? 'active' : ''; ?>">
                        <span class="ayra-pill-text">الكل</span>
                    </a>
                    <?php foreach ($categories as $cat):
                        // Use query-param based URL so filtering stays consistent
                        $cat_url = add_query_arg('product_cat', $cat->slug, $shop_page_url);
                        if ($selected_size) $cat_url = add_query_arg('filter_size', $selected_size, $cat_url);

                        // Get the cross-filtered count for this category
                        $filtered_count = isset($cat_counts[$cat->slug]) ? $cat_counts[$cat->slug] : 0;
                        // Active when the category itself OR one of its children is selected
                        $is_cat_active = ($active_top_id === (int) $cat->term_id);
                    ?>
                    <a href="<?php echo esc_url($cat_url); ?>"
                       class="ayra-filter-pill <?php echo $is_cat_active ? 'active' : ''; ?> <?php echo ($filtered_count <= 0 && !$is_cat_active) ? 'disabled' : ''; ?>">
                        <span class="ayra-pill-text"><?php echo esc_html($cat->name); ?></span>
                        <span class="ayra-pill-count"><?php echo $filtered_count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sub-category Filter (packs etc.) — shown when the active category has children -->
            <?php if (!empty($sub_categories) && $active_top_term && !is_wp_error($active_top_term)): ?>
            <div class="ayra-filter-section" id="ayra-subcat-filter">
                <div class="ayra-filter-section-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                    <?php echo esc_html($active_top_term->name); ?>
                </div>
                <div class="ayra-cat-filter-pills">
                    <?php
                    // "All" pill = the parent category itself
                    $parent_all_url = add_query_arg('product_cat', $active_top_term->slug, $shop_page_url);
                    if ($selected_size) $parent_all_url = add_query_arg('filter_size', $selected_size, $parent_all_url);
                    ?>
                    <a href="<?php echo esc_url($parent_all_url); ?>"
                       class="ayra-filter-pill <?php echo ($selected_cat === $active_top_term->slug) ? 'active' : ''; ?>">
                        <span class="ayra-pill-text">الكل</span>
                    </a>
                    <?php foreach ($sub_categories as $sub):
                        $sub_url = add_query_arg('product_cat', $sub->slug, $shop_page_url);
                        if ($selected_size) $sub_url = add_query_arg('filter_size', $selected_size, $sub_url);
                        $sub_count = isset($cat_counts[$sub->slug]) ? $cat_counts[$sub->slug] : 0;
                        $is_sub_active = ($selected_cat === $sub->slug);
                    ?>
                    <a href="<?php echo esc_url($sub_url); ?>"
                       class="ayra-filter-pill <?php echo $is_sub_active ? 'active' : ''; ?> <?php echo ($sub_count <= 0 && !$is_sub_active) ? 'disabled' : ''; ?>">
                        <span class="ayra-pill-text"><?php echo esc_html($sub->name); ?></span>
                        <span class="ayra-pill-count"><?php echo $sub_count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            </div><!-- .ayra-filter-collapsible-inner -->
            </div><!-- .ayra-filter-collapsible -->  
        </div>

        <!-- Active Filter Banner -->
        <?php if ($selected_size || $selected_cat):
            // Get category name for display (with parent path for sub-categories)
            $cat_name = '';
            if ($selected_cat_term && !is_wp_error($selected_cat_term)) {
                $cat_name = $selected_cat_term->name;
                if ($active_top_term && !is_wp_error($active_top_term) && $active_top_id !== (int) $selected_cat_term->term_id) {
                    $cat_name = $active_top_term->name . ' ← ' . $cat_name;
                }
            }
        ?>
        <div class="ayra-active-filter-banner">
            <div class="ayra-active-filter-inner">
                <div class="ayra-active-filter-info">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>
                        عرض المنتجات المتوفرة
                        <?php if ($selected_size): ?>
                            بمقاس <strong><?php echo esc_html(strtoupper($selected_size)); ?></strong>
                        <?php endif; ?>
                        <?php if ($selected_size && $cat_name): ?>
                            في
                        <?php endif; ?>
                        <?php if ($cat_name): ?>
                            فئة <strong><?php echo esc_html($cat_name); ?></strong>
                        <?php endif; ?>
                    </span>
                </div>
                <a href="<?php echo esc_url($shop_page_url); ?>" class="ayra-clear-all-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    مسح الفلتر
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ Products Grid ═══ -->
    <div class="ayra-products-container">
        <?php
        // Build a filtered product list based on active filters
        $filtered_ids = [];
        foreach ($all_product_data as $p) {
            $match_size = true;
            $match_cat = true;
            if ($selected_size && !isset($p['sizes'][strtoupper($selected_size)])) $match_size = false;
            if ($selected_cat && !in_array($selected_cat, $p['cat_slugs'])) $match_cat = false;
            if ($match_size && $match_cat) $filtered_ids[] = $p['product_id'];
        }
        
        if ($selected_size || $selected_cat) {
            if (!empty($filtered_ids)) {
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $custom_args = [
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => 24,
                    'paged'          => $paged,
                    'post__in'       => $filtered_ids,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                ];
                $product_query = new WP_Query($custom_args);
            } else {
                $product_query = new WP_Query(['post__in' => [0], 'post_type' => 'product']);
            }
        } else {
            // No filter – use the default main query, but only in-stock products
            global $wp_query;
            $product_query = $wp_query;
        }
        ?>
        <?php if ($product_query->have_posts()): ?>
        <div class="ayra-products-grid" id="ayra-products-grid">
            <?php while ($product_query->have_posts()): $product_query->the_post(); 
                global $product;
                $product = wc_get_product(get_the_ID());
                if (!$product) continue;
                $product_id = $product->get_id();
                $image_url = get_the_post_thumbnail_url($product_id, 'ayra-product-card');
                $price = $product->get_price_html();
                $name = $product->get_name();
                
                // Get available sizes with stock for this product
                $product_sizes = [];
                $total_product_stock = 0;
                if (isset($product_data_index[$product_id]) && !empty($product_data_index[$product_id]['sizes'])) {
                    foreach ($product_data_index[$product_id]['sizes'] as $size_name => $size_data) {
                        $stock_qty = is_array($size_data) ? $size_data['stock'] : $size_data;
                        $product_sizes[] = [
                            'name' => $size_name,
                            'stock' => $stock_qty,
                        ];
                        $total_product_stock += $stock_qty;
                    }
                }
                
                // Determine stock status display
                $stock_status = 'instock';
                $stock_text = '';
                if ($total_product_stock > 0 && $total_product_stock <= 3) {
                    $stock_status = 'low';
                    $stock_text = 'كمية محدودة';
                }
            ?>
            <div class="ayra-product-card" id="product-card-<?php echo $product_id; ?>">
                <a href="<?php the_permalink(); ?><?php echo $selected_size ? '?selected_size=' . esc_attr($selected_size) : ''; ?>" class="ayra-product-card-link">
                    <div class="ayra-product-card-img">
                        <?php if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="ayra-product-card-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badges -->
                        <div class="ayra-card-badges">
                            <?php if ($product->is_on_sale()): ?>
                                <span class="ayra-product-badge sale">تخفيض</span>
                            <?php endif; ?>
                            <?php if ($stock_text): ?>
                            <span class="ayra-product-badge stock-badge <?php echo esc_attr($stock_status); ?>">
                                <span class="ayra-stock-dot"></span>
                                <?php echo esc_html($stock_text); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ayra-product-card-info">
                        <h3 class="ayra-product-card-name"><?php echo esc_html($name); ?></h3>
                        <div class="ayra-product-card-price"><?php echo $price; ?></div>
                        
                        <!-- Size Tags with Stock -->
                        <?php if (!empty($product_sizes)): ?>
                        <div class="ayra-product-card-sizes">
                            <?php foreach ($product_sizes as $s): 
                                $is_highlighted = strtolower($selected_size) === strtolower($s['name']);
                                $size_class = $is_highlighted ? 'highlighted' : '';
                                if ($s['stock'] > 0 && $s['stock'] <= 2) $size_class .= ' low-stock';
                            ?>
                                <span class="ayra-product-size-tag <?php echo $size_class; ?>">
                                    <?php echo esc_html($s['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Quick Add to Cart Button -->
                <?php 
                $variation_id = false;
                if ($product->is_type('variable') && $selected_size) {
                    $target_sz = strtoupper(urldecode($selected_size));
                    $variation_id = isset($product_data_index[$product_id]['sizes'][$target_sz]['variation_id']) ? $product_data_index[$product_id]['sizes'][$target_sz]['variation_id'] : false;
                }
                
                if ($variation_id):
                ?>
                <button class="ayra-add-to-cart-btn" 
                        data-product-id="<?php echo $product_id; ?>"
                        data-variation-id="<?php echo $variation_id; ?>"
                        data-size="<?php echo esc_attr($selected_size); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    أضيفي للسلة
                </button>
                <?php else: ?>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="ayra-add-to-cart-btn select-options">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php echo $product->is_type('variable') ? 'اختيار المقاس' : 'أضيفي للسلة'; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <!-- Pagination -->
        <div class="ayra-pagination">
            <?php
            global $wp_query;
            $format = isset( $_SERVER['DOCUMENT_URI'] ) && strpos( $_SERVER['DOCUMENT_URI'], 'page/' ) !== false ? 'page/%#%' : '?paged=%#%';
            echo paginate_links([
                'base'      => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
                'format'    => $format,
                'add_args'  => false,
                'current'   => max( 1, get_query_var( 'paged' ) ),
                'total'     => isset($product_query) ? $product_query->max_num_pages : $wp_query->max_num_pages,
                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                'next_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
            ]);
            ?>
        </div>
        
        <?php else: ?>
        <div class="ayra-no-products">
            <div class="ayra-no-products-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <h3>لا توجد منتجات متوفرة</h3>
            <?php if ($selected_size): ?>
                <p>لا توجد منتجات متوفرة بمقاس <?php echo esc_html(strtoupper($selected_size)); ?> حالياً</p>
            <?php else: ?>
                <p>لا توجد منتجات للعرض حالياً</p>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="ayra-btn ayra-btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                اختاري مقاس آخر
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
