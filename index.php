<?php
/**
 * AYRA Homewear - Index (Fallback)
 */
defined('ABSPATH') || exit;

get_header();

$req = $_SERVER['REQUEST_URI'] ?? '';
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 24px;">
    <?php if (strpos($req, '/checkout') !== false) : ?>
        <?php echo do_shortcode('[woocommerce_checkout]'); ?>
    <?php elseif (strpos($req, '/cart') !== false) : ?>
        <?php echo do_shortcode('[woocommerce_cart]'); ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <h1 style="font-size: 28px; color: #2C2C2C;">AYRA Homewear</h1>
            <p style="color: #888; margin-top: 12px;">تسوقي من بيتك</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" style="display:inline-block; margin-top:24px; background:#C9A87C; color:white; padding:12px 32px; border-radius:8px; text-decoration:none; font-weight:600;">الرئيسية</a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
