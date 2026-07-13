<?php
/**
 * AYRA Homewear - 404 Page
 */
defined('ABSPATH') || exit;

$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// If someone hits a product page and it 404s, resolve and render single-product.php
if (preg_match('#/product/([^/\?]+)#', $request_uri, $matches) && function_exists('WC')) {
    $product_slug = sanitize_title($matches[1]);
    $product_post = get_page_by_path($product_slug, OBJECT, 'product');
    if ($product_post && $product_post->post_status === 'publish') {
        // Set up the global post data so single-product.php works correctly
        global $wp_query, $post;
        $wp_query->is_404 = false;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->post = $product_post;
        $wp_query->posts = [$product_post];
        $wp_query->post_count = 1;
        $wp_query->found_posts = 1;
        $wp_query->queried_object = $product_post;
        $wp_query->queried_object_id = $product_post->ID;
        $post = $product_post;
        setup_postdata($post);
        status_header(200);
        include(get_template_directory() . '/single-product.php');
        exit;
    }
}

// If someone hits /checkout and it 404s, render checkout anyway
if (strpos($request_uri, '/checkout') !== false && function_exists('WC')) {
    if (!defined('WOOCOMMERCE_CHECKOUT')) {
        define('WOOCOMMERCE_CHECKOUT', true);
    }
    status_header(200);
    get_header();
    echo '<div style="max-width:1200px; margin:0 auto; padding:20px;">';
    echo do_shortcode('[woocommerce_checkout]');
    echo '</div>';
    get_footer();
    exit;
}

// If someone hits /cart and it 404s, render cart anyway
if (strpos($request_uri, '/cart') !== false && function_exists('WC')) {
    status_header(200);
    get_header();
    echo '<div style="max-width:1200px; margin:0 auto; padding:20px; min-height:50vh;">';
    echo do_shortcode('[woocommerce_cart]');
    echo '</div>';
    get_footer();
    exit;
}

// Standard 404
get_header();
?>
<div style="text-align:center; padding:80px 20px; max-width:600px; margin:0 auto;">
    <h1 style="font-size:72px; color:#C9A87C; margin-bottom:16px;">404</h1>
    <h2 style="font-size:22px; color:#2C2C2C; margin-bottom:12px;">عذراً، الصفحة غير موجودة</h2>
    <p style="color:#888; margin-bottom:32px;">يبدو أن الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" 
       style="display:inline-block; background:#C9A87C; color:white; padding:12px 32px; border-radius:8px; text-decoration:none; font-weight:600;">
        العودة للرئيسية
    </a>
</div>
<?php get_footer(); ?>
