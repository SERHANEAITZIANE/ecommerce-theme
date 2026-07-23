<?php
/**
 * AYRA Homewear — Announcement Banner
 * 
 * Admin settings page for configurable scrolling banner at top of site.
 * Supports up to 10 sentences with customizable text/background colors.
 */
defined('ABSPATH') || exit;

// ─── Admin Settings Page ─────────────────────────────────
function ayra_banner_add_menu() {
    add_submenu_page(
        'options-general.php',
        'إعدادات البانر',
        '🔔 البانر العلوي',
        'manage_options',
        'ayra-banner-settings',
        'ayra_banner_settings_page'
    );
}
add_action('admin_menu', 'ayra_banner_add_menu');

// Register settings
function ayra_banner_register_settings() {
    register_setting('ayra_banner_options', 'ayra_banner_enabled');
    register_setting('ayra_banner_options', 'ayra_banner_bg_color');
    register_setting('ayra_banner_options', 'ayra_banner_text_color');
    register_setting('ayra_banner_options', 'ayra_banner_sentences');
}
add_action('admin_init', 'ayra_banner_register_settings');

// Settings page HTML
function ayra_banner_settings_page() {
    $enabled    = get_option('ayra_banner_enabled', 'yes');
    $bg_color   = get_option('ayra_banner_bg_color', '#1f2937');
    $text_color = get_option('ayra_banner_text_color', '#ffffff');
    $sentences  = get_option('ayra_banner_sentences', []);
    if (!is_array($sentences)) $sentences = [];
    // Ensure at least 1 slot
    while (count($sentences) < 1) $sentences[] = '';
    ?>
    <div class="wrap">
        <h1>🔔 إعدادات البانر العلوي</h1>
        <p style="font-size:14px; color:#666;">أضف حتى 10 جمل تظهر في بانر متحرك أعلى الموقع.</p>
        
        <form method="post" action="options.php">
            <?php settings_fields('ayra_banner_options'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">تفعيل البانر</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ayra_banner_enabled" value="yes" <?php checked($enabled, 'yes'); ?>>
                            إظهار البانر في أعلى الموقع
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">لون الخلفية</th>
                    <td>
                        <input type="color" name="ayra_banner_bg_color" value="<?php echo esc_attr($bg_color); ?>" style="width:60px;height:40px;cursor:pointer;">
                        <input type="text" value="<?php echo esc_attr($bg_color); ?>" style="width:100px;margin-right:8px;" readonly>
                    </td>
                </tr>
                <tr>
                    <th scope="row">لون النص</th>
                    <td>
                        <input type="color" name="ayra_banner_text_color" value="<?php echo esc_attr($text_color); ?>" style="width:60px;height:40px;cursor:pointer;">
                        <input type="text" value="<?php echo esc_attr($text_color); ?>" style="width:100px;margin-right:8px;" readonly>
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:30px;">الجمل (حتى 10)</h2>
            <p style="color:#666;">اترك الحقل فارغاً لتجاهله. الجمل الفارغة لن تظهر في البانر.</p>
            
            <div id="ayra-banner-sentences" style="max-width:700px;">
                <?php for ($i = 0; $i < 10; $i++): 
                    $val = isset($sentences[$i]) ? $sentences[$i] : '';
                ?>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <span style="min-width:30px; font-weight:700; color:#6366f1;"><?php echo ($i + 1); ?>.</span>
                    <input type="text" 
                           name="ayra_banner_sentences[]" 
                           value="<?php echo esc_attr($val); ?>" 
                           placeholder="أدخل الجملة رقم <?php echo ($i + 1); ?>..."
                           style="flex:1; padding:10px 14px; border:1.5px solid #d1d5db; border-radius:10px; font-size:15px; font-family:'Tajawal',sans-serif;"
                           dir="auto">
                </div>
                <?php endfor; ?>
            </div>

            <div style="margin-top:24px; padding:16px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; max-width:700px;">
                <strong>معاينة:</strong>
                <div id="ayra-banner-preview" style="margin-top:10px; padding:10px 16px; border-radius:8px; overflow:hidden; white-space:nowrap; font-size:14px; font-weight:600; background:<?php echo esc_attr($bg_color); ?>; color:<?php echo esc_attr($text_color); ?>;">
                    <?php 
                    $active = array_filter($sentences, function($s) { return trim($s) !== ''; });
                    echo esc_html(implode('   ✦   ', $active) ?: 'لا توجد جمل بعد');
                    ?>
                </div>
            </div>

            <?php submit_button('💾 حفظ الإعدادات'); ?>
        </form>
    </div>

    <script>
    // Live preview update
    document.querySelectorAll('input[name="ayra_banner_bg_color"], input[name="ayra_banner_text_color"]').forEach(function(inp){
        inp.addEventListener('input', function(){
            var bg = document.querySelector('input[name="ayra_banner_bg_color"]').value;
            var tc = document.querySelector('input[name="ayra_banner_text_color"]').value;
            var preview = document.getElementById('ayra-banner-preview');
            preview.style.background = bg;
            preview.style.color = tc;
            // Update text displays
            inp.parentElement.querySelector('input[type="text"]').value = inp.value;
        });
    });
    </script>
    <?php
}

// ─── Render Banner on Frontend ──────────────────────────
function ayra_render_announcement_banner() {
    $enabled = get_option('ayra_banner_enabled', 'yes');
    if ($enabled !== 'yes') return;

    $sentences  = get_option('ayra_banner_sentences', []);
    if (!is_array($sentences)) $sentences = [];
    $active = array_filter($sentences, function($s) { return trim($s) !== ''; });
    if (empty($active)) {
        $active = [
            'مرحباً بكم في AYRA Homewear 💖',
            'توصيل سريع لجميع الولايات 🇩🇿',
            'تسوقي أفضل البيجامات والملابس المنزلية العصرية'
        ];
    }

    $bg_color   = esc_attr(get_option('ayra_banner_bg_color', '#1f2937'));
    $text_color = esc_attr(get_option('ayra_banner_text_color', '#ffffff'));

    // Build the marquee content (duplicate for seamless loop)
    $separator = '&nbsp;&nbsp;&nbsp;✦&nbsp;&nbsp;&nbsp;';
    $content = '';
    foreach ($active as $s) {
        $content .= esc_html(trim($s)) . $separator;
    }
    ?>
    <div class="ayra-announcement-banner" id="ayra-announcement-banner" style="background:<?php echo $bg_color; ?>; color:<?php echo $text_color; ?>;">
        <div class="ayra-banner-track">
            <span class="ayra-banner-content"><?php echo $content; ?></span>
            <span class="ayra-banner-content"><?php echo $content; ?></span>
            <span class="ayra-banner-content"><?php echo $content; ?></span>
            <span class="ayra-banner-content"><?php echo $content; ?></span>
        </div>
    </div>
    <?php
}
