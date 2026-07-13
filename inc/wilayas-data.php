<?php
/**
 * AYRA Homewear - Wilayas Data (58 wilayas of Algeria)
 * Zone: 'north', 'center', or 'south' for delivery pricing
 *
 * North (شمال): Coastal + Tell Atlas wilayas
 * Center (وسط): Hauts Plateaux / Steppe wilayas
 * South (جنوب): Saharan wilayas
 */
defined('ABSPATH') || exit;

function ayra_get_wilayas_data() {
    $synced = get_option('ayra_zr_api_territories');
    if (!empty($synced) && is_array($synced)) {
        return $synced;
    }
    return ayra_get_wilayas_data_static();
}

function ayra_get_wilayas_data_static() {
    return [
        // ─── North (شمال) — Coastal & Tell Atlas ───────────────────
        '02' => ['name' => 'الشلف - Chlef',              'zone' => 'north', 'communes' => ['الشلف','تنس','بوقادير','الكريمية','واد فضة','أم الذروع','عين مران','بني حواء']],
        '06' => ['name' => 'بجاية - Béjaïa',            'zone' => 'north', 'communes' => ['بجاية','أقبو','سيدي عيش','خراطة','أميزور','تيشي','صدوق','الكسور']],
        '09' => ['name' => 'البليدة - Blida',            'zone' => 'north', 'communes' => ['البليدة','بوفاريك','المدية','الأربعاء','العفرون','بوقرة','موزاية','شريعة']],
        '10' => ['name' => 'البويرة - Bouira',           'zone' => 'north', 'communes' => ['البويرة','سور الغزلان','عين بسام','الأخضرية','برج أوخريص','الهاشمية']],
        '13' => ['name' => 'تلمسان - Tlemcen',           'zone' => 'north', 'communes' => ['تلمسان','مغنية','الغزوات','الرمشي','ندرومة','سبدو','هنين','بني سنوس']],
        '15' => ['name' => 'تيزي وزو - Tizi Ouzou',     'zone' => 'north', 'communes' => ['تيزي وزو','عزازقة','ذراع الميزان','واسيف','بوغني','عين الحمام','إفرحونن']],
        '16' => ['name' => 'الجزائر - Alger',            'zone' => 'north', 'communes' => ['بئر مراد رايس','حسين داي','باب الوادي','القبة','دالي إبراهيم','الأبيار','بئر خادم','الحراش','براقي','بوزريعة','درارية','المحمدية','سيدي امحمد','الشراقة']],
        '18' => ['name' => 'جيجل - Jijel',              'zone' => 'north', 'communes' => ['جيجل','الميلية','الطاهير','سيدي معروف','زيامة منصورية','العوانة','الشقفة']],
        '19' => ['name' => 'سطيف - Sétif',              'zone' => 'north', 'communes' => ['سطيف','العلمة','عين ولمان','برج بوعريريج','عين أزال','بوعنداس','جميلة']],
        '21' => ['name' => 'سكيكدة - Skikda',           'zone' => 'north', 'communes' => ['سكيكدة','القل','عزابة','تمالوس','الحروش','زيتونة','أولاد عطية']],
        '23' => ['name' => 'عنابة - Annaba',             'zone' => 'north', 'communes' => ['عنابة','الحجار','برحال','سرايدي','عين الباردة','شطايبي','سيدي عمار']],
        '24' => ['name' => 'قالمة - Guelma',             'zone' => 'north', 'communes' => ['قالمة','عين مخلوف','وادي الزناتي','حمام دباغ','بوشقوف','هيليوبوليس']],
        '25' => ['name' => 'قسنطينة - Constantine',      'zone' => 'north', 'communes' => ['قسنطينة','الخروب','عين السمارة','حامة بوزيان','ديدوش مراد','زيغود يوسف']],
        '27' => ['name' => 'مستغانم - Mostaganem',       'zone' => 'north', 'communes' => ['مستغانم','عين تادلس','حاسي ماماش','سيدي لخضر','بوقيرات','خير الدين']],
        '31' => ['name' => 'وهران - Oran',               'zone' => 'north', 'communes' => ['وهران','بئر الجير','السانية','عين الترك','أرزيو','وادي تليلات','بوسفر']],
        '34' => ['name' => 'برج بوعريريج - Bordj Bou Arréridj', 'zone' => 'north', 'communes' => ['برج بوعريريج','رأس الوادي','المنصورة','سيدي مبارك','بئر قاصد علي']],
        '35' => ['name' => 'بومرداس - Boumerdès',        'zone' => 'north', 'communes' => ['بومرداس','الرويبة','الثنية','دلس','برج منايل','خميس الخشنة','الأربعطاش']],
        '36' => ['name' => 'الطارف - El Tarf',           'zone' => 'north', 'communes' => ['الطارف','القالة','الشافعة','بوحجار','بن مهيدي','بسباس','بوثلجمة']],
        '41' => ['name' => 'سوق أهراس - Souk Ahras',     'zone' => 'north', 'communes' => ['سوق أهراس','مداوروش','سدراتة','تاورة','حنانشة','مشروحة']],
        '42' => ['name' => 'تيبازة - Tipaza',            'zone' => 'north', 'communes' => ['تيبازة','شرشال','القليعة','حجوط','سيدي أعمر','فوكة','بوإسماعيل']],
        '43' => ['name' => 'ميلة - Mila',                'zone' => 'north', 'communes' => ['ميلة','فرجيوة','شلغوم العيد','التلاغمة','وادي العثمانية','تسدان حدادة']],
        '44' => ['name' => 'عين الدفلى - Aïn Defla',     'zone' => 'north', 'communes' => ['عين الدفلى','الخميس','مليانة','الحمادية','العطاف','بومدفع']],
        '46' => ['name' => 'عين تموشنت - Aïn Témouchent', 'zone' => 'north', 'communes' => ['عين تموشنت','بني صاف','الحمام بوحجر','العمارنة','شعبة اللحم']],
        '48' => ['name' => 'غليزان - Relizane',          'zone' => 'north', 'communes' => ['غليزان','وادي رهيو','ماروانة','زمورة','عين طارق','الحمادنة']],

        // ─── Center (وسط) — Hauts Plateaux / Steppe ────────────────
        '03' => ['name' => 'الأغواط - Laghouat',         'zone' => 'center', 'communes' => ['الأغواط','أفلو','حاسي الرمل','عين ماضي','قصر الحيران','الحويطة','بريدة']],
        '04' => ['name' => 'أم البواقي - Oum El Bouaghi', 'zone' => 'center', 'communes' => ['أم البواقي','عين البيضاء','عين مليلة','عين فكرون','سيقوس','مسكيانة']],
        '05' => ['name' => 'باتنة - Batna',              'zone' => 'center', 'communes' => ['باتنة','بريكة','عين التوتة','مروانة','أريس','تازولت','نقاوس','سريانة']],
        '07' => ['name' => 'بسكرة - Biskra',             'zone' => 'center', 'communes' => ['بسكرة','طولقة','سيدي عقبة','أولاد جلال','الفيض','زريبة الوادي','جمورة']],
        '12' => ['name' => 'تبسة - Tébessa',             'zone' => 'center', 'communes' => ['تبسة','بئر العاتر','الشريعة','العوينات','مرسط','الونزة','بكارية']],
        '14' => ['name' => 'تيارت - Tiaret',             'zone' => 'center', 'communes' => ['تيارت','فرندة','سوقر','قصر الشلالة','مهدية','عين الذهب','عين كرمس']],
        '17' => ['name' => 'الجلفة - Djelfa',            'zone' => 'center', 'communes' => ['الجلفة','مسعد','عين وسارة','حاسي بحبح','بيرين','دار الشيوخ','الإدريسية']],
        '20' => ['name' => 'سعيدة - Saïda',             'zone' => 'center', 'communes' => ['سعيدة','عين الحجر','يوب','الحساسنة','أولاد إبراهيم']],
        '22' => ['name' => 'سيدي بلعباس - Sidi Bel Abbès', 'zone' => 'center', 'communes' => ['سيدي بلعباس','بن باديس','عين التبنت','تلاغ','سفيزف','مصطفى بن إبراهيم']],
        '26' => ['name' => 'المدية - Médéa',             'zone' => 'center', 'communes' => ['المدية','قصر البخاري','البرواقية','بن شكاو','تابلاط','شلالة العذاورة']],
        '28' => ['name' => 'المسيلة - M\'sila',          'zone' => 'center', 'communes' => ['المسيلة','بوسعادة','سيدي عيسى','عين الملح','حمام الضلعة','مقرة']],
        '29' => ['name' => 'معسكر - Mascara',            'zone' => 'center', 'communes' => ['معسكر','سيق','تيغنيف','المحمدية','بوحنيفية','غريس','عين فارس']],
        '32' => ['name' => 'البيض - El Bayadh',          'zone' => 'center', 'communes' => ['البيض','بوقطب','الأبيض سيدي الشيخ','بريزينة','ستيتن']],
        '38' => ['name' => 'تيسمسيلت - Tissemsilt',      'zone' => 'center', 'communes' => ['تيسمسيلت','ثنية الحد','برج بونعامة','لرجام','عماري']],
        '39' => ['name' => 'الوادي - El Oued',           'zone' => 'center', 'communes' => ['الوادي','قمار','المقرن','حاسي خليفة','الدبيلة','الرباح']],
        '40' => ['name' => 'خنشلة - Khenchela',          'zone' => 'center', 'communes' => ['خنشلة','قايس','بابار','شلية','أولاد رشاش','بوحمامة']],
        '45' => ['name' => 'النعامة - Naâma',            'zone' => 'center', 'communes' => ['النعامة','عين الصفراء','المشرية','مغرار','تيوت']],
        '49' => ['name' => 'المغير - El M\'Ghair',       'zone' => 'center', 'communes' => ['المغير','جامعة','سيدي خليل','أولاد عمران']],
        '51' => ['name' => 'أولاد جلال - Ouled Djellal',  'zone' => 'center', 'communes' => ['أولاد جلال','سيدي خالد','الدوسن','الشعيبة']],
        '55' => ['name' => 'تقرت - Touggourt',           'zone' => 'center', 'communes' => ['تقرت','تماسين','المقارين','النزلة']],

        // ─── South (جنوب) — Saharan wilayas ────────────────────────
        '01' => ['name' => 'أدرار - Adrar',              'zone' => 'south', 'communes' => ['أدرار','تيميمون','رقان','أولف','زاوية كنتة','تسابيت','شروين','فنوغيل']],
        '08' => ['name' => 'بشار - Béchar',              'zone' => 'south', 'communes' => ['بشار','بني ونيف','عبادلة','القنادسة','تاغيت','كرزاز','المشرية']],
        '11' => ['name' => 'تمنراست - Tamanrasset',      'zone' => 'south', 'communes' => ['تمنراست','عين قزام','عين صالح','تين زواتين']],
        '30' => ['name' => 'ورقلة - Ouargla',            'zone' => 'south', 'communes' => ['ورقلة','حاسي مسعود','تقرت','الحجيرة','سيدي خويلد','عين البيضاء']],
        '33' => ['name' => 'إليزي - Illizi',             'zone' => 'south', 'communes' => ['إليزي','جانت','دبداب','برج عمر إدريس']],
        '37' => ['name' => 'تندوف - Tindouf',            'zone' => 'south', 'communes' => ['تندوف']],
        '47' => ['name' => 'غرداية - Ghardaïa',          'zone' => 'south', 'communes' => ['غرداية','المنيعة','متليلي','بريان','القرارة','الضاية','بنورة']],
        '50' => ['name' => 'المنيعة - El Meniaa',        'zone' => 'south', 'communes' => ['المنيعة','حاسي الغلة']],
        '52' => ['name' => 'برج باجي مختار - Bordj Badji Mokhtar', 'zone' => 'south', 'communes' => ['برج باجي مختار','تيمياوين']],
        '53' => ['name' => 'بني عباس - Béni Abbès',      'zone' => 'south', 'communes' => ['بني عباس','الوطاة','تامترت','القصابي']],
        '54' => ['name' => 'تيميمون - Timimoun',         'zone' => 'south', 'communes' => ['تيميمون','أوقروت','طلمين','المطرفة']],
        '56' => ['name' => 'جانت - Djanet',              'zone' => 'south', 'communes' => ['جانت','برج الحواس']],
        '57' => ['name' => 'عين صالح - In Salah',        'zone' => 'south', 'communes' => ['عين صالح','فقارة الزوى','أولاد عيسى']],
        '58' => ['name' => 'عين قزام - In Guezzam',      'zone' => 'south', 'communes' => ['عين قزام','تين زواتين']],
    ];
}

function ayra_get_wilayas_dropdown($delivery_type = '') {
    $wilayas = ayra_get_wilayas_data();
    $options = ['' => 'اختر/ي الولاية'];

    // Get desk communes for filtering if needed
    $desk_communes = [];
    if ($delivery_type === 'desk') {
        $desk_communes = get_option('ayra_zr_desk_communes_static', []);
        if (empty($desk_communes)) {
            $desk_communes = get_transient('ayra_zr_desk_communes') ?: [];
        }
    }

    // Sort by wilaya code for proper order
    ksort($wilayas);

    foreach ($wilayas as $code => $data) {
        if ($delivery_type === 'desk' && !empty($desk_communes)) {
            $has_desk = false;
            foreach ($data['communes'] as $commune) {
                $c_lower = mb_strtolower(trim($commune));
                foreach ($desk_communes as $dc) {
                    if ($c_lower === $dc || strpos($c_lower, $dc) !== false || strpos($dc, $c_lower) !== false) {
                        $has_desk = true;
                        break;
                    }
                }
                if ($has_desk) break;
            }
            if (!$has_desk) continue;
        }

        $options[$code] = $code . ' - ' . $data['name'];
    }

    return $options;
}
?>