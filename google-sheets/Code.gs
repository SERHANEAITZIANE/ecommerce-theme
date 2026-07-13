/**
 * ═══════════════════════════════════════════════════════════════
 * AYRA HOMEWEAR — Google Sheets COD Order Management System
 * ═══════════════════════════════════════════════════════════════
 * 
 * SETUP INSTRUCTIONS:
 * 1. Create a new Google Sheet
 * 2. Go to Extensions > Apps Script
 * 3. Paste this entire code
 * 4. Click Deploy > New deployment > Web app
 *    - Execute as: Me
 *    - Who has access: Anyone
 * 5. Copy the Web App URL and paste it in WordPress:
 *    wp-admin > Settings > AYRA Sheets Sync
 * 
 * FEATURES:
 * - Auto-receive orders from WooCommerce
 * - Call tracking (up to 3 attempts)
 * - Agent assignment
 * - Wilaya/Commune change with auto delivery price recalc
 * - WhatsApp integration
 * - Beautiful Send to ZR button
 * - Product name truncation for ZR limits (50 chars)
 * - Conditional formatting (color-coded rows)
 * - Statistics dashboard
 */

// ═══════════════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════════════
var CONFIG = {
  SHEET_NAME: 'الطلبات',
  STATS_SHEET: 'إحصائيات',
  ZR_PRODUCT_NAME_LIMIT: 50,
  WHATSAPP_NUMBER: '213563537757',
  WHATSAPP_MESSAGE_TEMPLATE: 'مرحبا {name}، طلبك رقم #{order_id} من AYRA Homewear قيد التحضير. هل تؤكد/ين الطلب؟',
  // ZR Express API (fill these in)
  ZR_TENANT_ID: '',  // Get from WordPress: wp-admin > ZR Express
  ZR_API_KEY: '',    // Get from WordPress: wp-admin > ZR Express
  ZR_BASE_URL: 'https://api.zrexpress.app/api/v1',
  
  // List of helpdesk agents (confirmateurs)
  AGENTS: ['C1', 'C2'],
  // Auto-assign method: 'round-robin' (cycles through agents), or 'none' (leave blank for manual split button/dropdown)
  AUTO_ASSIGN_METHOD: 'round-robin', 
  
  // ─── Website configuration (optional if set in Script Properties) ───
  SITE_URL: 'https://ayrahomewear.com',      // e.g. 'https://ayrahomewear.com'
  SYNC_SECRET: '',   // Same secret configured in WooCommerce settings
};

// Helper to get site URL (checking CONFIG first, then Script Properties)
function getSiteUrl() {
  var url = CONFIG.SITE_URL || PropertiesService.getScriptProperties().getProperty('SITE_URL') || '';
  if (url && url.substring(url.length - 1) === '/') {
    url = url.substring(0, url.length - 1); // strip trailing slash
  }
  return url;
}

// Helper to get sync secret (checking CONFIG first, then Script Properties)
function getSyncSecret() {
  return CONFIG.SYNC_SECRET || PropertiesService.getScriptProperties().getProperty('SYNC_SECRET') || '';
}

// ═══════════════════════════════════════════════════════════════
// COLUMN MAP (1-indexed)
// ═══════════════════════════════════════════════════════════════
var COL = {
  ORDER_ID:       1,  // A
  DATE:           2,  // B
  NAME:           3,  // C
  PHONE:          4,  // D
  PRODUCTS:       5,  // E
  QTY:            6,  // F
  PRODUCTS_PRICE: 7,  // G
  DELIVERY_TYPE:  8,  // H
  WILAYA:         9,  // I
  COMMUNE:       10,  // J
  DELIVERY_FEE:  11,  // K
  GRAND_TOTAL:   12,  // L
  STATUS:        13,  // M
  AGENT:         14,  // N
  NOTES:         15,  // O
  CONFIRM_DATE:  16,  // P
  ZR_TRACKING:   17,  // Q
  CALL_1:        18,  // R
  CALL_2:        19,  // S
  CALL_3:        20,  // T
  WHATSAPP:      21,  // U — WhatsApp link
  WC_ORDER_ID:   22,  // V — Hidden: raw WooCommerce order ID for API calls
};

// ═══════════════════════════════════════════════════════════════
// DELIVERY PRICING DATA (from ZR Express)
// ═══════════════════════════════════════════════════════════════
var DELIVERY_PRICES = {
  1:{home:1450,desk:900},2:{home:600,desk:450},3:{home:1000,desk:600},
  4:{home:850,desk:450},5:{home:850,desk:450},6:{home:850,desk:450},
  7:{home:1000,desk:600},8:{home:1150,desk:650},9:{home:750,desk:450},
  10:{home:800,desk:450},11:{home:1650,desk:1050},12:{home:900,desk:450},
  13:{home:900,desk:500},14:{home:750,desk:450},15:{home:850,desk:450},
  16:{home:650,desk:350},17:{home:1000,desk:600},18:{home:850,desk:450},
  19:{home:850,desk:450},20:{home:800,desk:500},21:{home:850,desk:450},
  22:{home:800,desk:450},23:{home:900,desk:450},24:{home:850,desk:450},
  25:{home:850,desk:450},26:{home:800,desk:450},27:{home:750,desk:450},
  28:{home:900,desk:500},29:{home:750,desk:450},30:{home:1000,desk:600},
  31:{home:750,desk:450},32:{home:1100,desk:600},34:{home:850,desk:450},
  35:{home:800,desk:450},36:{home:900,desk:450},38:{home:750,desk:520},
  39:{home:1000,desk:600},40:{home:850,desk:450},41:{home:850,desk:450},
  42:{home:800,desk:450},43:{home:850,desk:450},44:{home:750,desk:450},
  45:{home:1100,desk:600},46:{home:800,desk:450},47:{home:1000,desk:600},
  48:{home:650,desk:450},49:{home:1450,desk:900},51:{home:1000,desk:550},
  52:{home:1200,desk:900},53:{home:1650,desk:1120},54:{home:1650,desk:0},
  55:{home:1000,desk:600},57:{home:1000,desk:0},58:{home:1050,desk:670}
};

// ═══════════════════════════════════════════════════════════════
// WILAYA NAMES (for display)
// ═══════════════════════════════════════════════════════════════
var WILAYA_NAMES = {
  1:"Adrar",2:"Chlef",3:"Laghouat",4:"Oum El Bouaghi",5:"Batna",
  6:"Bejaia",7:"Biskra",8:"Bechar",9:"Blida",10:"Bouira",
  11:"Tamanrasset",12:"Tebessa",13:"Tlemcen",14:"Tiaret",15:"Tizi Ouzou",
  16:"Alger",17:"Djelfa",18:"Jijel",19:"Setif",20:"Saida",
  21:"Skikda",22:"Sidi Bel Abbes",23:"Annaba",24:"Guelma",25:"Constantine",
  26:"Medea",27:"Mostaganem",28:"MSila",29:"Mascara",30:"Ouargla",
  31:"Oran",32:"El Bayadh",34:"Bordj Bou Arreridj",35:"Boumerdes",
  36:"El Tarf",38:"Tissemsilt",39:"El Oued",40:"Khenchela",
  41:"Souk Ahras",42:"Tipaza",43:"Mila",44:"Ain Defla",45:"Naama",
  46:"Ain Temouchent",47:"Ghardaia",48:"Relizane",49:"Timimoun",
  51:"Ouled Djellal",52:"Beni Abbes",53:"In Salah",54:"In Guezzam",
  55:"Touggourt",57:"El Meghaier",58:"El Menia"
};

// ═══════════════════════════════════════════════════════════════
// 1. MENU & INITIALIZATION
// ═══════════════════════════════════════════════════════════════

function onOpen() {
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('🚀 AYRA')
    .addItem('📦 إرسال المؤكدة إلى ZR Express', 'sendConfirmedToZR')
    .addSeparator()
    .addItem('👥 تقسيم الطلبات الجديدة', 'splitNewOrders')
    .addItem('🗑️ حذف المكررات', 'removeDuplicates')
    .addItem('📊 تحديث الإحصائيات', 'updateStats')
    .addItem('🎨 تطبيق التنسيق', 'applyFormatting')
    .addItem('🔄 مزامنة مكاتب التوصيل', 'syncDesks')
    .addItem('🔄 مزامنة البلديات', 'syncCommunes')
    .addSeparator()
    .addItem('🗑️ مسح جميع الطلبات', 'clearAllOrders')
    .addItem('⚙️ إعداد الورقة', 'setupSheet')
    .addToUi();
}

/**
 * Clear all data rows from the main sheet
 */
