<?php
/**
 * Checkout Form - AYRA Homewear
 * Premium mobile-first checkout with delivery calculation
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Get cart data
$cart_items = WC()->cart->get_cart();
$unique_items_count = count($cart_items);
$cart_qty = 1;
if (!empty($cart_items)) {
    foreach ($cart_items as $ci) { $cart_qty = $ci['quantity']; break; }
}
$cart_total_raw = WC()->cart->get_cart_contents_total();
?>

<div class="ayra-checkout-wrapper" dir="rtl">
  <div class="ayra-checkout-card">

    <!-- Progress Steps -->
    <div class="ayra-steps">
      <div class="ayra-step active" data-step="1"><span class="ayra-step-num">1</span><span class="ayra-step-label">المعلومات</span></div>
      <div class="ayra-step-line"></div>
      <div class="ayra-step" data-step="2"><span class="ayra-step-num">2</span><span class="ayra-step-label">التوصيل</span></div>
      <div class="ayra-step-line"></div>
      <div class="ayra-step" data-step="3"><span class="ayra-step-num">3</span><span class="ayra-step-label">التأكيد</span></div>
    </div>

    <form name="checkout" method="post" class="checkout woocommerce-checkout ayra-co-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

      <?php if ( $checkout->get_checkout_fields() ) : ?>
        <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

        <!-- STEP 1: Customer Info -->
        <div class="ayra-co-section" id="ayra-step-info">
          <h3 class="ayra-co-heading">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            معلومات الطلب
          </h3>
          <div class="ayra-fields-grid">
            <?php do_action( 'woocommerce_checkout_billing' ); ?>
          </div>
          <!-- Hidden fields for StopDesk hub data -->
          <input type="hidden" name="billing_hub_id" id="billing_hub_id" value="">
          <input type="hidden" name="billing_desk_district_id" id="billing_desk_district_id" value="">
          <input type="hidden" name="billing_desk_city_id" id="billing_desk_city_id" value="">
        </div>


        <div class="ayra-co-section" id="ayra-summary-section">
          <div class="ayra-summary-toggle open" id="ayra-summary-toggle">
            <div class="ayra-summary-toggle-left">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              <span>ملخص الطلب</span>
            </div>
            <svg class="ayra-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="ayra-summary-body" id="ayra-summary-body">
            <div id="order_review" class="woocommerce-checkout-review-order">
              <?php do_action( 'woocommerce_checkout_order_review' ); ?>
            </div>
          </div>
        </div>

        <!-- Action Area -->
        <div class="ayra-co-actions">
          <?php if ($unique_items_count <= 1) : ?>
          <div class="ayra-qty-row">
            <div class="ayra-qty-ctrl">
              <button type="button" class="ayra-qty-b minus">−</button>
              <input type="number" id="ayra_cart_qty" value="<?php echo esc_attr($cart_qty); ?>" min="1" readonly>
              <button type="button" class="ayra-qty-b plus">+</button>
            </div>
            <button type="button" class="ayra-delete-btn" id="ayra-delete-item" title="حذف المنتج">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </button>
          </div>
          <?php endif; ?>

          <div class="ayra-exchange-note">
            <span class="ayra-exchange-note-label">ملاحظة :</span> في حالة الاستبدال يرجى ترك رسالة على الواتساب
            <a href="https://wa.me/213563537757" class="ayra-exchange-note-phone" target="_blank">0563 53 77 57</a>
          </div>

          <button type="button" id="ayra_custom_submit" class="ayra-order-btn" <?php echo ($unique_items_count > 1) ? 'style="width:100%"' : ''; ?>>
            <span class="ayra-order-btn-text">تأكيد الطلب</span>
            <span class="ayra-order-btn-price" id="ayra-total-display"><?php echo WC()->cart->get_total(); ?></span>
          </button>
          <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Place order" style="display:none;">Place order</button>
        </div>



      <?php endif; ?>
    </form>
  </div>
</div>

<script>
<?php
$zr_dir = get_template_directory() . '/inc/zr-data/';

function ayra_clean_json_read($path) {
    if (!file_exists($path)) return [];
    $content = @file_get_contents($path);
    if (!$content) return [];
    // Remove UTF-8 BOM if present
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Fallback or debug (you can add logging here if needed)
    }
    return is_array($decoded) ? $decoded : [];
}

$wilayas_raw  = ayra_clean_json_read( $zr_dir . 'zr_wilayas.json' );
$communes_raw = ayra_clean_json_read( $zr_dir . 'zr_communes.json' );
$desks_raw    = ayra_clean_json_read( $zr_dir . 'zr_pickup_desks.json' );

$wilayas       = [];
$wilaya_id_map = [];
foreach ( $wilayas_raw as $w ) {
    $code = (int) $w['code'];
    $wilayas[ $code ] = [
        'name'       => $w['name']         ?? '',
        'price_home' => (int) ( $w['price_home']     ?? 0 ),
        'price_desk' => (int) ( $w['price_stopdesk'] ?? 0 ),
    ];
    if ( ! empty( $w['id'] ) ) $wilaya_id_map[ $w['id'] ] = $code;
}
ksort( $wilayas );

$communes_by_wilaya = [];
foreach ( $communes_raw as $c ) {
    $wcode = (int) ( $c['wilaya_code'] ?? 0 );
    if ( $wcode ) $communes_by_wilaya[ $wcode ][] = $c['name'];
}

$desks_by_wilaya = [];
foreach ( $desks_raw as $d ) {
    if ( empty( $d['is_pickup_point'] ) ) continue;
    $wcode = $wilaya_id_map[ $d['city_territory_id'] ?? '' ] ?? 0;
    if ( ! $wcode ) continue;
    $desks_by_wilaya[ $wcode ][] = [
        'hub_id'               => $d['id']                    ?? '',
        'district'             => $d['district']              ?? '',
        'district_territory_id'=> $d['district_territory_id'] ?? '',
        'city_territory_id'    => $d['city_territory_id']     ?? '',
        'name'                 => $d['name']                  ?? '',
        'street'               => $d['street']                ?? '',
        'phone'                => $d['phone1']                ?? '',
        'hours'                => $d['opening_hours']         ?? '',
    ];
}

$debug_info = [
    'zr_dir' => $zr_dir,
    'wilayas_file_exists' => file_exists($zr_dir . 'zr_wilayas.json'),
    'communes_file_exists' => file_exists($zr_dir . 'zr_communes.json'),
    'desks_file_exists' => file_exists($zr_dir . 'zr_pickup_desks.json'),
    'wilayas_raw_count' => count($wilayas_raw),
    'ayra_dir_defined' => defined('AYRA_DIR'),
];

echo 'var ZR=' . wp_json_encode([
    'wilayas'         => $wilayas,
    'communes'        => $communes_by_wilaya,
    'desks_by_wilaya' => $desks_by_wilaya,
    'free_shipping'   => [
        'enabled' => get_option('ayra_free_shipping_enabled') === 'yes',
        'min'     => (float) get_option('ayra_free_shipping_min', 15000),
        'active'  => function_exists('ayra_is_free_shipping_active') && ayra_is_free_shipping_active(),
    ],
    '_debug'          => $debug_info
]) . ';';
?>
console.log("ZR Data Loaded:", ZR);
if (Object.keys(ZR.wilayas).length === 0) {
    console.error("CRITICAL: ZR Wilayas data is empty! Check ZR._debug for details.");
}

jQuery(document).ready(function($){
    var currentDeliveryPrice=0;
    var ajaxUrl=(typeof ayra_ajax!=='undefined')?ayra_ajax.url:wc_checkout_params.ajax_url;

    function pad(n){return String(n).padStart(2,'0');}
    // Delivery type now comes from radio cards instead of a <select>
    function dType(){return $('input[name="billing_delivery_type"]:checked').val()||'';}
    function isFreeShipping(){return !!(ZR.free_shipping&&ZR.free_shipping.enabled&&ZR.free_shipping.active);}
    // Show each card's price for the selected wilaya (or "مجاني" during the promo)
    function updateCardPrices(){
        var code=parseInt($('#billing_wilaya').val())||0;
        var w=(code&&ZR.wilayas[code])?ZR.wilayas[code]:null;
        $('.ayra-dlv-price').each(function(){
            var t=$(this).data('type');
            if(isFreeShipping()){$(this).text('توصيل مجاني ✓').addClass('free');return;}
            $(this).removeClass('free');
            if(!w){$(this).text('');return;}
            // Special case: Ain Defla (44) home delivery has commune-based pricing
            if(code===44 && t==='home'){$(this).text('200 - 500 دج');return;}
            var p=(t==='desk')?w.price_desk:w.price_home;
            $(this).text(p>0?p+' دج':'');
        });
    }
    // The free-shipping summary row is rendered server-side only when the
    // threshold is met, so its presence is the source of truth after updates
    function refreshFreeShipping(){
        if(ZR.free_shipping&&ZR.free_shipping.enabled){
            ZR.free_shipping.active=$('.ayra-free-shipping-row').length>0;
        }
        updateCardPrices();
    }
    function msg(t,type){
        var $m=$('<div class="ayra-toast '+type+'">'+t+'</div>');
        $('body').append($m);
        setTimeout(function(){$m.addClass('show');},10);
        setTimeout(function(){$m.removeClass('show');setTimeout(function(){$m.remove();},300);},3000);
    }
    function steps(){
        var w=$('#billing_wilaya').val(), d=dType();
        $('.ayra-step').removeClass('active done');
        if(w&&d){$('.ayra-step[data-step="1"],.ayra-step[data-step="2"]').addClass('done');$('.ayra-step[data-step="3"]').addClass('active');}
        else if(w||d){$('.ayra-step[data-step="1"]').addClass('done');$('.ayra-step[data-step="2"]').addClass('active');}
        else{$('.ayra-step[data-step="1"]').addClass('active');}
    }
    function reloadWilayas(){
        var dt=dType();
        var $sel=$('#billing_wilaya'), prev=$sel.val();
        var codes=Object.keys(ZR.wilayas).map(Number).sort(function(a,b){return a-b;});
        var html='<option value="">اختر/ي الولاية</option>';
        codes.forEach(function(code){
            if(dt==='desk'&&(!ZR.desks_by_wilaya[code]||!ZR.desks_by_wilaya[code].length))return;
            html+='<option value="'+code+'">'+pad(code)+' - '+ZR.wilayas[code].name+'</option>';
        });
        $sel.html(html);
        if(prev&&$sel.find('option[value="'+prev+'"]').length){$sel.val(prev);}
        else{$sel.val('').trigger('change');}
    }
    // Central function to sync hidden hub fields from selected commune option
    function syncHubFields(){
        var $opt=$('#billing_commune').find('option:selected');
        var hubId=$opt.attr('data-hub-id')||'';
        var distId=$opt.attr('data-district-id')||'';
        var cityId=$opt.attr('data-city-id')||'';
        $('#billing_hub_id').val(hubId);
        $('#billing_desk_district_id').val(distId);
        $('#billing_desk_city_id').val(cityId);
        if(hubId) console.log('Hub fields synced: hub='+hubId+', dist='+distId+', city='+cityId);
    }

    function reloadCommunes(){
        var code=parseInt($('#billing_wilaya').val())||0;
        var dt=dType();
        var $sel=$('#billing_commune'), prev=$sel.val();
        $sel.html('<option value="">اختر/ي البلدية</option>');
        // Clear hub hidden fields
        $('#billing_hub_id').val('');
        $('#billing_desk_district_id').val('');
        $('#billing_desk_city_id').val('');
        if(!code)return;
        if(dt==='desk'){
            // Show ALL individual hubs — no dedup by district
            (ZR.desks_by_wilaya[code]||[]).forEach(function(d,idx){
                $sel.append('<option value="'+d.district+'__hub__'+idx+'"'
                    +' data-hub-id="'+d.hub_id+'"'
                    +' data-district-id="'+d.district_territory_id+'"'
                    +' data-city-id="'+(d.city_territory_id||'')+'"'
                    +'>'+d.name+' — '+d.district+'</option>');
            });
        }else{
            (ZR.communes[code]||[]).forEach(function(n){
                $sel.append('<option value="'+n+'">'+n+'</option>');
            });
        }
        if(prev&&$sel.find('option[value="'+prev+'"]').length){
            $sel.val(prev);
            // Re-populate hidden fields after restoring previous selection
            syncHubFields();
        }
    }
    function updatePrice(){
        var code=parseInt($('#billing_wilaya').val())||0;
        var dt=dType();
        currentDeliveryPrice=(code&&dt&&ZR.wilayas[code])
            ?(dt==='desk'?ZR.wilayas[code].price_desk:ZR.wilayas[code].price_home):0;
        if(isFreeShipping())currentDeliveryPrice=0;
        updateCardPrices();
        $(document.body).trigger('update_checkout');
    }

    $(document).on('change','input[name="billing_delivery_type"]',function(){
        $('.ayra-dlv-card').removeClass('active');
        $(this).closest('.ayra-dlv-card').addClass('active');
        reloadWilayas();reloadCommunes();updatePrice();steps();
    });
    $(document).on('change','#billing_wilaya',function(){reloadCommunes();updatePrice();steps();});
    // When commune changes in desk mode, populate hidden hub fields
    $(document).on('change','#billing_commune',function(){
        syncHubFields();
        // Re-trigger checkout update so Ain Defla commune-level pricing recalculates
        $(document.body).trigger('update_checkout');
    });

    // WooCommerce may re-trigger events after update_checkout — re-sync hub fields & button total
    $(document.body).on('updated_checkout',function(){
        refreshFreeShipping();
        if(dType()==='desk' && $('#billing_commune').val()){
            syncHubFields();
        }
        // Update the purple button total to include delivery fee
        var $orderTotal = $('.order-total td .woocommerce-Price-amount, .order-total td .amount');
        if ($orderTotal.length) {
            $('#ayra-total-display').html($orderTotal.first().parent().html() || $orderTotal.first().html());
        }
    });

    $('#ayra-summary-toggle').on('click',function(){
        $(this).toggleClass('open');
        $('#ayra-summary-body').slideToggle(250);
    });
    $('.ayra-qty-b').on('click',function(){
        var $i=$('#ayra_cart_qty'),cur=parseInt($i.val())||1;
        if($(this).hasClass('minus')&&cur<=1)return;
        $i.val($(this).hasClass('minus')?cur-1:cur+1);
        $('.ayra-checkout-card').css('opacity','0.6');
        $.post(ajaxUrl,{action:'ayra_update_checkout_qty',qty:$i.val()},function(){
            $('.ayra-checkout-card').css('opacity','1');$('body').trigger('update_checkout');
        });
    });
    // Delete item — empty cart and redirect to shop
    $('#ayra-delete-item').on('click',function(){
        var $btn=$(this);
        $btn.addClass('deleting');
        $('.ayra-checkout-card').css('opacity','0.5');
        $.post(ajaxUrl,{action:'ayra_empty_cart'},function(){
            window.location.href=(typeof ayra_ajax!=='undefined' && ayra_ajax.shop_url)?ayra_ajax.shop_url:'/';
        });
    });
    $('#ayra_custom_submit').on('click',function(e){
        e.preventDefault();
        if(!dType()){msg('يرجى اختيار طريقة التوصيل أولاً','error');return;}
        if(!$('#billing_wilaya').val()){msg('يرجى اختيار الولاية','error');return;}
        if(!$('#billing_commune').val()){msg('يرجى اختيار البلدية','error');return;}
        // Ensure hub fields are synced right before submission
        syncHubFields();
        $('#place_order').trigger('click');
    });

    if(dType()){
        reloadWilayas();
        if($('#billing_wilaya').val()){reloadCommunes();updatePrice();}
    }
    refreshFreeShipping();
    steps();
});
</script>

<style>
/* ═══ AYRA Checkout — Mobile-First Premium ═══ */
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');

