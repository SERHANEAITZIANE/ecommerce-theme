<?php
/**
 * AYRA Homewear — Reviews System
 * 
 * Custom review system with voice recording, text reviews, and image uploads.
 * Uses custom post type 'ayra_review'.
 */
defined('ABSPATH') || exit;

// ─── Custom Post Type ────────────────────────────────────
function ayra_register_review_cpt() {
    register_post_type('ayra_review', [
        'labels' => [
            'name'          => 'آراء العملاء',
            'singular_name' => 'رأي',
            'add_new'       => 'إضافة رأي',
            'add_new_item'  => 'إضافة رأي جديد',
            'edit_item'     => 'تعديل الرأي',
            'all_items'     => 'جميع الآراء',
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-star-filled',
        'menu_position'=> 57,
        'supports'     => ['title', 'editor', 'custom-fields'],
        'capability_type' => 'post',
    ]);
}
add_action('init', 'ayra_register_review_cpt');

// ─── Meta Boxes for Review Fields ────────────────────────
function ayra_review_meta_boxes() {
    add_meta_box('ayra_review_details', 'تفاصيل الرأي', 'ayra_review_meta_box_html', 'ayra_review', 'normal', 'high');
}
add_action('add_meta_boxes', 'ayra_review_meta_boxes');

function ayra_review_meta_box_html($post) {
    $author_name = get_post_meta($post->ID, '_ayra_review_author', true);
    $rating      = get_post_meta($post->ID, '_ayra_review_rating', true) ?: 5;
    $product_id  = get_post_meta($post->ID, '_ayra_review_product_id', true);
    $audio_url   = get_post_meta($post->ID, '_ayra_review_audio', true);
    $image_ids   = get_post_meta($post->ID, '_ayra_review_images', true);
    if (!is_array($image_ids)) $image_ids = [];
    wp_nonce_field('ayra_review_save', 'ayra_review_nonce');
    ?>
    <table class="form-table">
        <tr>
            <th><label>اسم العميل</label></th>
            <td><input type="text" name="ayra_review_author" value="<?php echo esc_attr($author_name); ?>" style="width:300px;"></td>
        </tr>
        <tr>
            <th><label>التقييم (1-5)</label></th>
            <td><input type="number" name="ayra_review_rating" value="<?php echo esc_attr($rating); ?>" min="1" max="5" style="width:80px;"></td>
        </tr>
        <tr>
            <th><label>المنتج (ID)</label></th>
            <td>
                <input type="number" name="ayra_review_product_id" value="<?php echo esc_attr($product_id); ?>" style="width:120px;">
                <p class="description">اتركه فارغاً للرأي العام (يظهر في جميع المنتجات)</p>
            </td>
        </tr>
        <tr>
            <th><label>صور المراجعة</label></th>
            <td>
                <div id="ayra-review-images-preview" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                    <?php foreach ($image_ids as $img_id):
                        $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                        if ($img_url):
                    ?>
                    <div style="position:relative; display:inline-block;">
                        <img src="<?php echo esc_url($img_url); ?>" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #ddd;">
                        <button type="button" class="ayra-remove-review-img" data-id="<?php echo $img_id; ?>" style="position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:12px; line-height:1;">✕</button>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <input type="hidden" name="ayra_review_images" id="ayra-review-images-input" value="<?php echo esc_attr(implode(',', $image_ids)); ?>">
                <button type="button" class="button" id="ayra-add-review-images">📷 إضافة صور</button>
                <p class="description">اختر حتى 5 صور للمراجعة</p>
            </td>
        </tr>
        <tr>
            <th><label>تسجيل صوتي</label></th>
            <td>
                <?php if ($audio_url): ?>
                    <audio controls src="<?php echo esc_url($audio_url); ?>" style="margin-bottom:8px;"></audio><br>
                    <input type="text" name="ayra_review_audio" value="<?php echo esc_url($audio_url); ?>" style="width:100%;">
                <?php else: ?>
                    <input type="text" name="ayra_review_audio" value="" style="width:100%;" placeholder="رابط الملف الصوتي (اختياري)">
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <script>
    jQuery(function($){
        // WordPress Media Library picker for images
        $('#ayra-add-review-images').on('click', function(e){
            e.preventDefault();
            var frame = wp.media({
                title: 'اختيار صور المراجعة',
                button: { text: 'إضافة الصور' },
                multiple: true,
                library: { type: 'image' }
            });
            frame.on('select', function(){
                var selection = frame.state().get('selection');
                var currentIds = $('#ayra-review-images-input').val().split(',').filter(function(v){ return v; });
                selection.each(function(attachment){
                    var a = attachment.toJSON();
                    if (currentIds.length >= 5) return;
                    if (currentIds.indexOf(String(a.id)) !== -1) return;
                    currentIds.push(String(a.id));
                    var thumb = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
                    $('#ayra-review-images-preview').append(
                        '<div style="position:relative; display:inline-block;">' +
                        '<img src="' + thumb + '" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #ddd;">' +
                        '<button type="button" class="ayra-remove-review-img" data-id="' + a.id + '" style="position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:12px; line-height:1;">✕</button>' +
                        '</div>'
                    );
                });
                $('#ayra-review-images-input').val(currentIds.join(','));
            });
            frame.open();
        });

        // Remove image
        $(document).on('click', '.ayra-remove-review-img', function(){
            var id = String($(this).data('id'));
            var currentIds = $('#ayra-review-images-input').val().split(',').filter(function(v){ return v && v !== id; });
            $('#ayra-review-images-input').val(currentIds.join(','));
            $(this).closest('div').remove();
        });
    });
    </script>
    <?php
}

function ayra_review_save_meta($post_id) {
    if (!isset($_POST['ayra_review_nonce']) || !wp_verify_nonce($_POST['ayra_review_nonce'], 'ayra_review_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['ayra_review_author']))     update_post_meta($post_id, '_ayra_review_author', sanitize_text_field($_POST['ayra_review_author']));
    if (isset($_POST['ayra_review_rating']))     update_post_meta($post_id, '_ayra_review_rating', intval($_POST['ayra_review_rating']));
    if (isset($_POST['ayra_review_product_id'])) update_post_meta($post_id, '_ayra_review_product_id', intval($_POST['ayra_review_product_id']));
    if (isset($_POST['ayra_review_audio']))      update_post_meta($post_id, '_ayra_review_audio', esc_url_raw($_POST['ayra_review_audio']));

    // Save image IDs
    if (isset($_POST['ayra_review_images'])) {
        $raw = sanitize_text_field($_POST['ayra_review_images']);
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        update_post_meta($post_id, '_ayra_review_images', $ids);
    }
}
add_action('save_post_ayra_review', 'ayra_review_save_meta');

// ─── Enqueue media library on review edit screens ────────
function ayra_review_admin_scripts($hook) {
    global $post_type;
    if ($post_type === 'ayra_review' && in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'ayra_review_admin_scripts');

// ─── Admin Columns ──────────────────────────────────────
function ayra_review_admin_columns($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        $new[$key] = $val;
        if ($key === 'title') {
            $new['ayra_author']  = 'العميل';
            $new['ayra_rating']  = 'التقييم';
            $new['ayra_product'] = 'المنتج';
            $new['ayra_images']  = 'صور';
            $new['ayra_audio']   = 'صوت';
        }
    }
    return $new;
}
add_filter('manage_ayra_review_posts_columns', 'ayra_review_admin_columns');

function ayra_review_admin_column_data($column, $post_id) {
    switch ($column) {
        case 'ayra_author':
            echo esc_html(get_post_meta($post_id, '_ayra_review_author', true) ?: '—');
            break;
        case 'ayra_rating':
            $r = intval(get_post_meta($post_id, '_ayra_review_rating', true));
            echo str_repeat('⭐', $r);
            break;
        case 'ayra_product':
            $pid = intval(get_post_meta($post_id, '_ayra_review_product_id', true));
            if ($pid) {
                $p = get_the_title($pid);
                echo $p ? esc_html($p) : "#{$pid}";
            } else {
                echo '<em>عام</em>';
            }
            break;
        case 'ayra_images':
            $imgs = get_post_meta($post_id, '_ayra_review_images', true);
            echo is_array($imgs) && !empty($imgs) ? '📷 ' . count($imgs) : '—';
            break;
        case 'ayra_audio':
            $audio = get_post_meta($post_id, '_ayra_review_audio', true);
            echo $audio ? '🎤' : '—';
            break;
    }
}
add_action('manage_ayra_review_posts_custom_column', 'ayra_review_admin_column_data', 10, 2);

// ─── AJAX: Get Reviews (paginated) ──────────────────────
add_action('wp_ajax_ayra_get_reviews', 'ayra_get_reviews_ajax');
add_action('wp_ajax_nopriv_ayra_get_reviews', 'ayra_get_reviews_ajax');

function ayra_get_reviews_ajax() {
    $product_id = intval($_POST['product_id'] ?? 0);
    $page       = max(1, intval($_POST['page'] ?? 1));
    $per_page   = 5;

    $args = [
        'post_type'      => 'ayra_review',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($args);
    $reviews = [];

    foreach ($query->posts as $post) {
        // Get image URLs
        $image_ids = get_post_meta($post->ID, '_ayra_review_images', true);
        $image_urls = [];
        if (is_array($image_ids)) {
            foreach ($image_ids as $img_id) {
                $url = wp_get_attachment_image_url($img_id, 'full');
                if ($url) $image_urls[] = $url;
            }
        }

        $reviews[] = [
            'id'         => $post->ID,
            'author'     => get_post_meta($post->ID, '_ayra_review_author', true) ?: 'زبون/ة',
            'rating'     => intval(get_post_meta($post->ID, '_ayra_review_rating', true)) ?: 5,
            'text'       => wp_strip_all_tags($post->post_content),
            'audio_url'  => get_post_meta($post->ID, '_ayra_review_audio', true) ?: '',
            'image_urls' => $image_urls,
            'date'       => get_the_date('j M Y', $post->ID),
        ];
    }

    wp_send_json_success([
        'reviews'  => $reviews,
        'has_more' => ($query->max_num_pages > $page),
        'total'    => $query->found_posts,
    ]);
}

// ─── AJAX: Submit Review ────────────────────────────────
add_action('wp_ajax_ayra_submit_review', 'ayra_submit_review_ajax');
// Removed nopriv hook — only logged-in admins can submit reviews

function ayra_submit_review_ajax() {
    // Only admins can submit reviews
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'غير مسموح — فقط المدير يمكنه إضافة آراء']);
    }

    $author     = sanitize_text_field($_POST['author_name'] ?? '');
    $text       = sanitize_textarea_field($_POST['review_text'] ?? '');
    $rating     = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $product_id = intval($_POST['product_id'] ?? 0);

    if (empty($author)) {
        wp_send_json_error(['message' => 'يرجى ملء الاسم']);
    }

    // Handle audio file upload
    $audio_url = '';
    if (!empty($_FILES['audio']) && $_FILES['audio']['size'] > 0) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $allowed = ['audio/webm', 'audio/ogg', 'audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/mp4'];
        if (!in_array($_FILES['audio']['type'], $allowed)) {
            wp_send_json_error(['message' => 'نوع الملف غير مدعوم']);
        }

        if ($_FILES['audio']['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)']);
        }

        $upload = wp_handle_upload($_FILES['audio'], ['test_form' => false]);
        if (!empty($upload['url'])) {
            $audio_url = $upload['url'];
        }
    }

    // Handle image file uploads (multiple)
    $image_ids = [];
    $image_urls = [];
    if (!empty($_FILES['review_images'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $files = $_FILES['review_images'];
        $file_count = is_array($files['name']) ? count($files['name']) : 0;
        $max_images = min($file_count, 5);

        for ($i = 0; $i < $max_images; $i++) {
            if (empty($files['name'][$i]) || $files['size'][$i] <= 0) continue;

            $allowed_img = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($files['type'][$i], $allowed_img)) continue;

            if ($files['size'][$i] > 5 * 1024 * 1024) continue;

            $single_file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $upload = wp_handle_upload($single_file, ['test_form' => false]);
            if (!empty($upload['url']) && !empty($upload['file'])) {
                $attachment = [
                    'post_mime_type' => $upload['type'],
                    'post_title'     => sanitize_file_name(basename($upload['file'])),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ];
                $attach_id = wp_insert_attachment($attachment, $upload['file']);
                if ($attach_id && !is_wp_error($attach_id)) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    $image_ids[] = $attach_id;
                    $medium_url = wp_get_attachment_image_url($attach_id, 'medium');
                    $image_urls[] = $medium_url ?: $upload['url'];
                }
            }
        }
    }

    // Create the review post
    $post_id = wp_insert_post([
        'post_type'    => 'ayra_review',
        'post_title'   => 'رأي من ' . $author,
        'post_content' => $text,
        'post_status'  => 'publish',
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'حدث خطأ، حاول/ي مرة أخرى']);
    }

    update_post_meta($post_id, '_ayra_review_author', $author);
    update_post_meta($post_id, '_ayra_review_rating', $rating);
    update_post_meta($post_id, '_ayra_review_product_id', $product_id);
    if ($audio_url) {
        update_post_meta($post_id, '_ayra_review_audio', $audio_url);
    }
    if (!empty($image_ids)) {
        update_post_meta($post_id, '_ayra_review_images', $image_ids);
    }

    wp_send_json_success([
        'message' => 'شكراً لك! تم إضافة رأيك بنجاح ✓',
        'review'  => [
            'id'         => $post_id,
            'author'     => $author,
            'rating'     => $rating,
            'text'       => $text,
            'audio_url'  => $audio_url,
            'image_urls' => $image_urls,
            'date'       => date('j M Y'),
        ],
    ]);
}