function clearAllOrders() {
  var ui = SpreadsheetApp.getUi();
  var response = ui.alert(
    '🗑️ مسح جميع الطلبات',
    'هل أنت متأكد من رغبتك في مسح جميع الطلبات من الجدول؟\nلا يمكن التراجع عن هذا الإجراء.',
    ui.ButtonSet.YES_NO
  );
  
  if (response !== ui.Button.YES) return;
  
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) return;
  
  var lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    sheet.deleteRows(2, lastRow - 1);
  }
  
  ui.alert('🗑️ مسح الطلبات', 'تم مسح جميع الطلبات بنجاح!', ui.ButtonSet.OK);
}

/**
 * Initial setup — creates headers, formatting, dropdowns, stats sheet
 */
function setupSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(CONFIG.SHEET_NAME);
  
  // Create main sheet if not exists
  if (!sheet) {
    sheet = ss.insertSheet(CONFIG.SHEET_NAME);
  }
  
  // Set RTL direction
  sheet.setRightToLeft(true);
  
  // ── Headers ──
  var headers = [
    'رقم الطلب',      // A
    'التاريخ',         // B
    'الاسم الكامل',    // C
    'رقم الهاتف',      // D
    'المنتجات',        // E
    'الكمية',          // F
    'ثمن المنتجات',    // G
    'طريقة التوصيل',   // H
    'الولاية',         // I
    'البلدية',         // J
    'ثمن التوصيل',     // K
    'المبلغ الإجمالي',  // L
    'الحالة',          // M
    'المسؤول',         // N
    'ملاحظات',         // O
    'تاريخ التأكيد',   // P
    'رقم التتبع ZR',   // Q
    'محاولة 1',        // R
    'محاولة 2',        // S
    'محاولة 3',        // T
    'واتساب',          // U
    'WC ID',           // V
  ];
  
  var headerRange = sheet.getRange(1, 1, 1, headers.length);
  headerRange.clearDataValidations();
  headerRange.setValues([headers]);
  headerRange.setFontWeight('bold');
  headerRange.setFontSize(13);
  headerRange.setBackground('#1f2937');
  headerRange.setFontColor('#ffffff');
  headerRange.setHorizontalAlignment('center');
  headerRange.setWrap(true);
  
  // ── Column widths ──
  sheet.setColumnWidth(COL.ORDER_ID, 90);
  sheet.setColumnWidth(COL.DATE, 140);
  sheet.setColumnWidth(COL.NAME, 160);
  sheet.setColumnWidth(COL.PHONE, 140);
  sheet.setColumnWidth(COL.PRODUCTS, 280);
  sheet.setColumnWidth(COL.QTY, 60);
  sheet.setColumnWidth(COL.PRODUCTS_PRICE, 110);
  sheet.setColumnWidth(COL.DELIVERY_TYPE, 110);
  sheet.setColumnWidth(COL.WILAYA, 140);
  sheet.setColumnWidth(COL.COMMUNE, 140);
  sheet.setColumnWidth(COL.DELIVERY_FEE, 100);
  sheet.setColumnWidth(COL.GRAND_TOTAL, 120);
  sheet.setColumnWidth(COL.STATUS, 110);
  sheet.setColumnWidth(COL.AGENT, 100);
  sheet.setColumnWidth(COL.NOTES, 200);
  sheet.setColumnWidth(COL.CONFIRM_DATE, 140);
  sheet.setColumnWidth(COL.ZR_TRACKING, 130);
  sheet.setColumnWidth(COL.CALL_1, 140);
  sheet.setColumnWidth(COL.CALL_2, 140);
  sheet.setColumnWidth(COL.CALL_3, 140);
  sheet.setColumnWidth(COL.WHATSAPP, 100);
  sheet.setColumnWidth(COL.WC_ORDER_ID, 80);
  
  // ── Freeze header row ──
  sheet.setFrozenRows(1);
  
  // ── Status dropdown (will be applied to data rows as they come in) ──
  // Done in applyStatusDropdown()
  
  // ── Delivery Type dropdown ──
  // Done in applyDeliveryDropdown()
  
  // ── Wilaya dropdown ──
  // Done in applyWilayaDropdown()
  
  // ── Create stats sheet ──
  setupStatsSheet(ss);
  
  // ── Apply conditional formatting ──
  applyFormatting();
  
  SpreadsheetApp.getUi().alert('✅ تم إعداد الورقة بنجاح!\n\nالخطوة التالية: انشر التطبيق كـ Web App');
}

/**
 * Setup the statistics sheet
 */
function setupStatsSheet(ss) {
  var statsSheet = ss.getSheetByName(CONFIG.STATS_SHEET);
  if (!statsSheet) {
    statsSheet = ss.insertSheet(CONFIG.STATS_SHEET);
  }
  statsSheet.setRightToLeft(true);
  
  // Headers
  var statsHeaders = [
    ['📊 إحصائيات AYRA Homewear', '', ''],
    ['', '', ''],
    ['المؤشر', 'القيمة', 'التفاصيل'],
  ];
  
  statsSheet.getRange(1, 1, 3, 3).setValues(statsHeaders);
  statsSheet.getRange(1, 1, 1, 3).merge().setFontSize(18).setFontWeight('bold')
    .setBackground('#4f46e5').setFontColor('#fff').setHorizontalAlignment('center');
  statsSheet.getRange(3, 1, 1, 3).setFontWeight('bold').setBackground('#f3f4f6');
  
  statsSheet.setColumnWidth(1, 200);
  statsSheet.setColumnWidth(2, 150);
  statsSheet.setColumnWidth(3, 250);
  
  updateStats();
}

// ═══════════════════════════════════════════════════════════════
// 2. WEBHOOK — Receive orders from WooCommerce
// ═══════════════════════════════════════════════════════════════

