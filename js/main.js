/**
 * AYRA Homewear - Main JavaScript
 */
(function ($) {
    'use strict';

    // ─── Cart Drawer ────────────────────────────────────
    const CartDrawer = {
        init() {
            this.$overlay = $('#ayra-cart-overlay');
            this.$drawer = $('#ayra-cart-drawer');
            this.$toggle = $('#ayra-cart-toggle');
            this.$close = $('#ayra-cart-close');

            this.$toggle.on('click', () => this.open());
            this.$close.on('click', () => this.close());
            this.$overlay.on('click', () => this.close());

            // Close on Escape
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });

            // Quantity buttons in cart
            $(document).on('click', '.ayra-qty-btn.plus', function () {
                CartDrawer.updateQty($(this).data('cart-key'), 1);
            });
            $(document).on('click', '.ayra-qty-btn.minus', function () {
                CartDrawer.updateQty($(this).data('cart-key'), -1);
            });

            // Remove item
            $(document).on('click', '.ayra-mini-cart-remove', function () {
                CartDrawer.removeItem($(this).data('cart-key'));
            });
        },

        open() {
            this.$drawer.addClass('open');
            this.$overlay.addClass('open');
            $('body').addClass('cart-open');
        },

        close() {
            this.$drawer.removeClass('open');
            this.$overlay.removeClass('open');
            $('body').removeClass('cart-open');
        },

        updateQty(cartKey, delta) {
            const $item = $(`.ayra-mini-cart-item[data-cart-key="${cartKey}"]`);
            const currentQty = parseInt($item.find('.ayra-qty-value').text());
            const newQty = currentQty + delta;

            if (newQty < 0) return;

            $.ajax({
                url: ayra_ajax.url,
                type: 'POST',
                data: {
                    action: newQty === 0 ? 'ayra_remove_cart_item' : 'ayra_update_cart_qty',
                    nonce: ayra_ajax.nonce,
                    cart_key: cartKey,
                    quantity: newQty,
                },
                success(res) {
                    if (res.success) {
                        CartDrawer.refreshCart(res.data);
                    }
                }
            });
        },

        removeItem(cartKey) {
            const $item = $(`.ayra-mini-cart-item[data-cart-key="${cartKey}"]`);
            $item.addClass('removing');

            setTimeout(() => {
                $.ajax({
                    url: ayra_ajax.url,
                    type: 'POST',
                    data: {
                        action: 'ayra_remove_cart_item',
                        nonce: ayra_ajax.nonce,
                        cart_key: cartKey,
                    },
                    success(res) {
                        if (res.success) {
                            CartDrawer.refreshCart(res.data);
                        }
                    }
                });
            }, 300);
        },

        refreshCart(data) {
            $('.ayra-cart-count').text(data.cart_count);
            $('.ayra-cart-total').html(data.cart_total);
            $('.ayra-cart-drawer-body').html(data.mini_cart);

            // Toggle checkout button
            if (data.cart_count === 0) {
                $('.ayra-cart-drawer-footer').addClass('empty');
            } else {
                $('.ayra-cart-drawer-footer').removeClass('empty');
            }
        }
    };

    // ─── Add to Cart ────────────────────────────────────
    const AddToCart = {
        init() {
            // From product cards (shop page)
            $(document).on('click', '.ayra-add-to-cart-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                AddToCart.add($btn, {
                    product_id: $btn.data('product-id'),
                    variation_id: $btn.data('variation-id'),
                    size: $btn.data('size'),
                    quantity: 1,
                });
            });

            // From single product page
            $('#ayra-add-to-cart-main').on('click', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const $selected = $('.ayra-size-option.selected');
                const $sizeOptions = $('.ayra-size-option');

                if ($sizeOptions.length > 0 && !$selected.length) {
                    AddToCart.showNotice('يرجى اختيار المقاس أولاً', 'error');
                    return;
                }

                AddToCart.add($btn, {
                    product_id: $btn.data('product-id'),
                    variation_id: $selected.length ? $selected.data('variation-id') : 0,
                    size: $selected.length ? $selected.data('size') : '',
                    quantity: parseInt($('#ayra-qty-input').val()) || 1,
                });
            });
        },

        add($btn, data) {
            if ($btn.hasClass('loading')) return;

            $btn.addClass('loading');
            const originalHTML = $btn.html();
            $btn.html('<span class="ayra-spinner"></span>');

            $.ajax({
                url: ayra_ajax.url,
                type: 'POST',
                data: {
                    action: 'ayra_add_to_cart',
                    nonce: ayra_ajax.nonce,
                    ...data,
                },
                success(res) {
                    if (res.success) {
                        CartDrawer.refreshCart(res.data);
                        $btn.html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> تمت الإضافة ✓');
                        $btn.addClass('success');

                        // Open cart drawer
                        setTimeout(() => CartDrawer.open(), 600);

                        setTimeout(() => {
                            $btn.html(originalHTML);
                            $btn.removeClass('success loading');
                        }, 2000);
                    } else {
                        AddToCart.showNotice(res.data.message, 'error');
                        $btn.html(originalHTML);
                        $btn.removeClass('loading');
                    }
                },
                error() {
                    AddToCart.showNotice('حدث خطأ، حاول/ي مرة أخرى', 'error');
                    $btn.html(originalHTML);
                    $btn.removeClass('loading');
                }
            });
        },

        showNotice(message, type) {
            const $notice = $('<div class="ayra-notice ' + type + '">' + message + '</div>');
            $('body').append($notice);
            setTimeout(() => $notice.addClass('show'), 10);
            setTimeout(() => {
                $notice.removeClass('show');
                setTimeout(() => $notice.remove(), 300);
            }, 3000);
        }
    };

    // ─── Single Product ─────────────────────────────────
    const SingleProduct = {
        init() {
            if (!$('#ayra-single-product').length) return;

            // Size selection
            $('.ayra-size-option.available').on('click', function () {
                $('.ayra-size-option').removeClass('selected');
                $(this).addClass('selected');

                // Update price
                const priceHtml = $(this).data('price-html');
                if (priceHtml) {
                    $('#ayra-product-price').html(priceHtml);
                }

                // Update stock info
                const stock = $(this).data('stock');
                const $stockInfo = $('#ayra-stock-info');
                const $stockText = $('#ayra-stock-text');

                if (stock > 0 && stock <= 3) {
                    $stockInfo.show();
                    $stockText.text('كمية محدودة');
                    $stockInfo.addClass('low-stock');
                } else {
                    $stockInfo.hide();
                    $stockInfo.removeClass('low-stock');
                }

            });

            // Quantity buttons
            $('#ayra-qty-plus').on('click', function () {
                const $input = $('#ayra-qty-input');
                const max = parseInt($input.attr('max'));
                let val = parseInt($input.val());
                if (val < max) $input.val(val + 1);
            });

            $('#ayra-qty-minus').on('click', function () {
                const $input = $('#ayra-qty-input');
                let val = parseInt($input.val());
                if (val > 1) $input.val(val - 1);
            });

            // Gallery thumbnails
            $('.ayra-gallery-thumb').on('click', function () {
                const imgUrl = $(this).data('img');
                $('#ayra-gallery-main-img').attr('src', imgUrl);
                $('.ayra-gallery-thumb').removeClass('active');
                $(this).addClass('active');
            });

            // WhatsApp order button
            $('#ayra-whatsapp-order').on('click', function (e) {
                e.preventDefault();
                const productName = $(this).data('product-name');
                const $selected = $('.ayra-size-option.selected');
                const size = $selected.length ? $selected.data('size') : '';
                const qty = $('#ayra-qty-input').val() || 1;

                let message = `مرحبا، أريد طلب:\n`;
                message += `المنتج: ${productName}\n`;
                if (size) message += `المقاس: ${size}\n`;
                message += `الكمية: ${qty}`;

                const url = `https://wa.me/${ayra_ajax.whatsapp}?text=${encodeURIComponent(message)}`;
                window.open(url, '_blank');
            });

            // Pre-select size if from filtered page
            const $preselected = $('.ayra-size-option.selected');
            if ($preselected.length) {
                $preselected.trigger('click');
            }
        }
    };

    // ─── Checkout ───────────────────────────────────────
    // NOTE: Checkout delivery logic is handled inline in form-checkout.php
    // to avoid duplicate AJAX calls and race conditions.
    const Checkout = {
        init() {
            // Intentionally empty — checkout JS is in form-checkout.php
        }
    };

    // ─── Header Scroll ──────────────────────────────────
    const Header = {
        init() {
            let lastScroll = 0;
            const $header = $('#ayra-header');

            $(window).on('scroll', function () {
                const scroll = $(this).scrollTop();

                if (scroll > 50) {
                    $header.addClass('scrolled');
                } else {
                    $header.removeClass('scrolled');
                }

                lastScroll = scroll;
            });

            // Mobile menu toggle
            $('#ayra-menu-toggle').on('click', function () {
                $(this).toggleClass('active');
                $('#ayra-nav').toggleClass('open');
            });
        }
    };

    // ─── Size Selector Animations (Front Page) ──────────
    const SizeSelector = {
        init() {
            if (!$('#ayra-size-grid').length) return;

            // Stagger animation on load
            $('.ayra-size-card').each(function (i) {
                $(this).css('animation-delay', (i * 0.05) + 's');
                $(this).addClass('animate-in');
            });

            // Hero scroll indicator click
            $('.ayra-hero-scroll').on('click', function () {
                const $target = $('#ayra-size-section');
                if ($target.length) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 70
                    }, 800);
                }
            });
        }
    };

    // ─── Mobile Filter Toggle ────────────────────────────
    const FilterToggle = {
        init() {
            const $toggle = $('#ayra-filter-toggle');
            const $filterBar = $('#ayra-filter-bar');
            if (!$toggle.length) return;

            $toggle.on('click', function () {
                $filterBar.toggleClass('filter-open');
                const isOpen = $filterBar.hasClass('filter-open');
                $toggle.attr('aria-expanded', isOpen);
            });

            // Auto-open if a filter is active (so user sees what's selected)
            if ($filterBar.find('.ayra-filter-pill.active[data-size]').not('[data-size=""]').length) {
                $filterBar.addClass('filter-open');
                $toggle.attr('aria-expanded', 'true');
            }
        }
    };

    // ─── Search Overlay ────────────────────────────────
    // ─── Search Overlay ────────────────────────────────
    const Search = {
        timer: null,
        pollTimer: null,

        doSearch(q, $results) {
            if (q.length < 2) {
                $results.html('<div class="ayra-search-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><p>اكتب للبحث عن المنتجات</p></div>');
                return;
            }
            $results.html('<div class="ayra-search-loading"><span class="ayra-spinner"></span></div>');
            clearTimeout(Search.timer);
            Search.timer = setTimeout(() => {
                $.ajax({
                    url: ayra_ajax.url,
                    type: 'POST',
                    data: { action: 'ayra_search_products', query: q },
                    success(res) {
                        if (!res.success || !res.data.products.length) {
                            $results.html('<div class="ayra-search-empty"><p>لا توجد نتائج لـ "' + q + '"</p></div>');
                            return;
                        }
                        let html = '<div class="ayra-search-grid">';
                        res.data.products.forEach(p => {
                            html += `<a href="${p.permalink}" class="ayra-search-item">`;
                            html += `<div class="ayra-search-item-img">${p.image ? '<img src="' + p.image + '" alt="' + p.name + '">' : ''}</div>`;
                            html += `<div class="ayra-search-item-info">`;
                            html += `<span class="ayra-search-item-name">${p.name}</span>`;
                            html += `<span class="ayra-search-item-price">${p.price}</span>`;
                            html += `</div></a>`;
                        });
                        html += '</div>';
                        $results.html(html);
                    }
                });
            }, 300);
        },

        startPoll($input, $results) {
            clearInterval(Search.pollTimer);
            let lastVal = $input.val();
            Search.pollTimer = setInterval(function () {
                const val = $input.val().trim();
                if (val !== lastVal) {
                    lastVal = val;
                    Search.doSearch(val, $results);
                }
            }, 200);
        },

        stopPoll() {
            clearInterval(Search.pollTimer);
        },

        init() {
            const $overlay = $('#ayra-search-overlay');
            const $input = $('#ayra-search-input');
            const $results = $('#ayra-search-results');

            // Open
            $('#ayra-search-toggle').on('click', () => {
                $overlay.addClass('open');
                $('body').addClass('search-open');
                setTimeout(() => $input.focus(), 300);
            });

            // Close
            const close = () => {
                $overlay.removeClass('open');
                $('body').removeClass('search-open');
                $input.val('');
                $results.html('<div class="ayra-search-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><p>اكتب للبحث عن المنتجات</p></div>');
            };
            $('#ayra-search-close').on('click', close);
            $overlay.on('click', function (e) {
                if (e.target === this) close();
            });
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $overlay.hasClass('open')) close();
            });

            // Search input bindings with multi-event listener for mobile keyboards
            $input.on('input keyup keypress change compositionupdate compositionend', function () {
                Search.doSearch($(this).val().trim(), $results);
            });

            // Start/stop polling when focused on mobile
            $input.on('focus', function () {
                Search.startPoll($input, $results);
            });
            $input.on('blur', function () {
                setTimeout(() => Search.stopPoll(), 400);
            });
        }
    };

    // ─── Hero Search Autocomplete (Front Page) ────────────
    const HeroSearch = {
        timer: null,
        pollTimer: null,

        doSearch(q) {
            const $dropdown = $('#ayra-hero-search-dropdown');

            if (q.length < 2) {
                $dropdown.removeClass('visible').html('');
                return;
            }

            $dropdown.addClass('visible').html('<div class="search-loading"><span class="ayra-spinner"></span></div>');

            clearTimeout(HeroSearch.timer);
            HeroSearch.timer = setTimeout(() => {
                $.ajax({
                    url: ayra_ajax.url,
                    type: 'POST',
                    data: { action: 'ayra_search_products', query: q },
                    success(res) {
                        if (!res.success || !res.data.products.length) {
                            $dropdown.html('<div class="search-empty">لا توجد نتائج لـ "' + q + '"</div>');
                            return;
                        }
                        let html = '';
                        res.data.products.forEach(p => {
                            html += '<a href="' + p.permalink + '" class="ayra-hero-suggest-item">';
                            html += p.image ? '<img class="ayra-hero-suggest-img" src="' + p.image + '" alt="' + p.name + '" />' : '<div class="ayra-hero-suggest-img"></div>';
                            html += '<div class="ayra-hero-suggest-info">';
                            html += '<span class="ayra-hero-suggest-name">' + p.name + '</span>';
                            html += '<span class="ayra-hero-suggest-price">' + p.price + '</span>';
                            html += '</div></a>';
                        });
                        $dropdown.html(html);
                    },
                    error() {
                        $dropdown.html('<div class="search-empty">حدث خطأ في البحث</div>');
                    }
                });
            }, 300);
        },

        // Poll the input value — catches mobile keyboard input that doesn't fire events
        startPoll($input) {
            clearInterval(HeroSearch.pollTimer);
            let lastVal = $input.val();
            HeroSearch.pollTimer = setInterval(function () {
                const val = $input.val().trim();
                if (val !== lastVal) {
                    lastVal = val;
                    HeroSearch.doSearch(val);
                }
            }, 200);
        },

        stopPoll() {
            clearInterval(HeroSearch.pollTimer);
        },

        init() {
            const $input = $('#ayra-hero-search-input');
            const $dropdown = $('#ayra-hero-search-dropdown');
            if (!$input.length || !$dropdown.length) return;

            // Direct tap/focus redirection for mobile devices
            $input.on('focus click touchstart', function (e) {
                const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                if (isMobile) {
                    e.preventDefault();
                    $input.blur(); // Remove focus from the hero input immediately
                    
                    const $overlay = $('#ayra-search-overlay');
                    const $overlayInput = $('#ayra-search-input');
                    if ($overlay.length && $overlayInput.length) {
                        $overlay.addClass('open');
                        $('body').addClass('search-open');
                        
                        // Copy search term if they started typing something
                        $overlayInput.val($input.val());
                        
                        setTimeout(() => {
                            $overlayInput.focus();
                            // Trigger input event to run search immediately if there is a value
                            const val = $overlayInput.val().trim();
                            if (val.length >= 2) {
                                $overlayInput.trigger('input');
                            }
                        }, 100);
                    }
                }
            });

            // Fire on ALL possible mobile/desktop input events
            $input.on('input keyup keypress change compositionupdate compositionend', function () {
                const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                if (!isMobile) {
                    HeroSearch.doSearch($(this).val().trim());
                }
            });

            // On focus: start polling to catch any mobile keyboard event that slips through
            $input.on('focus', function () {
                const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                if (!isMobile) {
                    HeroSearch.startPoll($input);
                    if ($dropdown.children().length) {
                        $dropdown.addClass('visible');
                    }
                }
            });

            // On blur: stop polling
            $input.on('blur', function () {
                setTimeout(function () { HeroSearch.stopPoll(); }, 400);
            });

            // Enter — submit form as real product search
            $input.on('keydown', function (e) {
                if (e.key === 'Enter') {
                    $dropdown.removeClass('visible');
                    HeroSearch.stopPoll();
                    $(this).closest('form').off('submit').submit();
                } else if (e.key === 'Escape') {
                    $dropdown.removeClass('visible').html('');
                    HeroSearch.stopPoll();
                    $input.val('').blur();
                }
            });

            // Close on click/touch outside
            $(document).on('click touchstart', function (e) {
                if (!$(e.target).closest('.ayra-hero-search-container').length) {
                    $dropdown.removeClass('visible');
                    HeroSearch.stopPoll();
                }
            });
        }
    };

    // ─── Front page: category / packs browser ──────────
    const HeroCategories = {
        init() {
            $(document).on('click', '.ayra-hero-cat.has-children > .ayra-hero-cat-btn', function () {
                const $wrap = $(this).closest('.ayra-hero-cat');
                $('.ayra-hero-cat.has-children').not($wrap).removeClass('open');
                $wrap.toggleClass('open');
            });
        }
    };

    // ─── Initialize All ─────────────────────────────────
    $(document).ready(function () {
        CartDrawer.init();
        AddToCart.init();
        SingleProduct.init();
        Checkout.init();
        Header.init();
        SizeSelector.init();
        FilterToggle.init();
        Search.init();
        HeroSearch.init();
        HeroCategories.init();
    });

})(jQuery);