// ─── Allow audio uploads ────────────────────────────────
function ayra_allow_audio_uploads($mimes) {
    $mimes['webm'] = 'audio/webm';
    $mimes['ogg']  = 'audio/ogg';
    return $mimes;
}
add_filter('upload_mimes', 'ayra_allow_audio_uploads');

// ─── Render Reviews Section (for single-product.php) ────
function ayra_render_reviews_section($product_id) {
    // Get initial reviews — show ALL reviews on every product
    $reviews = get_posts([
        'post_type'      => 'ayra_review',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $total = new WP_Query([
        'post_type'      => 'ayra_review',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    ]);
    $total_count = $total->found_posts;
    $star_svg = '<svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    ?>
    <div class="ayra-reviews-section" id="ayra-reviews" data-product-id="<?php echo esc_attr($product_id); ?>">
        <h3>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            آراء العملاء
            <?php if ($total_count > 0): ?>
            <span style="font-size:14px; font-weight:500; color:var(--ayra-text-muted);">(<?php echo $total_count; ?>)</span>
            <?php endif; ?>
        </h3>

        <!-- Reviews List -->
        <div class="ayra-reviews-list" id="ayra-reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="ayra-no-reviews">لا توجد آراء بعد 💬</div>
            <?php else: ?>
                <?php foreach ($reviews as $review): 
                    $r_author = get_post_meta($review->ID, '_ayra_review_author', true) ?: 'زبون/ة';
                    $r_rating = intval(get_post_meta($review->ID, '_ayra_review_rating', true)) ?: 5;
                    $r_audio  = get_post_meta($review->ID, '_ayra_review_audio', true);
                    $r_images = get_post_meta($review->ID, '_ayra_review_images', true);
                    $r_text   = wp_strip_all_tags($review->post_content);
                    $r_date   = get_the_date('j M Y', $review->ID);
                    $r_initial = mb_substr($r_author, 0, 1);
                ?>
                <div class="ayra-review-card">
                    <div class="ayra-review-card-header">
                        <div class="ayra-review-card-author">
                            <div class="ayra-review-avatar"><?php echo esc_html($r_initial); ?></div>
                            <div>
                                <div class="ayra-review-name"><?php echo esc_html($r_author); ?></div>
                                <div class="ayra-review-date"><?php echo esc_html($r_date); ?></div>
                            </div>
                        </div>
                        <div class="ayra-review-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <svg viewBox="0 0 24 24" class="<?php echo $s > $r_rating ? 'empty' : ''; ?>"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if ($r_text): ?>
                    <div class="ayra-review-card-body"><?php echo esc_html($r_text); ?></div>
                    <?php endif; ?>
                    <?php if (is_array($r_images) && !empty($r_images)): ?>
                    <div class="ayra-review-images-gallery">
                        <?php foreach ($r_images as $img_id):
                            $img_url = wp_get_attachment_image_url($img_id, 'full');
                            if ($img_url):
                        ?>
                        <div class="ayra-review-img-thumb">
                            <img src="<?php echo esc_url($img_url); ?>" alt="صورة المراجعة" loading="lazy">
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($r_audio): ?>
                    <div class="ayra-review-audio">
                        <div class="ayra-review-audio-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            تسجيل صوتي
                        </div>
                        <audio controls src="<?php echo esc_url($r_audio); ?>"></audio>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_count > 5): ?>
        <div class="ayra-reviews-load-more">
            <button class="ayra-load-more-btn" id="ayra-load-more-reviews" data-page="2">
                عرض المزيد من الآراء
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