function doPost(e) {
  var lock = LockService.getScriptLock();
  try {
    // Wait for up to 30 seconds to acquire the lock
    lock.waitLock(30000);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      error: 'Lock timeout: Another request is currently syncing an order.'
    })).setMimeType(ContentService.MimeType.JSON);
  }

  try {
    var data = JSON.parse(e.postData.contents);
    
    // Validate required fields
    if (!data.order_id) {
      return ContentService.createTextOutput(JSON.stringify({
        success: false, error: 'Missing order_id'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(CONFIG.SHEET_NAME);
    if (!sheet) {
      sheet = ss.insertSheet(CONFIG.SHEET_NAME);
      setupSheet();
    }
    
    // ── Duplicate check (check both display ID and raw WC ID) ──
    var lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      var maxCol = sheet.getLastColumn();
      var fetchColCount = Math.max(maxCol, COL.WC_ORDER_ID);
      var allData = sheet.getRange(2, 1, lastRow - 1, fetchColCount).getValues();
      var incomingId = String(data.order_id).replace('#', '').trim();
      
      for (var i = 0; i < allData.length; i++) {
        var sheetDisplayId = String(allData[i][COL.ORDER_ID - 1]).replace('#', '').trim();
        var sheetRawId = String(allData[i][COL.WC_ORDER_ID - 1]).trim();
        
        if (sheetDisplayId === incomingId || sheetRawId === incomingId) {
          return ContentService.createTextOutput(JSON.stringify({
            success: true, message: 'Order already exists', duplicate: true
          })).setMimeType(ContentService.MimeType.JSON);
        }
      }
    }
    
    // Save full product names in the sheet
    var products = data.products || '';
    
    // ── Build WhatsApp link ──
    var phone = String(data.phone || '').replace(/[^0-9]/g, '');
    if (phone.charAt(0) === '0') phone = '213' + phone.substring(1);
    if (phone.substring(0,3) !== '213') phone = '213' + phone;
    
    var waMessage = CONFIG.WHATSAPP_MESSAGE_TEMPLATE
      .replace('{name}', data.name || '')
      .replace('{order_id}', data.order_id || '');
    var waLink = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(waMessage);
    
    // ── Calculate delivery fee ──
    var wilayaCode = parseInt(data.wilaya_code) || 0;
    var deliveryType = data.delivery_type || 'home';
    var deliveryFee = getDeliveryPrice(wilayaCode, deliveryType);
    var productsPrice = parseFloat(data.products_price) || 0;
    var grandTotal = productsPrice + deliveryFee;
    
    // ── Wilaya display ──
    var wilayaDisplay = wilayaCode + ' - ' + (WILAYA_NAMES[wilayaCode] || '');
    var deliveryTypeDisplay = deliveryType === 'home' ? 'منزل 🏠' : 'مكتب 📦';
    
    // ── Phone display (clickable) ──
    var phoneDisplay = data.phone || '';
    
    // ── Auto-assign agent if configured ──
    var agentName = '';
    if (CONFIG.AUTO_ASSIGN_METHOD === 'round-robin' && CONFIG.AGENTS && CONFIG.AGENTS.length > 0) {
      try {
        var props = PropertiesService.getScriptProperties();
        var lastIndexStr = props.getProperty('LAST_ASSIGNED_AGENT_INDEX');
        var nextIndex = 0;
        if (lastIndexStr !== null) {
          nextIndex = (parseInt(lastIndexStr, 10) + 1) % CONFIG.AGENTS.length;
        }
        agentName = CONFIG.AGENTS[nextIndex];
        props.setProperty('LAST_ASSIGNED_AGENT_INDEX', String(nextIndex));
      } catch (e) {
        // Fallback if PropertiesService fails
        agentName = CONFIG.AGENTS[0];
      }
    }

    // Parse order date from WooCommerce payload (or fallback to current time)
    var orderDate = new Date();
    if (data.date) {
      var parsedDate = new Date(data.date.replace(/-/g, '/'));
      if (!isNaN(parsedDate.getTime())) {
        orderDate = parsedDate;
      }
    }

    // ── Add row ──
    var newRow = [
      '#' + data.order_id,                     // A: Order ID
      orderDate,                               // B: Date
      data.name || '',                          // C: Name
      phoneDisplay,                             // D: Phone
      products,                                 // E: Products (truncated)
      data.qty || 1,                            // F: Qty
      productsPrice,                            // G: Products price
      deliveryTypeDisplay,                      // H: Delivery type
      wilayaDisplay,                            // I: Wilaya
      data.commune || '',                       // J: Commune
      deliveryFee,                              // K: Delivery fee
      grandTotal,                               // L: Grand total
      'جديد 🆕',                                // M: Status
      agentName,                                // N: Agent
      '',                                       // O: Notes
      '',                                       // P: Confirm date
      '',                                       // Q: ZR Tracking
      '',                                       // R: Call 1
      '',                                       // S: Call 2
      '',                                       // T: Call 3
      waLink,                                   // U: WhatsApp link
      data.order_id,                            // V: Raw WC order ID
    ];
    
    // Insert new order at the top (Row 2, right below headers)
    sheet.insertRowBefore(2);
    sheet.getRange(2, 1, 1, newRow.length).setValues([newRow]);
    var targetRow = 2;
    
    // ── Apply formatting to the new row ──
    applyRowFormatting(sheet, targetRow);
    applyDropdowns(sheet, targetRow);
    updateCommuneDropdown(sheet, targetRow);
    
    // ── Make WhatsApp cell a clickable link ──
    var waCell = sheet.getRange(targetRow, COL.WHATSAPP);
    waCell.setValue('📱 واتساب');
    waCell.setFontColor('#16a34a');
    waCell.setFontWeight('bold');
    
    // Insert hyperlink using RichTextValue
    var richText = SpreadsheetApp.newRichTextValue()
      .setText('📱 واتساب')
      .setLinkUrl(waLink)
      .build();
    waCell.setRichTextValue(richText);
    
    // ── Format phone (as clickable call link tel:+213...) ──
    var phoneCell = sheet.getRange(targetRow, COL.PHONE);
    var telLink = 'tel:+' + phone;
    var phoneText = '📞 ' + phoneDisplay;
    var telRichText = SpreadsheetApp.newRichTextValue()
      .setText(phoneText)
      .setLinkUrl(telLink)
      .build();
    phoneCell.setRichTextValue(telRichText);
    phoneCell.setFontColor('#4f46e5');
    phoneCell.setFontWeight('bold');
    
    // ── Format Date as dd-MM-yyyy ──
    sheet.getRange(targetRow, COL.DATE).setNumberFormat('dd-MM-yyyy HH:mm');
    
    // ── Format currency cells ──
    sheet.getRange(targetRow, COL.PRODUCTS_PRICE).setNumberFormat('#,##0 "د.ج"');
    sheet.getRange(targetRow, COL.DELIVERY_FEE).setNumberFormat('#,##0 "د.ج"');
    sheet.getRange(targetRow, COL.GRAND_TOTAL).setNumberFormat('#,##0 "د.ج"');
    sheet.getRange(targetRow, COL.GRAND_TOTAL).setFontWeight('bold').setFontSize(14);
    
    return ContentService.createTextOutput(JSON.stringify({
      success: true, message: 'Order added', row: targetRow
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false, error: err.toString(), stack: err.stack
    })).setMimeType(ContentService.MimeType.JSON);
  } finally {
    // Always release the lock
    lock.releaseLock();
  }
}

// Also support GET for testing and serve the Agent Web Portal
function doGet(e) {
  // If request has format=json or json=1, return JSON status
  if (e && e.parameter && (e.parameter.json === '1' || e.parameter.format === 'json')) {
    return ContentService.createTextOutput(JSON.stringify({
      status: 'ok',
      message: 'AYRA Sheets Sync is running ✅',
      time: new Date().toISOString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
  
  // Otherwise, serve the HTML dashboard template
  var template = HtmlService.createTemplateFromFile('Index');
  template.agents = CONFIG.AGENTS || ['C1', 'C2'];
  return template.evaluate()
    .setTitle('AYRA Homewear — Agent Portal')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

// ═══════════════════════════════════════════════════════════════
// 3. ON EDIT — Auto-track call attempts, status changes, price recalc
// ═══════════════════════════════════════════════════════════════

function onEdit(e) {
  var sheet = e.source.getActiveSheet();
  if (sheet.getName() !== CONFIG.SHEET_NAME) return;
  
  var row = e.range.getRow();
  var col = e.range.getColumn();
  if (row <= 1) return; // Skip header
  
  var now = new Date();
  var timestamp = Utilities.formatDate(now, 'Africa/Algiers', 'dd/MM HH:mm');
  
  // ── Status changed ──
  if (col === COL.STATUS) {
    var newStatus = e.value || '';
    
    // Auto-fill call attempt timestamps
    if (newStatus.indexOf('محاولة 1') > -1) {
      sheet.getRange(row, COL.CALL_1).setValue(timestamp);
    } else if (newStatus.indexOf('محاولة 2') > -1) {
      sheet.getRange(row, COL.CALL_2).setValue(timestamp);
    } else if (newStatus.indexOf('محاولة 3') > -1) {
      sheet.getRange(row, COL.CALL_3).setValue(timestamp);
    }
    
    // Auto-fill confirmation date
    if (newStatus.indexOf('مؤكد') > -1) {
      sheet.getRange(row, COL.CONFIRM_DATE).setValue(timestamp);
    }
    
    // Apply row color based on status
    applyRowColor(sheet, row, newStatus);
  }
  
  // Agent assigned (status remains جديد 🆕 per request)
  
  // ── Wilaya changed → Recalculate delivery price & update desk list dropdown ──
  if (col === COL.WILAYA || col === COL.DELIVERY_TYPE) {
    recalculateDelivery(sheet, row);
    updateCommuneDropdown(sheet, row);
  }
}

/**
 * Recalculate delivery fee when Wilaya or Delivery Type changes
 */
function recalculateDelivery(sheet, row) {
  var wilayaVal = String(sheet.getRange(row, COL.WILAYA).getValue());
  var deliveryVal = String(sheet.getRange(row, COL.DELIVERY_TYPE).getValue());
  
  // Extract wilaya code from "16 - Alger" format
  var wilayaCode = parseInt(wilayaVal) || 0;
  
  // Determine delivery type
  var deliveryType = 'home';
  if (deliveryVal.indexOf('مكتب') > -1 || deliveryVal.indexOf('desk') > -1) {
    deliveryType = 'desk';
  }
  
  // Get new price
  var newFee = getDeliveryPrice(wilayaCode, deliveryType);
  var productsPrice = parseFloat(sheet.getRange(row, COL.PRODUCTS_PRICE).getValue()) || 0;
  var newTotal = productsPrice + newFee;
  
  // Update cells
  sheet.getRange(row, COL.DELIVERY_FEE).setValue(newFee).setNumberFormat('#,##0 "د.ج"');
  sheet.getRange(row, COL.GRAND_TOTAL).setValue(newTotal).setNumberFormat('#,##0 "د.ج"');
  
  // Flash the cell to show it changed
  sheet.getRange(row, COL.DELIVERY_FEE).setBackground('#dbeafe');
  sheet.getRange(row, COL.GRAND_TOTAL).setBackground('#dbeafe');
  SpreadsheetApp.flush();
  Utilities.sleep(800);
  // Restore based on status
  var status = sheet.getRange(row, COL.STATUS).getValue();
  applyRowColor(sheet, row, status);
}

// ═══════════════════════════════════════════════════════════════
// 4. SEND TO ZR EXPRESS
// ═══════════════════════════════════════════════════════════════

/**
 * Send all confirmed orders to ZR Express
 * Called from the AYRA menu
 */
function sendConfirmedToZR() {
  var ui = SpreadsheetApp.getUi();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) { ui.alert('❌ الورقة غير موجودة'); return; }
  
  var siteUrl = getSiteUrl();
  if (!siteUrl) {
    ui.alert('⚠️ يرجى ضبط SITE_URL في إعدادات CONFIG أو خصائص السكريبت للاتصال بالموقع.');
    return;
  }
  
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) { ui.alert('لا توجد طلبات'); return; }
  
  // Find confirmed orders without tracking
  var data = sheet.getRange(2, 1, lastRow - 1, COL.WC_ORDER_ID).getValues();
  var confirmedRows = [];
  
  for (var i = 0; i < data.length; i++) {
    var status = String(data[i][COL.STATUS - 1]);
    var tracking = String(data[i][COL.ZR_TRACKING - 1]);
    
    if (status.indexOf('مؤكد') > -1 && (!tracking || tracking === '')) {
      confirmedRows.push({
        row: i + 2,
        orderId: String(data[i][COL.WC_ORDER_ID - 1]).replace('#', ''),
        name: data[i][COL.NAME - 1],
        phone: data[i][COL.PHONE - 1],
        products: data[i][COL.PRODUCTS - 1],
        total: data[i][COL.GRAND_TOTAL - 1],
        wilaya: data[i][COL.WILAYA - 1],
        commune: data[i][COL.COMMUNE - 1],
        deliveryType: data[i][COL.DELIVERY_TYPE - 1],
        wcOrderId: data[i][COL.WC_ORDER_ID - 1],
      });
    }
  }
  
  if (confirmedRows.length === 0) {
    ui.alert('✅ لا توجد طلبات مؤكدة في انتظار الإرسال');
    return;
  }
  
  var result = ui.alert(
    '📦 إرسال إلى ZR Express',
    'تم العثور على ' + confirmedRows.length + ' طلب مؤكد.\n\nهل تريد إرسالها إلى ZR Express؟',
    ui.ButtonSet.YES_NO
  );
  
  if (result !== ui.Button.YES) return;
  
  var success = 0, failed = 0, errors = [];
  
  for (var j = 0; j < confirmedRows.length; j++) {
    var order = confirmedRows[j];
    var statusCell = sheet.getRange(order.row, COL.STATUS);
    var originalValidation = statusCell.getDataValidation();
    
    try {
      // Temporarily clear validation to allow "⏳ جاري الإرسال..."
      statusCell.setDataValidation(null);
      statusCell.setValue('⏳ جاري الإرسال...');
      statusCell.setBackground('#fef3c7');
      SpreadsheetApp.flush();
      
      // Call WooCommerce to trigger ZR send
      // (The actual ZR API call is handled by WordPress since it has the full order data)
      // Look up desk ID if it's a desk order
      var deskId = '';
      if (order.deliveryType.indexOf('مكتب') > -1 && order.commune) {
        var wilayaCode = '';
        var match = order.wilaya.match(/^(\d+)/);
        if (match) {
          wilayaCode = match[1];
          if (wilayaCode.length === 1) wilayaCode = '0' + wilayaCode;
        }
        
        var deskSheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('zr_desks');
        if (deskSheet) {
          var dlRow = deskSheet.getLastRow();
          if (dlRow > 1) {
            var dData = deskSheet.getRange(2, 1, dlRow - 1, 3).getValues();
            for (var k = 0; k < dData.length; k++) {
              var dwCode = String(dData[k][0]).trim();
              if (dwCode.length === 1) dwCode = '0' + dwCode;
              
              var dName = String(dData[k][2]).trim();
              if (dwCode === wilayaCode && dName === order.commune.trim()) {
                deskId = String(dData[k][1]); // desk_id is index 1
                break;
              }
            }
          }
        }
      }
      
      // Call WooCommerce to trigger ZR send, passing updated destination details
      var response = sendOrderToZRviaWP(order.wcOrderId, order.deliveryType, order.wilaya, order.commune, deskId);
      
      if (response && response.success) {
        var trackingId = response.tracking || response.barcode || 'SENT';
        sheet.getRange(order.row, COL.ZR_TRACKING).setValue(trackingId);
        
        // Restore validation and set final status
        if (originalValidation) statusCell.setDataValidation(originalValidation);
        statusCell.setValue('📦 تم الإرسال');
        applyRowColor(sheet, order.row, '📦 تم الإرسال');
        success++;
      } else {
        var errMsg = response ? (response.error || 'Unknown error') : 'No response';
        
        // Restore validation and reset status
        if (originalValidation) statusCell.setDataValidation(originalValidation);
        statusCell.setValue('✅ مؤكد');
        sheet.getRange(order.row, COL.NOTES)
          .setValue(sheet.getRange(order.row, COL.NOTES).getValue() + ' | ZR Error: ' + errMsg);
        applyRowColor(sheet, order.row, '✅ مؤكد');
        errors.push('#' + order.orderId + ': ' + errMsg);
        failed++;
      }
    } catch (err) {
      // Restore validation and reset status on catch
      if (originalValidation) statusCell.setDataValidation(originalValidation);
      statusCell.setValue('✅ مؤكد');
      applyRowColor(sheet, order.row, '✅ مؤكد');
      errors.push('#' + order.orderId + ': ' + err.toString());
      failed++;
    }
  }
  
  var message = '✅ تم إرسال: ' + success + ' طلب\n';
  if (failed > 0) {
    message += '❌ فشل: ' + failed + ' طلب\n\n' + errors.join('\n');
  }
  ui.alert('📦 نتيجة الإرسال', message, ui.ButtonSet.OK);
}

/**
 * Send order to ZR via WordPress AJAX endpoint
 */
function sendOrderToZRviaWP(wcOrderId, deliveryType, wilaya, commune, deskId) {
  var siteUrl = getSiteUrl();
  if (!siteUrl) {
    // Fallback: Try direct ZR API
    return sendOrderDirectToZR(wcOrderId);
  }
  
  var url = siteUrl + '/wp-admin/admin-ajax.php';
  var payload = {
    'action': 'ayra_sheets_send_to_zr',
    'order_id': wcOrderId,
    'delivery_type': deliveryType || '',
    'wilaya': wilaya || '',
    'commune': commune || '',
    'desk_id': deskId || '',
    'secret': getSyncSecret()
  };
  
  var options = {
    'method': 'post',
    'payload': payload,
    'muteHttpExceptions': true
  };
  
  var response = UrlFetchApp.fetch(url, options);
  return JSON.parse(response.getContentText());
}

/**
 * Fallback: Send directly to ZR Express API from Apps Script
 * (Used if WordPress endpoint is not available)
 */
function sendOrderDirectToZR(wcOrderId) {
  // This is a simplified version — the full logic is in WordPress
  return { success: false, error: 'يرجى إعداد رابط الموقع في خصائص السكريبت (SITE_URL)' };
}

// ═══════════════════════════════════════════════════════════════
// 5. FORMATTING & STYLING
// ═══════════════════════════════════════════════════════════════

/**
 * Apply conditional formatting rules
 */
function applyFormatting() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) return;
  
  // Clear existing conditional formats
  sheet.clearConditionalFormatRules();
  
  var lastRow = Math.max(sheet.getLastRow(), 100);
  var range = sheet.getRange(2, 1, lastRow, COL.WC_ORDER_ID);
  
  var rules = [];
  
  // 🟢 Confirmed — green
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="✅ مؤكد"')
    .setBackground('#dcfce7')
    .setRanges([range]).build());
  
  // 🔴 Cancelled — red
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="❌ ملغي"')
    .setBackground('#fee2e2')
    .setRanges([range]).build());
  
  // 🟡 New — yellow
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="جديد 🆕"')
    .setBackground('#fef9c3')
    .setRanges([range]).build());
  
  // 🟠 Attempt 1 — light orange
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="محاولة 1 📞"')
    .setBackground('#fed7aa')
    .setRanges([range]).build());
  
  // 🟠 Attempt 2 — orange
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="محاولة 2 📞"')
    .setBackground('#fdba74')
    .setRanges([range]).build());
  
  // 🟠 Attempt 3 — dark orange
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="محاولة 3 📞"')
    .setBackground('#fb923c')
    .setRanges([range]).build());
  
  // 🔵 Sent to ZR — blue
  rules.push(SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied('=$M2="📦 تم الإرسال"')
    .setBackground('#dbeafe')
    .setRanges([range]).build());
  
  sheet.setConditionalFormatRules(rules);
}

