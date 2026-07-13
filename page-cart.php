<?php
/**
 * AYRA Homewear - Cart Page Template
 * WordPress auto-loads this for a page with slug "cart"
 */
defined('ABSPATH') || exit;

get_header();
?>

<div class="ayra-page-content" style="max-width: 1200px; margin: 0 auto; padding: 20px; min-height: 50vh;">
    <?php
    echo do_shortcode('[woocommerce_cart]');
    
    if (function_exists('WC') && WC()->cart && WC()->cart->is_empty()):
    ?>
    <div style="text-align:center; padding: 40px; background: white; border-radius: 12px; border: 1px solid #E8E4DF; margin-top: 20px;">
        <h2 style="font-size: 24px; color: #2C2C2C;">سلة التسوق فارغة</h2>
        <p style="color: #6B6B6B; margin-bottom: 20px;">يرجى إضافة بعض المنتجات لمتابعة الطلب.</p>
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" 
           style="display:inline-block; background: #C9A87C; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
            تصفح المنتجات
        </a>
    </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
