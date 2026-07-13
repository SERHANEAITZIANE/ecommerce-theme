<?php
/**
 * AYRA Homewear - Checkout Page Template
 * WordPress auto-loads this for a page with slug "checkout"
 */
defined('ABSPATH') || exit;

if (!defined('WOOCOMMERCE_CHECKOUT')) {
    define('WOOCOMMERCE_CHECKOUT', true);
}

get_header();
?>

<div class="ayra-page-content" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <?php echo do_shortcode('[woocommerce_checkout]'); ?>
</div>

<?php get_footer(); ?>