/**
 * Apply status dropdown to a specific row
 */
function applyDropdowns(sheet, row) {
  // Status dropdown
  var statusRule = SpreadsheetApp.newDataValidation()
    .requireValueInList([
      'جديد 🆕',
      'محاولة 1 📞',
      'محاولة 2 📞',
      'محاولة 3 📞',
      '✅ مؤكد',
      '❌ ملغي',
      '📦 تم الإرسال'
    ], true)
    .setAllowInvalid(false)
    .build();
  sheet.getRange(row, COL.STATUS).setDataValidation(statusRule);
  
  // Delivery type dropdown
  var deliveryRule = SpreadsheetApp.newDataValidation()
    .requireValueInList(['منزل 🏠', 'مكتب 📦'], true)
    .setAllowInvalid(false)
    .build();
  sheet.getRange(row, COL.DELIVERY_TYPE).setDataValidation(deliveryRule);
  
  // Wilaya dropdown (code - name format)
  var wilayaOptions = [];
  var codes = Object.keys(WILAYA_NAMES).map(Number).sort(function(a,b){return a-b;});
  for (var i = 0; i < codes.length; i++) {
    wilayaOptions.push(codes[i] + ' - ' + WILAYA_NAMES[codes[i]]);
  }
  var wilayaRule = SpreadsheetApp.newDataValidation()
    .requireValueInList(wilayaOptions, true)
    .setAllowInvalid(true) // Allow manual entry too
    .build();
  sheet.getRange(row, COL.WILAYA).setDataValidation(wilayaRule);
  
  // Agent (Confirmateur) dropdown
  if (CONFIG.AGENTS && CONFIG.AGENTS.length > 0) {
    var agentRule = SpreadsheetApp.newDataValidation()
      .requireValueInList(CONFIG.AGENTS, true)
      .setAllowInvalid(false)
      .build();
    sheet.getRange(row, COL.AGENT).setDataValidation(agentRule);
  }
}

