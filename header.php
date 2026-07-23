<?php
/**
 * AYRA Homewear - Header
 */
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AYRA Homewear - تسوقي من بيتك | ملابس منزلية وبيجامات عصرية">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- TikTok Pixel Code Start -->
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(
    var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script")
    ;n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};


      ttq.load('D8PJDIRC77U896T4R9N0');
      ttq.page();
    }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>


    <header class="ayra-header" id="ayra-header">
        <?php if (function_exists('ayra_render_announcement_banner')) ayra_render_announcement_banner(); ?>
        <div class="ayra-header-inner">
            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="ayra-logo" id="ayra-logo">
                <span class="ayra-logo-text">AYRA</span>
                <span class="ayra-logo-sub">HOMEWEAR</span>
            </a>

            <!-- Navigation -->
            <nav class="ayra-nav" id="ayra-nav">
                <a href="<?php echo esc_url(home_url('/')); ?>"
                    class="ayra-nav-link <?php echo is_front_page() ? 'active' : ''; ?>">الرئيسية</a>
                <a href="<?php echo esc_url(home_url('/?post_type=product')); ?>"
                    class="ayra-nav-link <?php echo is_shop() || is_product_taxonomy() ? 'active' : ''; ?>">المنتجات</a>
            </nav>

            <div class="ayra-header-actions">
                <!-- Search Button -->
                <button class="ayra-search-btn" id="ayra-search-toggle" aria-label="Search products">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </button>

                <!-- Cart Button -->
                <button class="ayra-cart-btn" id="ayra-cart-toggle" aria-label="Open cart">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 01-8 0" />
                    </svg>
                    <span
                        class="ayra-cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
                </button>

                <!-- Mobile Menu Toggle -->
                <button class="ayra-menu-toggle" id="ayra-menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Cart Drawer -->
    <div class="ayra-cart-overlay" id="ayra-cart-overlay"></div>
    <aside class="ayra-cart-drawer" id="ayra-cart-drawer">
        <div class="ayra-cart-drawer-header">
            <h3>سلة التسوق</h3>
            <button class="ayra-cart-close" id="ayra-cart-close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <div class="ayra-cart-drawer-body">
            <?php ayra_render_mini_cart(); ?>
        </div>

        <div class="ayra-cart-drawer-footer">
            <div class="ayra-cart-subtotal">
                <span>المجموع</span>
                <span class="ayra-cart-total"><?php echo WC()->cart ? WC()->cart->get_cart_total() : '0 DA'; ?></span>
            </div>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="ayra-btn ayra-btn-primary ayra-checkout-btn"
                id="ayra-proceed-checkout">
                إتمام الطلب
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12" />
                    <polyline points="12 5 19 12 12 19" />
                </svg>
            </a>
        </div>
    </aside>

    <!-- Search Overlay -->
    <div class="ayra-search-overlay" id="ayra-search-overlay">
        <div class="ayra-search-modal">
            <div class="ayra-search-header">
                <div class="ayra-search-input-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="text" id="ayra-search-input" placeholder="ابحث عن منتج..." autocomplete="off"
                        autofocus>
                </div>
                <button class="ayra-search-close" id="ayra-search-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="ayra-search-results" id="ayra-search-results">
                <div class="ayra-search-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"
                        opacity="0.3">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <p>اكتب للبحث عن المنتجات</p>
                </div>
            </div>
        </div>
    </div>

    <main class="ayra-main" id="ayra-main">