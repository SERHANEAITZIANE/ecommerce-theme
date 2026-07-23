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
                const $btn = $(this);
                const variationId = $btn.data('variation-id');

                // If no size/variation selected or button is "select-options" ("اختيار المقاس")
                if ($btn.hasClass('select-options') || !variationId) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $sizeFilter = $('#ayra-size-filter');
                    if ($sizeFilter.length) {
                        const headerOffset = 110;
                        const elementPosition = $sizeFilter.offset().top;
                        const offsetPosition = elementPosition - headerOffset;
                        window.scrollTo({
                            top: Math.max(0, offsetPosition),
                            behavior: 'smooth'
                        });
                        $sizeFilter.addClass('ayra-pulse-focus');
                        setTimeout(function () {
                            $sizeFilter.removeClass('ayra-pulse-focus');
                        }, 2500);
                        return;
                    }
                    // Fallback to product page if not on shop page
                    const href = $btn.attr('href');
                    if (href) window.location.href = href;
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                AddToCart.add($btn, {
                    product_id: $btn.data('product-id'),
                    variation_id: variationId,
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
                    const $selector = $('.ayra-size-selector');
                    if ($selector.length) {
                        const headerOffset = 120;
                        const elementPosition = $selector.offset().top;
                        const offsetPosition = elementPosition - headerOffset;
                        window.scrollTo({
                            top: Math.max(0, offsetPosition),
                            behavior: 'smooth'
                        });
                        $selector.addClass('ayra-pulse-focus');
                        setTimeout(function () {
                            $selector.removeClass('ayra-pulse-focus');
                        }, 2500);
                    }
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

            // ─── Image Carousel (Swipeable) ───────────────
            const $track = $('#ayra-carousel-track');
            if ($track.length) {
                const $dots = $('.ayra-carousel-dot');
                const $thumbs = $('.ayra-carousel-thumb');
                let scrollTimeout;

                // Scroll → update active dot + thumb
                $track.on('scroll', function () {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        const scrollLeft = $track.scrollLeft();
                        const slideWidth = $track[0].clientWidth;
                        const idx = Math.round(scrollLeft / slideWidth);
                        $dots.removeClass('active').eq(idx).addClass('active');
                        $thumbs.removeClass('active').eq(idx).addClass('active');
                        // Scroll thumb into view
                        const $activeThumb = $thumbs.eq(idx);
                        if ($activeThumb.length) {
                            $activeThumb[0].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                        }
                    }, 50);
                });

                // Dot click → scroll to slide
                $dots.on('click', function () {
                    const idx = $(this).data('slide');
                    $track[0].scrollTo({ left: idx * $track[0].clientWidth, behavior: 'smooth' });
                });

                // Thumb click → scroll to slide
                $thumbs.on('click', function () {
                    const idx = $(this).data('slide');
                    $track[0].scrollTo({ left: idx * $track[0].clientWidth, behavior: 'smooth' });
                });
            }

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

    // ─── Reviews System ─────────────────────────────────
    const Reviews = {
        mediaRecorder: null,
        audioChunks: [],
        audioBlob: null,
        imageFiles: [],
        timerInterval: null,
        recordStartTime: 0,

        init() {
            if (!$('#ayra-reviews').length) return;

            // Star rating input
            $('#ayra-star-input button').on('click', function () {
                const rating = $(this).data('rating');
                $('#ayra-review-rating').val(rating);
                $('#ayra-star-input button').each(function () {
                    $(this).toggleClass('active', $(this).data('rating') <= rating);
                });
            });

            // Voice recording
            $('#ayra-record-btn').on('click', () => this.toggleRecording());
            $('#ayra-record-remove').on('click', () => this.removeRecording());

            // Image upload
            $('#ayra-add-images-btn').on('click', () => $('#ayra-review-images-file').trigger('click'));
            $('#ayra-review-images-file').on('change', (e) => this.handleImageSelect(e));
            $(document).on('click', '.ayra-img-preview-remove', function () {
                const idx = $(this).data('idx');
                Reviews.removeImage(idx);
            });

            // Submit review
            $('#ayra-submit-review').on('click', () => this.submitReview());

            // Load more
            $('#ayra-load-more-reviews').on('click', function () {
                Reviews.loadMore($(this));
            });

            // Click to enlarge review screenshots (Lightbox Modal)
            $(document).on('click', '.ayra-review-img-thumb img, .ayra-review-images-gallery img', function () {
                const imgSrc = $(this).attr('src');
                if (!imgSrc) return;

                let $modal = $('#ayra-image-lightbox-modal');
                if (!$modal.length) {
                    $modal = $(`
                        <div id="ayra-image-lightbox-modal" class="ayra-lightbox-overlay">
                            <div class="ayra-lightbox-content">
                                <button type="button" class="ayra-lightbox-close">&times;</button>
                                <img src="" alt="صورة المراجعة مكبرة" class="ayra-lightbox-img">
                            </div>
                        </div>
                    `);
                    $('body').append($modal);
                    $modal.on('click', function (e) {
                        if ($(e.target).hasClass('ayra-lightbox-overlay') || $(e.target).hasClass('ayra-lightbox-close')) {
                            $modal.removeClass('open');
                        }
                    });
                }
                $modal.find('.ayra-lightbox-img').attr('src', imgSrc);
                $modal.addClass('open');
            });
        },

        handleImageSelect(e) {
            const files = Array.from(e.target.files);
            const remaining = 5 - this.imageFiles.length;
            const toAdd = files.slice(0, remaining);
            toAdd.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                if (file.size > 5 * 1024 * 1024) return;
                this.imageFiles.push(file);
            });
            this.renderImagePreviews();
            // Reset file input so same file can be selected again
            e.target.value = '';
        },

        removeImage(idx) {
            this.imageFiles.splice(idx, 1);
            this.renderImagePreviews();
        },

        renderImagePreviews() {
            const $container = $('#ayra-image-previews');
            $container.empty();
            this.imageFiles.forEach((file, idx) => {
                const url = URL.createObjectURL(file);
                $container.append(
                    `<div class="ayra-img-preview-item">
                        <img src="${url}" alt="">
                        <button type="button" class="ayra-img-preview-remove" data-idx="${idx}">✕</button>
                    </div>`
                );
            });
            // Show/hide add button based on count
            if (this.imageFiles.length >= 5) {
                $('#ayra-add-images-btn').hide();
            } else {
                $('#ayra-add-images-btn').show();
            }
        },

        toggleRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                this.stopRecording();
            } else {
                this.startRecording();
            }
        },

        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) this.audioChunks.push(e.data);
                };

                this.mediaRecorder.onstop = () => {
                    this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    const url = URL.createObjectURL(this.audioBlob);
                    $('#ayra-record-audio').attr('src', url);
                    $('#ayra-record-preview').show();
                    stream.getTracks().forEach(t => t.stop());
                };

                this.mediaRecorder.start();
                $('#ayra-record-btn').addClass('recording').find('span').text('إيقاف');
                $('#ayra-record-timer').show();
                this.recordStartTime = Date.now();
                this.timerInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - this.recordStartTime) / 1000);
                    const m = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const s = String(elapsed % 60).padStart(2, '0');
                    $('#ayra-record-timer').text(`${m}:${s}`);
                }, 500);
            } catch (err) {
                alert('لم نتمكن من الوصول للميكروفون. يرجى السماح بالوصول.');
            }
        },

        stopRecording() {
            if (this.mediaRecorder) this.mediaRecorder.stop();
            clearInterval(this.timerInterval);
            $('#ayra-record-btn').removeClass('recording').find('span').text('تسجيل');
            $('#ayra-record-timer').hide();
        },

        removeRecording() {
            this.audioBlob = null;
            this.audioChunks = [];
            $('#ayra-record-preview').hide();
            $('#ayra-record-audio').attr('src', '');
        },

        submitReview() {
            const name = $('#ayra-review-name').val().trim();
            const text = $('#ayra-review-text').val().trim();
            const rating = $('#ayra-review-rating').val();
            const productId = $('#ayra-reviews').data('product-id');

            if (!name) { alert('يرجى إدخال اسمك'); return; }

            const formData = new FormData();
            formData.append('action', 'ayra_submit_review');
            formData.append('author_name', name);
            formData.append('review_text', text);
            formData.append('rating', rating);
            formData.append('product_id', productId);
            if (this.audioBlob) {
                formData.append('audio', this.audioBlob, 'review.webm');
            }
            // Append image files
            this.imageFiles.forEach(file => {
                formData.append('review_images[]', file);
            });

            const $btn = $('#ayra-submit-review');
            $btn.prop('disabled', true).text('جاري الإرسال...');

            $.ajax({
                url: ayra_ajax.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (res) => {
                    if (res.success) {
                        // Add new review to list
                        const r = res.data.review;
                        const initial = r.author.charAt(0);
                        let starsHtml = '';
                        for (let i = 1; i <= 5; i++) {
                            starsHtml += `<svg viewBox="0 0 24 24" class="${i > r.rating ? 'empty' : ''}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
                        }
                        let audioHtml = '';
                        if (r.audio_url) {
                            audioHtml = `<div class="ayra-review-audio">
                                <div class="ayra-review-audio-label"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg> تسجيل صوتي</div>
                                <audio controls src="${r.audio_url}"></audio>
                            </div>`;
                        }
                        let imagesHtml = '';
                        if (r.image_urls && r.image_urls.length) {
                            imagesHtml = '<div class="ayra-review-images-gallery">';
                            r.image_urls.forEach(url => {
                                imagesHtml += `<div class="ayra-review-img-thumb"><img src="${url}" alt="صورة المراجعة"></div>`;
                            });
                            imagesHtml += '</div>';
                        }
                        const html = `<div class="ayra-review-card" style="animation:fadeIn 0.4s">
                            <div class="ayra-review-card-header">
                                <div class="ayra-review-card-author">
                                    <div class="ayra-review-avatar">${initial}</div>
                                    <div><div class="ayra-review-name">${r.author}</div><div class="ayra-review-date">${r.date}</div></div>
                                </div>
                                <div class="ayra-review-stars">${starsHtml}</div>
                            </div>
                            ${r.text ? `<div class="ayra-review-card-body">${r.text}</div>` : ''}
                            ${imagesHtml}
                            ${audioHtml}
                        </div>`;
                        $('.ayra-no-reviews').remove();
                        $('#ayra-reviews-list').prepend(html);

                        // Reset form
                        $('#ayra-review-name').val('');
                        $('#ayra-review-text').val('');
                        this.removeRecording();
                        this.imageFiles = [];
                        this.renderImagePreviews();
                        AddToCart.showNotice(res.data.message, 'success');
                    } else {
                        alert(res.data.message || 'حدث خطأ');
                    }
                },
                error: () => alert('حدث خطأ في الاتصال'),
                complete: () => $btn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> إرسال الرأي'),
            });
        },

        loadMore($btn) {
            const page = $btn.data('page');
            const productId = $('#ayra-reviews').data('product-id');
            $btn.text('جاري التحميل...');

            $.post(ayra_ajax.url, {
                action: 'ayra_get_reviews',
                product_id: productId,
                page: page,
            }, function (res) {
                if (res.success && res.data.reviews.length) {
                    res.data.reviews.forEach(function (r) {
                        const initial = r.author.charAt(0);
                        let starsHtml = '';
                        for (let i = 1; i <= 5; i++) {
                            starsHtml += `<svg viewBox="0 0 24 24" class="${i > r.rating ? 'empty' : ''}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
                        }
                        let audioHtml = r.audio_url ? `<div class="ayra-review-audio"><div class="ayra-review-audio-label"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg> تسجيل صوتي</div><audio controls src="${r.audio_url}"></audio></div>` : '';
                        let imagesHtml = '';
                        if (r.image_urls && r.image_urls.length) {
                            imagesHtml = '<div class="ayra-review-images-gallery">';
                            r.image_urls.forEach(function(url) {
                                imagesHtml += '<div class="ayra-review-img-thumb"><img src="' + url + '" alt="صورة المراجعة"></div>';
                            });
                            imagesHtml += '</div>';
                        }
                        const html = `<div class="ayra-review-card">
                            <div class="ayra-review-card-header">
                                <div class="ayra-review-card-author"><div class="ayra-review-avatar">${initial}</div><div><div class="ayra-review-name">${r.author}</div><div class="ayra-review-date">${r.date}</div></div></div>
                                <div class="ayra-review-stars">${starsHtml}</div>
                            </div>
                            ${r.text ? `<div class="ayra-review-card-body">${r.text}</div>` : ''}
                            ${imagesHtml}
                            ${audioHtml}
                        </div>`;
                        $('#ayra-reviews-list').append(html);
                    });

                    if (res.data.has_more) {
                        $btn.data('page', page + 1).text('عرض المزيد من الآراء');
                    } else {
                        $btn.remove();
                    }
                } else {
                    $btn.remove();
                }
            });
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

    // ─── Sub-category Size Picker Modal ──────────────────
    const SubcatSizePicker = {
        pendingUrl: '',

        init() {
            const $overlay = $('#ayra-sizepick-overlay');
            if (!$overlay.length) return;

            // Focus client on size selection when visiting shop without a specific size selected
            const urlParams = new URLSearchParams(window.location.search);
            const hasSize = urlParams.has('filter_size') && urlParams.get('filter_size');
            const isShopPage = $('#ayra-shop').length > 0;
            if (isShopPage && !hasSize) {
                const $sizeFilter = $('#ayra-size-filter');
                if ($sizeFilter.length) {
                    setTimeout(function() {
                        const headerOffset = 110;
                        const elementPosition = $sizeFilter.offset().top;
                        const offsetPosition = elementPosition - headerOffset;
                        window.scrollTo({
                            top: Math.max(0, offsetPosition),
                            behavior: 'smooth'
                        });
                        $sizeFilter.addClass('ayra-pulse-focus');
                        setTimeout(function() {
                            $sizeFilter.removeClass('ayra-pulse-focus');
                        }, 2500);
                    }, 300);
                }
            }

            // Intercept sub-category pill clicks that have the data attribute
            $(document).on('click', 'a[data-subcat-sizepick]', function (e) {
                e.preventDefault();
                const baseUrl = $(this).attr('href');
                const subcatName = $(this).data('subcat-name');
                SubcatSizePicker.pendingUrl = baseUrl;

                // Update modal title
                $('#ayra-sizepick-title').text('اختاري المقاس — ' + subcatName);

                // Set skip link to navigate without size
                $('#ayra-sizepick-skip').attr('href', baseUrl);

                // Open modal
                $overlay.addClass('open');
                $('body').css('overflow', 'hidden');
            });

            // Size button click — navigate with size
            $(document).on('click', '.ayra-sizepick-btn', function () {
                const size = $(this).data('size');
                let url = SubcatSizePicker.pendingUrl;
                // Add filter_size parameter
                if (url.indexOf('?') !== -1) {
                    url += '&filter_size=' + encodeURIComponent(size);
                } else {
                    url += '?filter_size=' + encodeURIComponent(size);
                }
                window.location.href = url;
            });

            // Skip link — navigate without size
            $(document).on('click', '#ayra-sizepick-skip', function (e) {
                // href is already set, let default navigation happen
                SubcatSizePicker.close();
            });

            // Close modal
            $('#ayra-sizepick-close').on('click', function () {
                SubcatSizePicker.close();
            });
            $overlay.on('click', function (e) {
                if (e.target === this) SubcatSizePicker.close();
            });
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $overlay.hasClass('open')) {
                    SubcatSizePicker.close();
                }
            });
        },

        close() {
            $('#ayra-sizepick-overlay').removeClass('open');
            $('body').css('overflow', '');
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
        Reviews.init();
        SubcatSizePicker.init();
    });

})(jQuery);