/**
 * Apply row formatting (borders, alignment)
 */
function applyRowFormatting(sheet, row) {
  var range = sheet.getRange(row, 1, 1, COL.WC_ORDER_ID);
  range.setVerticalAlignment('middle');
  range.setHorizontalAlignment('center');
  range.setFontSize(12); // Made larger (from 10 to 12)
  range.setBorder(false, false, true, false, false, false, '#e5e7eb', SpreadsheetApp.BorderStyle.SOLID);
  
  // Bold the order ID
  sheet.getRange(row, COL.ORDER_ID).setFontWeight('bold').setFontSize(12);
  
  // Product name — left align, wrap, size 11 (made larger from 9)
  sheet.getRange(row, COL.PRODUCTS).setHorizontalAlignment('right').setFontSize(11).setWrap(true);
  
  // Notes — left align, size 11
  sheet.getRange(row, COL.NOTES).setHorizontalAlignment('right').setFontSize(11);
}

/**
 * Apply row color based on status
 */
function applyRowColor(sheet, row, status) {
  var range = sheet.getRange(row, 1, 1, COL.WC_ORDER_ID);
  var color = '#ffffff'; // default white
  
  if (status.indexOf('مؤكد') > -1)         color = '#dcfce7'; // green
  else if (status.indexOf('ملغي') > -1)     color = '#fee2e2'; // red
  else if (status.indexOf('جديد') > -1)     color = '#fef9c3'; // yellow
  else if (status.indexOf('محاولة 3') > -1) color = '#fb923c'; // dark orange
  else if (status.indexOf('محاولة 2') > -1) color = '#fdba74'; // orange
  else if (status.indexOf('محاولة 1') > -1) color = '#fed7aa'; // light orange
  else if (status.indexOf('تم الإرسال') > -1) color = '#dbeafe'; // blue
  else if (status.indexOf('جاري') > -1)     color = '#fef3c7'; // loading yellow
  
  range.setBackground(color);
}

// ═══════════════════════════════════════════════════════════════
// 6. STATISTICS
// ═══════════════════════════════════════════════════════════════

function updateStats() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(CONFIG.SHEET_NAME);
  var statsSheet = ss.getSheetByName(CONFIG.STATS_SHEET);
  if (!sheet || !statsSheet) return;
  
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return;
  
  var data = sheet.getRange(2, 1, lastRow - 1, COL.WC_ORDER_ID).getValues();
  var today = Utilities.formatDate(new Date(), 'Africa/Algiers', 'dd/MM/yyyy');
  
  var total = data.length;
  var todayOrders = 0;
  var confirmed = 0, cancelled = 0, sent = 0, pending = 0;
  var totalRevenue = 0;
  var wilayaCounts = {};
  var productCounts = {};
  
  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    var status = String(row[COL.STATUS - 1]);
    var orderDate = row[COL.DATE - 1];
    
    // Today count
    if (orderDate instanceof Date) {
      var d = Utilities.formatDate(orderDate, 'Africa/Algiers', 'dd/MM/yyyy');
      if (d === today) todayOrders++;
    }
    
    // Status counts
    if (status.indexOf('مؤكد') > -1) { confirmed++; totalRevenue += parseFloat(row[COL.GRAND_TOTAL - 1]) || 0; }
    else if (status.indexOf('ملغي') > -1) cancelled++;
    else if (status.indexOf('تم الإرسال') > -1) { sent++; totalRevenue += parseFloat(row[COL.GRAND_TOTAL - 1]) || 0; }
    else pending++;
    
    // Wilaya counts
    var wilaya = String(row[COL.WILAYA - 1]).split(' - ')[1] || String(row[COL.WILAYA - 1]);
    if (wilaya) wilayaCounts[wilaya] = (wilayaCounts[wilaya] || 0) + 1;
    
    // Product counts
    var products = String(row[COL.PRODUCTS - 1]);
    if (products) {
      var items = products.split(' | ');
      for (var p = 0; p < items.length; p++) {
        var pName = items[p].replace(/\s*×\s*\d+$/, '').trim();
        if (pName) productCounts[pName] = (productCounts[pName] || 0) + 1;
      }
    }
  }
  
  var confirmRate = total > 0 ? Math.round(((confirmed + sent) / total) * 100) : 0;
  var cancelRate = total > 0 ? Math.round((cancelled / total) * 100) : 0;
  
  // Top wilayas
  var topWilayas = Object.keys(wilayaCounts)
    .sort(function(a,b) { return wilayaCounts[b] - wilayaCounts[a]; })
    .slice(0, 5)
    .map(function(w) { return w + ' (' + wilayaCounts[w] + ')'; })
    .join('، ');
  
  // Top products
  var topProducts = Object.keys(productCounts)
    .sort(function(a,b) { return productCounts[b] - productCounts[a]; })
    .slice(0, 5)
    .map(function(p) { return p + ' (' + productCounts[p] + ')'; })
    .join('، ');
  
  // Write stats
  var statsData = [
    ['📦 إجمالي الطلبات', total, ''],
    ['📅 طلبات اليوم', todayOrders, today],
    ['✅ طلبات مؤكدة', confirmed, confirmRate + '% نسبة التأكيد'],
    ['❌ طلبات ملغية', cancelled, cancelRate + '% نسبة الإلغاء'],
    ['📦 تم الإرسال', sent, ''],
    ['⏳ في الانتظار', pending, ''],
    ['💰 إجمالي المبيعات', totalRevenue, 'د.ج (مؤكدة + مرسلة)'],
    ['', '', ''],
    ['🏙️ أكثر الولايات', '', topWilayas],
    ['👕 أكثر المنتجات', '', topProducts],
  ];
  
  statsSheet.getRange(4, 1, statsData.length, 3).setValues(statsData);
  
  // Format revenue
  statsSheet.getRange(10, 2).setNumberFormat('#,##0 "د.ج"');
}

// ═══════════════════════════════════════════════════════════════
// 7. HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

/**
 * Get delivery price for a wilaya code and type
 */
function getDeliveryPrice(wilayaCode, type) {
  var pricing = DELIVERY_PRICES[wilayaCode];
  if (!pricing) return 0;
  return type === 'desk' ? (pricing.desk || 0) : (pricing.home || 0);
}

/**
 * Truncate product names to fit ZR Express limit (50 chars each)
 * Input: "CROP TOP / PREMIUM QUALITE / NOIR × 2 | ENSEMBLE VELOUR PREMIUM × 1"
 * Output: Truncated version where each product name is ≤ 50 chars
 */
function truncateProductNames(productsStr) {
  if (!productsStr) return '';
  var limit = CONFIG.ZR_PRODUCT_NAME_LIMIT;
  
  var items = productsStr.split(' | ');
  var result = [];
  
  for (var i = 0; i < items.length; i++) {
    var item = items[i].trim();
    // Split name and quantity: "PRODUCT NAME × 2"
    var parts = item.split(/\s*×\s*/);
    var name = parts[0] || '';
    var qty = parts[1] || '';
    
    // Truncate name if needed
    if (name.length > limit) {
      name = name.substring(0, limit - 2) + '..';
    }
    
    result.push(qty ? name + ' × ' + qty : name);
  }
  
  return result.join(' | ');
}

// ═══════════════════════════════════════════════════════════════
// 8. REMOVE DUPLICATES
// ═══════════════════════════════════════════════════════════════

/**
 * Remove duplicate orders — keeps the FIRST row for each order ID
 * Called from 🚀 AYRA menu
 */
