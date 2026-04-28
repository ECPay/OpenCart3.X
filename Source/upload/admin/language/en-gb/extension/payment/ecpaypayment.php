<?php
// Heading
$_['heading_title'] 				= '綠界金流模組';

// Text
$_['text_ecpaypayment'] 			= '<a href="https://www.ecpay.com.tw/" target="_blank"><img src="view/image/payment/ecpaypayment.png" width="80px" height="24px" /></a>';
$_['ecpaypayment_text_success'] 		= 'Success: You have modified ' . $_['heading_title'] . ' details!';
$_['ecpaypayment_text_extension'] 		= 'Extensions';
$_['ecpaypayment_text_edit'] 			= 'Edit ' . $_['heading_title'];
$_['ecpaypayment_text_enabled'] 		= 'Enabled';
$_['ecpaypayment_text_disabled'] 		= 'Disabled';
$_['ecpaypayment_text_credit'] 			= 'Credit';
$_['ecpaypayment_text_credit_3'] 		= 'Credit(3 Installments)';
$_['ecpaypayment_text_credit_6'] 		= 'Credit(6 Installments)';
$_['ecpaypayment_text_credit_12'] 		= 'Credit(12 Installments)';
$_['ecpaypayment_text_credit_18'] 		= 'Credit(18 Installments)';
$_['ecpaypayment_text_credit_24'] 		= 'Credit(24 Installments)';
$_['ecpaypayment_text_webatm'] 			= 'WEB-ATM';
$_['ecpaypayment_text_atm'] 			= 'ATM';
$_['ecpaypayment_text_cvs'] 			= 'CVS';
$_['ecpaypayment_text_barcode'] 		= 'BARCODE';
$_['ecpaypayment_text_weixin'] 		    = 'WeiXin';
$_['ecpaypayment_text_twqr'] 		    = 'TWQR';
$_['ecpaypayment_text_bnpl'] 		    = 'BNPL';
$_['ecpaypayment_text_bnpl_urich']      = 'URICH';
$_['ecpaypayment_text_bnpl_zingala']    = 'ZINGALA';
$_['ecpaypayment_text_applepay'] 		= 'ApplePay';
$_['ecpaypayment_text_dca']             = 'DCA';
$_['ecpaypayment_text_jkopay']          = 'Jkopay';
$_['ecpaypayment_text_ipassmoney']      = 'iPass MONEY';

// Entry
$_['ecpaypayment_entry_status'] 		= 'Status';
$_['ecpaypayment_entry_merchant_id'] 		= 'Merchant ID';
$_['ecpaypayment_entry_hash_key'] 		= 'Hash Key';
$_['ecpaypayment_entry_hash_iv'] 		= 'Hash IV';
$_['ecpaypayment_entry_payment_methods'] 	= 'Payment Method';
$_['ecpaypayment_entry_create_status'] 		= 'Create Status';
$_['ecpaypayment_entry_success_status'] 	= 'Success Status';
$_['ecpaypayment_entry_failed_status'] 		= 'Failed Status';
$_['ecpaypayment_entry_geo_zone'] 		= 'Geo Zone';
$_['ecpaypayment_entry_sort_order'] 		= 'Sort Order';
$_['ecpaypayment_entry_dca_period_type']  = 'DCA Period Type';
$_['ecpaypayment_entry_dca_frequency']    = 'DCA Frequency';
$_['ecpaypayment_entry_dca_exec_times']   = 'DCA ExecTimes';
$_['ecpaypayment_entry_test_mode']        = 'Test Mode';
$_['ecpaypayment_entry_test_mode_info']   = 'If you switch to Test Mode while in Live Mode, it will affect the receipt of payment result notifications from ECPay for orders.';

// Error
$_['ecpaypayment_error_permission'] 		= 'Warning: You do not have permission to modify payment ECPay!';
$_['ecpaypayment_error_merchant_id'] 		= 'Merchant ID Required!';
$_['ecpaypayment_error_hash_key'] 		= 'Hash Key Required!';
$_['ecpaypayment_error_hash_iv'] 		= 'Hash IV Required!';

// DCA Error
$_['ecpaypayment_error_dca_frequency_y']  = 'When PeriodType is set to Y, only the value 1 (year) can be set.';
$_['ecpaypayment_error_dca_exec_times_y'] = 'When PeriodType is set to Y, the value must be between 2 and 99 times.';
$_['ecpaypayment_error_dca_frequency_m']  = 'When PeriodType is set to M, the value that can be set is 1~12 (months).';
$_['ecpaypayment_error_dca_exec_times_m'] = 'When PeriodType is set to M, the value must be between 2 and 999 times.';
$_['ecpaypayment_error_dca_frequency_d']  = 'When PeriodType is set to D, the value can be set from 1 to 365 (days).';
$_['ecpaypayment_error_dca_exec_times_d'] = 'When PeriodType is set to D, the value must be between 2 and 999 times.';