.ayra-checkout-wrapper {
    font-family: 'Tajawal', 'Cairo', sans-serif;
    min-height: 100vh;
    padding: 16px 12px 100px;
    background: linear-gradient(160deg, #f8f6f3 0%, #eee9e2 50%, #f5f0eb 100%);
    display: flex; justify-content: center; align-items: flex-start;
}
.ayra-checkout-card {
    width: 100%; max-width: 520px;
    background: #fff;
    border-radius: 24px;
    padding: 24px 18px 28px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04);
    transition: opacity 0.3s;
}

/* ─── Progress Steps ─── */
.ayra-steps {
    display: flex; align-items: center; justify-content: center;
    gap: 0; margin-bottom: 28px; padding: 0 8px;
}
.ayra-step {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    flex-shrink: 0;
}
.ayra-step-num {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
    background: #e5e7eb; color: #9ca3af;
    transition: all 0.3s;
}
.ayra-step.active .ayra-step-num {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}
.ayra-step.done .ayra-step-num {
    background: #10b981; color: #fff;
}
.ayra-step-label {
    font-size: 11px; font-weight: 600; color: #9ca3af; transition: color 0.3s;
}
.ayra-step.active .ayra-step-label { color: #4f46e5; }
.ayra-step.done .ayra-step-label { color: #10b981; }
.ayra-step-line {
    flex: 1; height: 2px; background: #e5e7eb; margin: 0 6px; margin-bottom: 18px;
    border-radius: 2px; min-width: 30px;
}

/* ─── Section Headings ─── */
.ayra-co-heading {
    display: flex; align-items: center; gap: 10px;
    font-size: 17px; font-weight: 800; color: #1f2937;
    margin: 0 0 18px; padding-bottom: 12px;
    border-bottom: 2px solid #f3f4f6;
}
.ayra-co-heading svg { color: #6366f1; flex-shrink: 0; }

/* ─── Hide WC defaults ─── */
.woocommerce-billing-fields h3,
.woocommerce-additional-fields { display: none !important; }
#payment { background: transparent !important; padding: 0 !important; }
#payment .payment_methods, #payment .place-order { display: none !important; }

/* ─── Form Fields ─── */
.ayra-fields-grid .form-row { margin-bottom: 16px; }
.ayra-fields-grid .form-row label {
    display: block; margin-bottom: 6px;
    font-size: 14px; font-weight: 700; color: #374151;
}
.ayra-fields-grid .form-row label .optional { display: none; }
.ayra-fields-grid .form-row-first,
.ayra-fields-grid .form-row-last { width: 100%; float: none; }
.ayra-fields-grid .form-row-wide { width: 100%; }
.ayra-fields-grid:after { content: ""; display: table; clear: both; }

/* Hide the delivery type hidden field wrapper */
.ayra-hidden-field { display: none !important; margin: 0 !important; padding: 0 !important; height: 0 !important; overflow: hidden !important; }

.ayra-fields-grid input[type="text"],
.ayra-fields-grid input[type="tel"],
.ayra-fields-grid select,
.select2-container .select2-selection--single {
    width: 100% !important; height: 52px !important;
    padding: 0 16px; border: 1.5px solid #d1d5db !important;
    border-radius: 14px !important; background: #fafafa !important;
    font-size: 16px; font-weight: 600; color: #111827 !important;
    outline: none; box-sizing: border-box;
    transition: border 0.2s, box-shadow 0.2s;
    font-family: 'Tajawal', sans-serif;
}
.ayra-fields-grid input:focus,
.select2-container--open .select2-selection--single {
    border-color: #6366f1 !important; background: #fff !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12) !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 52px !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 52px !important; color: #4b5563 !important; font-family: 'Tajawal', sans-serif;
}

/* ─── Delivery Cards ─── */
.ayra-delivery-cards {
    display: flex; flex-direction: column; gap: 12px;
}
.ayra-dlv-field { margin-bottom: 16px; }
.ayra-dlv-group-label {
    display: block; margin-bottom: 8px;
    font-size: 14px; font-weight: 700; color: #374151;
}
.ayra-dlv-group-label .required { color: #ef4444; text-decoration: none; }
.ayra-dlv-radio {
    position: absolute; opacity: 0; pointer-events: none; width: 0; height: 0;
}
.ayra-dlv-icon svg { stroke: #6366f1; transition: stroke 0.25s; }
.ayra-dlv-info { display: flex; flex-direction: column; }
.ayra-dlv-price { display: block; margin-top: 4px; }
.ayra-dlv-price:empty { display: none; }
.ayra-dlv-price.free { color: #16a34a; }
.woocommerce-invalid .ayra-dlv-card { border-color: #ef4444; }
.ayra-free-shipping-row th { font-weight: 600; color: #16a34a; font-size: 13px; }
.ayra-free-shipping-row td { font-weight: 800; color: #16a34a; font-size: 14px; }
.ayra-dlv-card {
    display: flex; align-items: center; gap: 14px;
    padding: 16px; border: 2px solid #e5e7eb;
    border-radius: 16px; cursor: pointer;
    background: #fafafa; position: relative;
    transition: all 0.25s ease;
}
.ayra-dlv-card:hover { border-color: #c7d2fe; background: #f5f3ff; }
.ayra-dlv-card.active {
    border-color: #6366f1; background: linear-gradient(135deg, #eef2ff 0%, #e8e0ff 100%);
    box-shadow: 0 4px 16px rgba(99,102,241,0.15);
}
.ayra-dlv-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid #e5e7eb; flex-shrink: 0;
    transition: all 0.25s;
}
.ayra-dlv-card.active .ayra-dlv-icon {
    background: #6366f1; border-color: #6366f1;
}
.ayra-dlv-card.active .ayra-dlv-icon svg { stroke: #fff; }
.ayra-dlv-info { flex: 1; min-width: 0; }
.ayra-dlv-name {
    display: block; font-size: 15px; font-weight: 700; color: #1f2937;
}
.ayra-dlv-time {
    display: block; font-size: 12px; font-weight: 500; color: #9ca3af; margin-top: 2px;
}
.ayra-dlv-price {
    font-size: 16px; font-weight: 800; color: #4f46e5;
    white-space: nowrap; flex-shrink: 0;
}
.ayra-dlv-check {
    position: absolute; top: 10px; left: 10px;
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: #6366f1; opacity: 0; transform: scale(0.5);
    transition: all 0.25s;
}
.ayra-dlv-check svg { stroke: #fff; }
.ayra-dlv-card.active .ayra-dlv-check { opacity: 1; transform: scale(1); }

/* ─── Summary Toggle ─── */
.ayra-summary-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; background: #f9fafb; border: 1px solid #e5e7eb;
    border-radius: 14px; cursor: pointer; margin-bottom: 2px;
    font-size: 15px; font-weight: 700; color: #374151;
    transition: background 0.2s;
}
.ayra-summary-toggle:hover { background: #f3f4f6; }
.ayra-summary-toggle-left { display: flex; align-items: center; gap: 10px; }
.ayra-summary-toggle-left svg { color: #6366f1; }
.ayra-chevron { transition: transform 0.3s; color: #9ca3af; }
.ayra-summary-toggle.open .ayra-chevron { transform: rotate(180deg); }
.ayra-summary-body {
    padding: 16px 4px 8px; display: block;
}

/* ─── WC Order Table ─── */
.woocommerce-checkout-review-order-table th,
.woocommerce-checkout-review-order-table td {
    padding: 10px 0; border-bottom: 1px solid #f3f4f6;
    text-align: right; font-size: 14px;
}
.woocommerce-checkout-review-order-table thead { display: none; }
.product-total, .cart-subtotal { display: none; }
.woocommerce-checkout-review-order-table .product-name { font-weight: 700; color: #1f2937; }
.product-quantity {
    display: inline-block; background: #f3f4f6; color: #4b5563;
    padding: 2px 7px; border-radius: 5px; font-size: 12px; font-weight: 700; margin-right: 8px;
}
.fee th { font-weight: 600; color: #4f46e5; font-size: 13px; }
.fee td { font-weight: 700; color: #4f46e5; font-size: 14px; }
.order-total th {
    color: #1f2937; font-weight: 800; font-size: 15px;
    border-top: 2px solid #e5e7eb; padding-top: 14px;
}
.order-total td {
    color: #111827; font-weight: 800; font-size: 20px;
    border-top: 2px solid #e5e7eb; padding-top: 14px;
}

/* ─── Actions ─── */
.ayra-co-actions {
    display: flex; gap: 12px; align-items: center;
    margin-top: 24px;
}
.ayra-qty-row {
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
}
.ayra-qty-ctrl {
    display: flex; height: 52px; border: 1.5px solid #d1d5db;
    border-radius: 14px; overflow: hidden; background: #fff; flex-shrink: 0;
}
.ayra-delete-btn {
    width: 48px; height: 52px; border: 1.5px solid #fca5a5;
    border-radius: 14px; background: #fef2f2;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.25s ease;
    color: #ef4444; flex-shrink: 0;
}
.ayra-delete-btn:hover {
    background: #fee2e2; border-color: #ef4444;
    transform: scale(1.05);
}
.ayra-delete-btn:active { transform: scale(0.95); }
.ayra-delete-btn.deleting {
    opacity: 0.5; pointer-events: none;
}
.ayra-qty-b {
    width: 42px; border: none; background: #f9fafb; font-size: 20px;
    font-weight: 600; cursor: pointer; color: #4b5563; transition: background 0.2s;
}
.ayra-qty-b:hover { background: #f3f4f6; }
.ayra-qty-ctrl input {
    width: 44px; border: none; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;
    text-align: center; font-size: 17px; font-weight: 700; color: #111; background: transparent;
    pointer-events: none; font-family: 'Tajawal', sans-serif;
}

.ayra-exchange-note {
    margin-bottom: 14px; padding: 12px 16px;
    background: #fffbeb; border: 1.5px solid #fde68a;
    border-radius: 12px; font-size: 14px; font-weight: 600;
    color: #92400e; line-height: 1.7; text-align: center;
}
.ayra-exchange-note-label {
    color: #dc2626; font-weight: 800; font-size: 15px;
}
.ayra-exchange-note-phone {
    display: inline-block; direction: ltr; font-weight: 800;
    color: #16a34a; text-decoration: none; margin-right: 4px;
}
.ayra-exchange-note-phone:hover { text-decoration: underline; }

.ayra-order-btn {
    flex: 1; height: 64px; border: none; border-radius: 16px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #fff; font-size: 18px; font-weight: 800; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    box-shadow: 0 8px 24px rgba(99,102,241,0.35);
    transition: transform 0.2s, box-shadow 0.2s;
    font-family: 'Tajawal', sans-serif;
    letter-spacing: 0.3px;
}
.ayra-order-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(99,102,241,0.45);
}
.ayra-order-btn:active { transform: translateY(0); }
.ayra-order-btn-text { font-size: 18px; }
.ayra-order-btn-price {
    font-size: 14px; opacity: 0.9;
    background: rgba(255,255,255,0.2); padding: 5px 14px;
    border-radius: 10px; font-weight: 700;
}



/* ─── Toast ─── */
.ayra-toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(80px);
    padding: 14px 28px; border-radius: 14px; font-size: 15px; font-weight: 700;
    z-index: 9999; opacity: 0; transition: all 0.3s;
    font-family: 'Tajawal', sans-serif;
}
.ayra-toast.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.ayra-toast.success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.ayra-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ─── Validation ─── */
.woocommerce-invalid input,
.woocommerce-invalid select { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.1) !important; }
.woocommerce-error {
    background: #fee2e2; color: #991b1b; padding: 14px 18px;
    border-radius: 14px; border-right: 4px solid #ef4444;
    margin-bottom: 20px; font-size: 14px;
}

/* ─── Sections spacing ─── */
.ayra-co-section { margin-bottom: 24px; }

/* ═══ Responsive ═══ */
@media (min-width: 600px) {
    .ayra-checkout-wrapper { padding: 40px 20px 60px; }
    .ayra-checkout-card { padding: 36px 32px 36px; border-radius: 28px; }
    .ayra-co-heading { font-size: 19px; }
    .ayra-fields-grid .form-row-first { width: 48%; float: right; }
    .ayra-fields-grid .form-row-last { width: 48%; float: left; }
    .ayra-delivery-cards { flex-direction: row; }
    .ayra-dlv-card { flex: 1; flex-direction: column; text-align: center; padding: 20px 14px; }
    .ayra-dlv-info { text-align: center; }
    .ayra-dlv-price { margin-top: 8px; }
}

@media (max-width: 599px) {
    .ayra-checkout-card { padding: 20px 14px 24px; border-radius: 20px; }
    .ayra-co-actions { flex-direction: column; }
    .ayra-qty-row { width: 100%; }
    .ayra-qty-ctrl { flex: 1; justify-content: center; }
    .ayra-qty-ctrl input { width: 60px; }
    .ayra-order-btn { width: 100%; }
    .ayra-step-label { font-size: 10px; }
    .ayra-step-num { width: 28px; height: 28px; font-size: 12px; }
}
</style>