function removeDuplicates() {
  var ui = SpreadsheetApp.getUi();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) { ui.alert('❌ الورقة غير موجودة'); return; }
  
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) { ui.alert('لا توجد بيانات'); return; }
  
  // Read all WC Order IDs (column V) and display IDs (column A)
  var displayIds = sheet.getRange(2, COL.ORDER_ID, lastRow - 1, 1).getValues();
  var wcIds = sheet.getRange(2, COL.WC_ORDER_ID, lastRow - 1, 1).getValues();
  
  var seen = {};
  var rowsToDelete = []; // Store row numbers to delete (0-indexed from data)
  
  for (var i = 0; i < displayIds.length; i++) {
    // Normalize: strip # and whitespace
    var id = String(wcIds[i][0]).trim();
    if (!id || id === '' || id === 'undefined') {
      id = String(displayIds[i][0]).replace('#', '').trim();
    }
    
    if (!id || id === '') continue;
    
    if (seen[id]) {
      // This is a duplicate — mark for deletion
      rowsToDelete.push(i + 2); // +2 because row 1 is header, i is 0-indexed
    } else {
      seen[id] = true;
    }
  }
  
  if (rowsToDelete.length === 0) {
    ui.alert('✅ لا توجد مكررات!');
    return;
  }
  
  // Confirm before deleting
  var result = ui.alert(
    '🗑️ حذف المكررات',
    'تم العثور على ' + rowsToDelete.length + ' صف مكرر.\n\nهل تريد حذفها؟ (سيتم الاحتفاظ بالنسخة الأولى من كل طلب)',
    ui.ButtonSet.YES_NO
  );
  
  if (result !== ui.Button.YES) return;
  
  // Delete from bottom to top so row numbers don't shift
  rowsToDelete.sort(function(a, b) { return b - a; });
  
  for (var j = 0; j < rowsToDelete.length; j++) {
    sheet.deleteRow(rowsToDelete[j]);
  }
  
  ui.alert('✅ تم حذف ' + rowsToDelete.length + ' صف مكرر بنجاح!');
}

/**
 * Split/distribute new unassigned orders among the active agents list
 * Called from 🚀 AYRA menu
 */
function splitNewOrders() {
  var ui = SpreadsheetApp.getUi();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) { ui.alert('❌ الورقة غير موجودة'); return; }
  
  var agents = CONFIG.AGENTS;
  if (!agents || agents.length === 0) {
    ui.alert('⚠️ يرجى تحديد قائمة المسؤولين (AGENTS) في الكود أولاً.');
    return;
  }
  
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) {
    ui.alert('لا توجد بيانات لتقسيمها.');
    return;
  }
  
  // Find all new, unassigned orders
  var statuses = sheet.getRange(2, COL.STATUS, lastRow - 1, 1).getValues();
  var currentAgents = sheet.getRange(2, COL.AGENT, lastRow - 1, 1).getValues();
  
  var unassignedRowIndices = [];
  for (var i = 0; i < statuses.length; i++) {
    var status = String(statuses[i][0]).trim();
    var agent = String(currentAgents[i][0]).trim();
    
    // Check if status is "جديد 🆕" and agent is empty
    if (status === 'جديد 🆕' && (!agent || agent === '' || agent === 'undefined')) {
      unassignedRowIndices.push(i + 2); // row number (2-based)
    }
  }
  
  if (unassignedRowIndices.length === 0) {
    ui.alert('✅ لا توجد طلبات جديدة غير موزعة حالياً.');
    return;
  }
  
  // Confirm action
  var response = ui.alert(
    '👥 تقسيم الطلبات',
    'تم العثور على ' + unassignedRowIndices.length + ' طلب جديد غير موزع.\n' +
    'سيتم توزيعها بالتساوي على المسؤولين: ' + agents.join(', ') + '.\n\nهل تريد الاستمرار؟',
    ui.ButtonSet.YES_NO
  );
  
  if (response !== ui.Button.YES) return;
  
  // Distribute round-robin
  var props = PropertiesService.getScriptProperties();
  var lastIndex = parseInt(props.getProperty('LAST_ASSIGNED_AGENT_INDEX') || '0', 10);
  
  var assignedCount = {};
  for (var k = 0; k < agents.length; k++) {
    assignedCount[agents[k]] = 0;
  }
  
  var now = new Date();
  var timestamp = Utilities.formatDate(now, 'Africa/Algiers', 'dd/MM HH:mm');
  
  for (var j = 0; j < unassignedRowIndices.length; j++) {
    var agentIndex = (lastIndex + j) % agents.length;
    var agentName = agents[agentIndex];
    var rowNum = unassignedRowIndices[j];
    
    sheet.getRange(rowNum, COL.AGENT).setValue(agentName);
    
    assignedCount[agentName]++;
  }
  
  // Save the last assigned agent index
  var newLastIndex = (lastIndex + unassignedRowIndices.length) % agents.length;
  props.setProperty('LAST_ASSIGNED_AGENT_INDEX', String(newLastIndex));
  
  // Build report
  var report = '✅ تم توزيع الطلبات بنجاح!\n\nالتفاصيل:\n';
  for (var name in assignedCount) {
    report += '- ' + name + ': ' + assignedCount[name] + ' طلب\n';
  }
  
  ui.alert('👥 نتيجة التوزيع', report, ui.ButtonSet.OK);
}

/**
 * Sync StopDesk pickup points/desks from WordPress database
 * Called from 🚀 AYRA menu
 */
function syncDesks() {
  var ui = SpreadsheetApp.getUi();
  var siteUrl = getSiteUrl();
  if (!siteUrl) {
    ui.alert('⚠️ يرجى ضبط SITE_URL في CONFIG (أو خصائص المشروع) أولاً.');
    return;
  }
  
  var secret = getSyncSecret();
  var url = siteUrl + '/wp-admin/admin-ajax.php?action=ayra_sheets_get_desks&secret=' + encodeURIComponent(secret);
  
  try {
    ui.showModalDialog(
      HtmlService.createHtmlOutput('⏳ جاري جلب مكاتب StopDesk من الموقع... يرجى الانتظار.'),
      'جاري العمل'
    );
    
    var response = UrlFetchApp.fetch(url, { method: 'get', muteHttpExceptions: true });
    var res = JSON.parse(response.getContentText());
    
    // Close dialog
    ui.showModalDialog(HtmlService.createHtmlOutput('<script>google.script.host.close();</script>'), 'جاري العمل');
    
    if (!res || !res.success || !res.data || !res.data.desks) {
      ui.alert('❌ فشل جلب المكاتب: ' + (res && res.data ? res.data.error : 'استجابة غير صالحة'));
      return;
    }
    
    var desksByWilaya = res.data.desks;
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var deskSheet = ss.getSheetByName('zr_desks');
    if (!deskSheet) {
      deskSheet = ss.insertSheet('zr_desks');
      deskSheet.hideSheet(); // Hide the sheet to keep the workspace clean
    }
    
    deskSheet.clear();
    // Headers
    deskSheet.appendRow(['wilaya_code', 'desk_id', 'desk_name', 'commune', 'address']);
    
    var rows = [];
    for (var wCode in desksByWilaya) {
      var desks = desksByWilaya[wCode];
      for (var i = 0; i < desks.length; i++) {
        var d = desks[i];
        rows.push([
          wCode,
          d.id,
          d.name + ' (' + d.commune + ')', // Display name: Desk Name (District)
          d.commune,
          d.address
        ]);
      }
    }
    
    if (rows.length > 0) {
      deskSheet.getRange(2, 1, rows.length, 5).setValues(rows);
    }
    
    ui.alert('✅ تم مزامنة ' + rows.length + ' مكتب StopDesk بنجاح!');
    
  } catch (err) {
    ui.alert('❌ حدث خطأ أثناء الاتصال بالموقع: ' + err.toString());
  }
}

/**
 * Sync communes from WordPress database to a hidden sheet
 * Called from 🚀 AYRA menu
 */
function syncCommunes() {
  var ui = SpreadsheetApp.getUi();
  var siteUrl = getSiteUrl();
  if (!siteUrl) {
    ui.alert('⚠️ يرجى ضبط SITE_URL في CONFIG (أو خصائص المشروع) أولاً.');
    return;
  }
  
  var secret = getSyncSecret();
  var url = siteUrl + '/wp-admin/admin-ajax.php?action=ayra_sheets_get_communes&secret=' + encodeURIComponent(secret);
  
  try {
    ui.showModalDialog(
      HtmlService.createHtmlOutput('⏳ جاري جلب البلديات من الموقع... يرجى الانتظار.'),
      'جاري العمل'
    );
    
    var response = UrlFetchApp.fetch(url, { method: 'get', muteHttpExceptions: true });
    var res = JSON.parse(response.getContentText());
    
    // Close dialog
    ui.showModalDialog(HtmlService.createHtmlOutput('<script>google.script.host.close();</script>'), 'جاري العمل');
    
    if (!res || !res.success || !res.data || !res.data.communes) {
      ui.alert('❌ فشل جلب البلديات: ' + (res && res.data ? res.data.error : 'استجابة غير صالحة'));
      return;
    }
    
    var communesByWilaya = res.data.communes;
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var communeSheet = ss.getSheetByName('zr_communes');
    if (!communeSheet) {
      communeSheet = ss.insertSheet('zr_communes');
      communeSheet.hideSheet(); // Hide the sheet to keep the workspace clean
    }
    
    communeSheet.clear();
    // Headers
    communeSheet.appendRow(['wilaya_code', 'commune_name']);
    
    var rows = [];
    for (var wCode in communesByWilaya) {
      var list = communesByWilaya[wCode];
      for (var i = 0; i < list.length; i++) {
        rows.push([
          wCode,
          list[i]
        ]);
      }
    }
    
    if (rows.length > 0) {
      communeSheet.getRange(2, 1, rows.length, 2).setValues(rows);
    }
    
    ui.alert('✅ تم مزامنة ' + rows.length + ' بلدية بنجاح!');
    
  } catch (err) {
    ui.alert('❌ حدث خطأ أثناء الاتصال بالموقع: ' + err.toString());
  }
}

