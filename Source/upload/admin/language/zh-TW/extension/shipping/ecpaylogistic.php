<?php
// Heading
$_['heading_title']						= '綠界物流模組';

// Text
$_['text_success']						= '成功：您已成功修改綠界物流模組設定!';
$_['text_edit']							= '綠界物流模組';
$_['text_extension']					= 'Extension';

// Tab
$_['text_general']						= '一般設定';
$_['text_unimart_collection']			= '統一超商取貨付款';
$_['text_fami_collection']				= '全家超商取貨付款';
$_['text_hilife_collection']			= '萊爾富超商取貨付款';
$_['text_unimart']						= '統一超商取貨';
$_['text_fami']							= '全家超商取貨';
$_['text_hilife']						= '萊爾富超商取貨';
$_['text_sender_cellphone']				= '類型為 C2C 時請設定寄件人手機';

// Entry
$_['entry_mid']							= '商店代號(Merchant ID)';
$_['entry_hashkey']						= '金鑰(Hash Key)';
$_['entry_hashiv']						= '向量(Hash IV)';
$_['entry_type']						= '類型';
$_['entry_UNIMART_Collection_fee']		= '統一超商取貨付款運費';
$_['entry_FAMI_Collection_fee']			= '全家取貨付款運費';
$_['entry_HILIFE_Collection_fee']		= '萊爾富取貨付款運費';
$_['entry_geo_zone']					= '適用地區';
$_['entry_status']						= '狀態';
$_['entry_UNIMART_fee']					= '統一超商取貨運費';
$_['entry_FAMI_fee']					= '全家取貨運費';
$_['entry_HILIFE_fee']					= '萊爾富取貨運費';
$_['entry_FreeShippingAmount']			= '多少金額以上免運費';
$_['entry_MinAmount']					= '超商取貨最低金額';
$_['entry_MaxAmount']					= '超商取貨最高金額';
$_['entry_order_status']				= '訂單狀態';
$_['entry_sender_name']					= '寄件人名稱';
$_['entry_sender_cellphone']			= '寄件人手機';

// Error
$_['error_mid']							= '請輸入' . $_['entry_mid'];
$_['error_hashkey']						= '請輸入' . $_['entry_hashkey'];
$_['error_hashiv']						= '請輸入' . $_['entry_hashiv'];
$_['error_UNIMART_Collection_fee']		= '請輸入' . $_['entry_UNIMART_Collection_fee'];
$_['error_FAMI_Collection_fee']			= '請輸入' . $_['entry_FAMI_Collection_fee'];
$_['error_HILIFE_Collection_fee']		= '請輸入' . $_['entry_HILIFE_Collection_fee'];
$_['error_UNIMART_fee']					= '請輸入' . $_['entry_UNIMART_fee'];
$_['error_FAMI_fee']					= '請輸入' . $_['entry_FAMI_fee'];
$_['error_HILIFE_fee']					= '請輸入' . $_['entry_HILIFE_fee'];
$_['error_FreeShippingAmount']			= '請輸入免運費金額';
$_['error_MinAmount']					= '請輸入' . $_['entry_MinAmount'];
$_['error_MaxAmount']					= '請輸入' . $_['entry_MaxAmount'] .'(大於' . $_['entry_MinAmount'] . '且小於19,999)';
$_['error_sender_name']					= '請輸入' . $_['entry_sender_name'];
$_['error_sender_name_length']			= '寄件人名稱最多5個中文字、10個英文字';
$_['error_sender_name_length']			= '寄件人名稱最多5個中文字、10個英文字';

$_['error_sender_cellphone']			= '請輸入' . $_['entry_sender_cellphone'];
$_['error_sender_cellphone_length']		= $_['entry_sender_cellphone'] . '格式錯誤，09開頭、10位數字';

// create_shipping_order
$_['error_shipping_order_exists']		= '物流訂單已存在';
$_['error_order_info']					= '載入訂單資訊失敗';
?>