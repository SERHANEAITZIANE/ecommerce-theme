<?php
/**
 * AYRA Homewear - Front Page (Size Selector)
 * 
 * The landing page where users choose their size first.
 * Only sizes with available products are shown as active.
 */
defined('ABSPATH') || exit;

get_header();

// Get available sizes with product counts (cached for 1 hour)
$available_sizes = ayra_get_sizes_with_stock_cached();

// Build the correct product archive URL (not the shop page permalink which may resolve to homepage)
// Force the correct product archive URL that loads archive-product.php
$archive_url = home_url('/?post_type=product');

$all_sizes = array_unique(array_keys($available_sizes));
$size_order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, '3XL' => 7, '4XL' => 8, '5XL' => 9, '6XL' => 10];
usort($all_sizes, function($a, $b) use ($size_order) {
    $weight_a = isset($size_order[strtoupper($a)]) ? $size_order[strtoupper($a)] : 99;
    $weight_b = isset($size_order[strtoupper($b)]) ? $size_order[strtoupper($b)] : 99;
    return $weight_a === $weight_b ? strcmp($a, $b) : $weight_a - $weight_b;
});
?>

<?php
// Size label → numeric equivalent mapping
$size_numbers = [
    'S'   => '36',
    'M'   => '38',
    'L'   => '40',
    'XL'  => '42',
    'XXL' => '44/46',
    '3XL' => '48',
    '4XL' => '50',
    '5XL' => '52',
    '6XL' => '54',
    'XS'  => '34/36',
];
?>

<section class="ayra-hero ayra-hero--with-sizes" id="ayra-hero">
    <div class="ayra-hero-bg">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/img/hero-bg.jpg'); ?>" 
             alt="AYRA Boutique" 
             class="ayra-hero-bg-img" />
        <div class="ayra-hero-overlay"></div>
    </div>
    <div class="ayra-hero-content">
        <div class="ayra-hero-text-wrap">
            <h1 class="ayra-hero-title">تسوقي من بيتك</h1>
        </div>

        <div class="ayra-hero-buttons-wrap">

            <!-- Search bar ABOVE sizes -->
            <div class="ayra-hero-search-container">
                <form role="search" method="get" class="ayra-hero-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <div class="ayra-hero-search-input-wrap">
                        <span class="ayra-hero-search-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="text"
                               class="ayra-hero-search-input"
                               id="ayra-hero-search-input"
                               placeholder="بحث عن منتج..."
                               value=""
                               name="s"
                               autocomplete="off"
                               autocorrect="off"
                               autocapitalize="off"
                               spellcheck="false"
                               inputmode="search" />
                        <input type="hidden" name="post_type" value="product" />
                    </div>
                    <div class="ayra-hero-search-dropdown" id="ayra-hero-search-dropdown"></div>
                </form>
            </div>

            <p class="ayra-hero-subtitle">اختاري مقاسك لتصفح المنتجات المتوفرة</p>

            <div class="ayra-hero-sizes" id="ayra-hero-sizes">
                <div class="ayra-hero-sizes-grid">
                    <?php foreach ($all_sizes as $size): 
                        $count = isset($available_sizes[$size]) ? $available_sizes[$size] : 0;
                        if ($count <= 0) continue;
                        $shop_url = add_query_arg('filter_size', strtolower($size), $archive_url);
                        $num = isset($size_numbers[$size]) ? $size_numbers[$size] : '';
                    ?>
                    <a href="<?php echo esc_url($shop_url); ?>" 
                       class="ayra-hero-size-btn"
                       data-size="<?php echo esc_attr($size); ?>">
                        <div class="ayra-hero-size-btn-inner">
                            <span class="ayra-hero-size-letter"><?php echo esc_html($size); ?></span>
                            <?php if ($num): ?>
                            <span class="ayra-hero-size-num"><?php echo esc_html($num); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- WhatsApp Exchange & Inquiry Action Buttons -->
            <div class="ayra-hero-whatsapp-actions">
                <a href="https://wa.me/213563537757?text=<?php echo urlencode('السلام عليكم، أريد طلب استبدال منتج'); ?>" target="_blank" class="ayra-hero-wa-btn exchange-btn">
                    <span class="wa-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></span>
                    <span>طلب استبدال</span>
                </a>
                <a href="https://wa.me/213563537757?text=<?php echo urlencode('السلام عليكم، أريد استفسار'); ?>" target="_blank" class="ayra-hero-wa-btn question-btn">
                    <span class="wa-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    <span>استفسار</span>
                </a>
            </div>

        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="ayra-contact-section" id="ayra-contact">
    <div class="ayra-contact-inner">
        <div class="ayra-contact-info">
            <h2>تواصلي معنا</h2>
            <p class="ayra-contact-subtitle">نحن هنا لمساعدتك في أي استفسار</p>
            
            <div class="ayra-contact-cards">
                <a href="https://wa.me/213563537757" target="_blank" class="ayra-contact-card">
                    <div class="ayra-contact-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div class="ayra-contact-card-text">
                        <span class="ayra-contact-card-label">الهاتف / واتساب</span>
                        <span class="ayra-contact-card-value">0563 53 77 57</span>
                    </div>
                </a>

                <a href="https://wa.me/213563537757?text=<?php echo urlencode('السلام عليكم، أريد استفسار'); ?>" target="_blank" class="ayra-contact-card">
                    <div class="ayra-contact-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="ayra-contact-card-text">
                        <span class="ayra-contact-card-label">استفسار</span>
                        <span class="ayra-contact-card-value">راسلينا على واتساب</span>
                    </div>
                </a>

                <div class="ayra-contact-card">
                    <div class="ayra-contact-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="ayra-contact-card-text">
                        <span class="ayra-contact-card-label">العنوان</span>
                        <span class="ayra-contact-card-value" style="direction:rtl;">عين الدفلى، الجزائر</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ayra-contact-map">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3217.1!2d1.9579499!3d36.2614667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1285b7d97099b155%3A0x296f242fa03fb685!2sBoutique%20ayra!5e0!3m2!1sfr!2sdz!4v1700000000000!5m2!1sfr!2sdz"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>


<?php get_footer(); ?>