/**
 * Dynamically updates the Commune cell's validation.
 * If delivery type is "مكتب 📦", it loads the StopDesks of that Wilaya as dropdown options.
 * If delivery type is "منزل 🏠", it loads the Communes of that Wilaya as dropdown options.
 */
function updateCommuneDropdown(sheet, row) {
  var deliveryType = String(sheet.getRange(row, COL.DELIVERY_TYPE).getValue()).trim();
  var wilayaVal = String(sheet.getRange(row, COL.WILAYA).getValue()).trim();
  var communeCell = sheet.getRange(row, COL.COMMUNE);
  
  if (!deliveryType) {
    communeCell.clearDataValidations();
    return;
  }
  
  // Get wilaya code (e.g. "16 - Alger" -> "16")
  var wilayaCode = '';
  if (wilayaVal) {
    var match = wilayaVal.match(/^(\d+)/);
    if (match) {
      wilayaCode = match[1];
      // Pad with leading zero if single digit (e.g. "6" -> "06")
      if (wilayaCode.length === 1) {
        wilayaCode = '0' + wilayaCode;
      }
    }
  }
  
  if (!wilayaCode) {
    communeCell.clearDataValidations();
    return;
  }
  
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var options = [];
  
  if (deliveryType.indexOf('مكتب') > -1) {
    // Read desks from hidden sheet "zr_desks"
    var deskSheet = ss.getSheetByName('zr_desks');
    if (!deskSheet) {
      communeCell.clearDataValidations();
      return;
    }
    
    var lastRow = deskSheet.getLastRow();
    if (lastRow <= 1) {
      communeCell.clearDataValidations();
      return;
    }
    
    var data = deskSheet.getRange(2, 1, lastRow - 1, 3).getValues();
    for (var i = 0; i < data.length; i++) {
      var rowWilayaCode = String(data[i][0]).trim();
      if (rowWilayaCode.length === 1) rowWilayaCode = '0' + rowWilayaCode;
      
      if (rowWilayaCode === wilayaCode) {
        options.push(String(data[i][2]).trim()); // desk_name is index 2
      }
    }
  } else if (deliveryType.indexOf('منزل') > -1) {
    // Read communes from hidden sheet "zr_communes"
    var communeSheet = ss.getSheetByName('zr_communes');
    if (!communeSheet) {
      communeCell.clearDataValidations();
      return;
    }
    
    var lastRowCommune = communeSheet.getLastRow();
    if (lastRowCommune <= 1) {
      communeCell.clearDataValidations();
      return;
    }
    
    var dataCommune = communeSheet.getRange(2, 1, lastRowCommune - 1, 2).getValues();
    for (var j = 0; j < dataCommune.length; j++) {
      var rowWilayaCodeCommune = String(dataCommune[j][0]).trim();
      if (rowWilayaCodeCommune.length === 1) rowWilayaCodeCommune = '0' + rowWilayaCodeCommune;
      
      if (rowWilayaCodeCommune === wilayaCode) {
        options.push(String(dataCommune[j][1]).trim()); // commune_name is index 1
      }
    }
  }
  
  if (options.length > 0) {
    options.sort();
    var rule = SpreadsheetApp.newDataValidation()
      .requireValueInList(options, true)
      .setAllowInvalid(true) // Allow typing too if needed
      .build();
    communeCell.setDataValidation(rule);
  } else {
    communeCell.clearDataValidations();
  }
}

// ═══════════════════════════════════════════════════════════════
// 9. AGENT WEB PORTAL BACKEND APIs
// ═══════════════════════════════════════════════════════════════

function portal_getAgentOrders(agentName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) return [];
  
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return [];
  
  // ── Pre-load all desks and communes once to avoid N+1 query problem ──
  var allDesks = {};
  var deskSheet = ss.getSheetByName('zr_desks');
  if (deskSheet) {
    var lastRowDesk = deskSheet.getLastRow();
    if (lastRowDesk > 1) {
      var dataDesk = deskSheet.getRange(2, 1, lastRowDesk - 1, 3).getValues();
      for (var k = 0; k < dataDesk.length; k++) {
        var wCode = String(dataDesk[k][0]).trim();
        if (wCode.length === 1) wCode = '0' + wCode;
        if (!allDesks[wCode]) allDesks[wCode] = [];
        allDesks[wCode].push(String(dataDesk[k][2]).trim());
      }
    }
  }

  var allCommunes = {};
  var communeSheet = ss.getSheetByName('zr_communes');
  if (communeSheet) {
    var lastRowCommune = communeSheet.getLastRow();
    if (lastRowCommune > 1) {
      var dataCommune = communeSheet.getRange(2, 1, lastRowCommune - 1, 2).getValues();
      for (var l = 0; l < dataCommune.length; l++) {
        var wCode = String(dataCommune[l][0]).trim();
        if (wCode.length === 1) wCode = '0' + wCode;
        if (!allCommunes[wCode]) allCommunes[wCode] = [];
        allCommunes[wCode].push(String(dataCommune[l][1]).trim());
      }
    }
  }
  
  var maxCol = sheet.getLastColumn();
  var fetchColCount = Math.max(maxCol, COL.WC_ORDER_ID);
  var values = sheet.getRange(2, 1, lastRow - 1, fetchColCount).getValues();
  var orders = [];
  
  for (var i = 0; i < values.length; i++) {
    var row = values[i];
    var rowAgent = String(row[COL.AGENT - 1]).trim();
    if (rowAgent === agentName) {
      var orderDate = row[COL.DATE - 1];
      var dateStr = '';
      if (orderDate instanceof Date) {
        dateStr = Utilities.formatDate(orderDate, 'Africa/Algiers', 'dd-MM-yyyy HH:mm');
      } else {
        dateStr = String(orderDate);
      }
      
      var deliveryType = String(row[COL.DELIVERY_TYPE - 1]);
      var wilaya = String(row[COL.WILAYA - 1]);
      
      // Look up communes/desks in memory
      var wilayaCode = '';
      if (wilaya) {
        var match = String(wilaya).match(/^(\d+)/);
        if (match) {
          wilayaCode = match[1];
          if (wilayaCode.length === 1) wilayaCode = '0' + wilayaCode;
        }
      }
      
      var options = [];
      if (wilayaCode) {
        if (deliveryType.indexOf('مكتب') > -1) {
          options = allDesks[wilayaCode] || [];
        } else {
          options = allCommunes[wilayaCode] || [];
        }
      }
      options.sort();
      
      orders.push({
        rowNum: i + 2, // 2-indexed row number
        orderId: String(row[COL.ORDER_ID - 1]),
        date: dateStr,
        name: String(row[COL.NAME - 1]),
        phone: String(row[COL.PHONE - 1]),
        products: String(row[COL.PRODUCTS - 1]),
        qty: row[COL.QTY - 1],
        productsPrice: row[COL.PRODUCTS_PRICE - 1],
        deliveryType: deliveryType,
        wilaya: wilaya,
        commune: String(row[COL.COMMUNE - 1]),
        deliveryFee: row[COL.DELIVERY_FEE - 1],
        grandTotal: row[COL.GRAND_TOTAL - 1],
        status: String(row[COL.STATUS - 1]),
        notes: String(row[COL.NOTES - 1]),
        confirmDate: String(row[COL.CONFIRM_DATE - 1]),
        zrTracking: String(row[COL.ZR_TRACKING - 1]),
        call1: String(row[COL.CALL_1 - 1]),
        call2: String(row[COL.CALL_2 - 1]),
        call3: String(row[COL.CALL_3 - 1]),
        whatsappLink: String(row[COL.WHATSAPP - 1]),
        communeOptions: options
      });
    }
  }
  
  // Sort orders by row number descending (newest first, since WooCommerce inserts them at row 2)
  orders.sort(function(a, b) {
    return b.rowNum - a.rowNum;
  });
  
  return orders;
}

function portal_updateOrderField(rowNum, colName, value) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) throw new Error('Sheet not found');
  
  var colIndex = COL[colName];
  if (!colIndex) throw new Error('Invalid column name: ' + colName);
  
  sheet.getRange(rowNum, colIndex).setValue(value);
  
  // Custom logic based on column
  if (colName === 'STATUS') {
    var now = new Date();
    var timestamp = Utilities.formatDate(now, 'Africa/Algiers', 'dd/MM HH:mm');
    if (value.indexOf('مؤكد') > -1) {
      sheet.getRange(rowNum, COL.CONFIRM_DATE).setValue(timestamp);
    }
    applyRowColor(sheet, rowNum, value);
    updateStats(); // Auto-update statistics dashboard
  }
  
  return { success: true };
}

function portal_logCallAttempt(rowNum, attemptNum) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) throw new Error('Sheet not found');
  
  var now = new Date();
  var timestamp = Utilities.formatDate(now, 'Africa/Algiers', 'dd/MM HH:mm');
  
  var colIndex;
  var statusValue;
  
  if (attemptNum === 1) {
    colIndex = COL.CALL_1;
    statusValue = 'محاولة 1 📞';
  } else if (attemptNum === 2) {
    colIndex = COL.CALL_2;
    statusValue = 'محاولة 2 📞';
  } else if (attemptNum === 3) {
    colIndex = COL.CALL_3;
    statusValue = 'محاولة 3 📞';
  } else {
    throw new Error('Invalid attempt number: ' + attemptNum);
  }
  
  sheet.getRange(rowNum, colIndex).setValue(timestamp);
  sheet.getRange(rowNum, COL.STATUS).setValue(statusValue);
  applyRowColor(sheet, rowNum, statusValue);
  updateStats();
  
  return { success: true, timestamp: timestamp, status: statusValue };
}

function portal_sendOrderToZR(rowNum) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) throw new Error('Sheet not found');
  
  var statusCell = sheet.getRange(rowNum, COL.STATUS);
  var trackingVal = String(sheet.getRange(rowNum, COL.ZR_TRACKING).getValue()).trim();
  if (trackingVal && trackingVal !== '') {
    return { success: true, tracking: trackingVal, message: 'Already sent to ZR Express' };
  }
  
  var status = String(statusCell.getValue());
  if (status.indexOf('مؤكد') === -1) {
    throw new Error('Order must be Confirmed (مؤكد) before sending to ZR Express');
  }
  
  var wcOrderId = sheet.getRange(rowNum, COL.WC_ORDER_ID).getValue();
  var deliveryType = String(sheet.getRange(rowNum, COL.DELIVERY_TYPE).getValue());
  var wilaya = String(sheet.getRange(rowNum, COL.WILAYA).getValue());
  var commune = String(sheet.getRange(rowNum, COL.COMMUNE).getValue());
  
  var originalValidation = statusCell.getDataValidation();
  
  try {
    statusCell.setDataValidation(null);
    statusCell.setValue('⏳ جاري الإرسال...');
    statusCell.setBackground('#fef3c7');
    SpreadsheetApp.flush();
    
    // Look up desk ID
    var deskId = '';
    if (deliveryType.indexOf('مكتب') > -1 && commune) {
      var wilayaCode = '';
      var match = wilaya.match(/^(\d+)/);
      if (match) {
        wilayaCode = match[1];
        if (wilayaCode.length === 1) wilayaCode = '0' + wilayaCode;
      }
      
      var deskSheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('zr_desks');
      if (deskSheet) {
        var dlRow = deskSheet.getLastRow();
        if (dlRow > 1) {
          var dData = deskSheet.getRange(2, 1, dlRow - 1, 3).getValues();
          for (var k = 0; k < dData.length; k++) {
            var dwCode = String(dData[k][0]).trim();
            if (dwCode.length === 1) dwCode = '0' + dwCode;
            
            var dName = String(dData[k][2]).trim();
            if (dwCode === wilayaCode && dName === commune.trim()) {
              deskId = String(dData[k][1]);
              break;
            }
          }
        }
      }
    }
    
    var response = sendOrderToZRviaWP(wcOrderId, deliveryType, wilaya, commune, deskId);
    
    if (response && response.success) {
      var trackingId = response.tracking || response.barcode || 'SENT';
      sheet.getRange(rowNum, COL.ZR_TRACKING).setValue(trackingId);
      
      if (originalValidation) statusCell.setDataValidation(originalValidation);
      statusCell.setValue('📦 تم الإرسال');
      applyRowColor(sheet, rowNum, '📦 تم الإرسال');
      updateStats();
      return { success: true, tracking: trackingId };
    } else {
      var errMsg = response ? (response.error || 'Unknown error') : 'No response';
      if (originalValidation) statusCell.setDataValidation(originalValidation);
      statusCell.setValue('✅ مؤكد');
      applyRowColor(sheet, rowNum, '✅ مؤكد');
      
      var notesRange = sheet.getRange(rowNum, COL.NOTES);
      notesRange.setValue((notesRange.getValue() ? notesRange.getValue() + ' | ' : '') + 'ZR Error: ' + errMsg);
      
      return { success: false, error: errMsg };
    }
  } catch (err) {
    if (originalValidation) statusCell.setDataValidation(originalValidation);
    statusCell.setValue('✅ مؤكد');
    applyRowColor(sheet, rowNum, '✅ مؤكد');
    return { success: false, error: err.toString() };
  }
}

function getCommunesOrDesks(wilayaVal, deliveryType) {
  var wilayaCode = '';
  if (wilayaVal) {
    var match = String(wilayaVal).match(/^(\d+)/);
    if (match) {
      wilayaCode = match[1];
      if (wilayaCode.length === 1) wilayaCode = '0' + wilayaCode;
    }
  }
  
  if (!wilayaCode) return [];
  
  var isDesk = deliveryType.indexOf('مكتب') > -1;
  var cacheKey = (isDesk ? 'desks_' : 'communes_') + wilayaCode;
  var cache = CacheService.getScriptCache();
  var cachedData = cache.get(cacheKey);
  
  if (cachedData) {
    try {
      return JSON.parse(cachedData);
    } catch(e) {
      // ignore parse error and reload
    }
  }
  
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var options = [];
  
  if (isDesk) {
    var deskSheet = ss.getSheetByName('zr_desks');
    if (!deskSheet) return [];
    var lastRow = deskSheet.getLastRow();
    if (lastRow <= 1) return [];
    var data = deskSheet.getRange(2, 1, lastRow - 1, 3).getValues();
    for (var i = 0; i < data.length; i++) {
      var rowWilayaCode = String(data[i][0]).trim();
      if (rowWilayaCode.length === 1) rowWilayaCode = '0' + rowWilayaCode;
      if (rowWilayaCode === wilayaCode) {
        options.push(String(data[i][2]).trim());
      }
    }
  } else {
    var communeSheet = ss.getSheetByName('zr_communes');
    if (!communeSheet) return [];
    var lastRowCommune = communeSheet.getLastRow();
    if (lastRowCommune <= 1) return [];
    var dataCommune = communeSheet.getRange(2, 1, lastRowCommune - 1, 2).getValues();
    for (var j = 0; j < dataCommune.length; j++) {
      var rowWilayaCodeCommune = String(dataCommune[j][0]).trim();
      if (rowWilayaCodeCommune.length === 1) rowWilayaCodeCommune = '0' + rowWilayaCodeCommune;
      if (rowWilayaCodeCommune === wilayaCode) {
        options.push(String(dataCommune[j][1]).trim());
      }
    }
  }
  
  options.sort();
  
  try {
    cache.put(cacheKey, JSON.stringify(options), 21600); // cache for 6 hours
  } catch(e) {
    // If it's too big, ignore
  }
  
  return options;
}

function portal_updateDeliveryDetails(rowNum, newType, newCommune) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);
  if (!sheet) throw new Error('Sheet not found');
  
  // Update Delivery Type if provided
  if (newType) {
    sheet.getRange(rowNum, COL.DELIVERY_TYPE).setValue(newType);
    recalculateDelivery(sheet, rowNum);
    updateCommuneDropdown(sheet, rowNum);
  }
  
  // Update Commune if provided
  if (newCommune) {
    sheet.getRange(rowNum, COL.COMMUNE).setValue(newCommune);
  }
  
  // Read back the updated values
  var deliveryFee = sheet.getRange(rowNum, COL.DELIVERY_FEE).getValue();
  var grandTotal = sheet.getRange(rowNum, COL.GRAND_TOTAL).getValue();
  var finalType = sheet.getRange(rowNum, COL.DELIVERY_TYPE).getValue();
  var finalCommune = sheet.getRange(rowNum, COL.COMMUNE).getValue();
  var wilaya = sheet.getRange(rowNum, COL.WILAYA).getValue();
  
  // Also get the new list of communes/desks for the dropdown
  var options = getCommunesOrDesks(wilaya, finalType);
  
  updateStats();
  
  return {
    success: true,
    deliveryType: finalType,
    commune: finalCommune,
    deliveryFee: deliveryFee,
    grandTotal: grandTotal,
    options: options
  };
}
