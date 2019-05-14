<?php
$module_name = 'ecpaypayment';
$setting_prefix = 'payment_' . $module_name . '_';
$lang_prefix = $module_name .'_';
$name_prefix = 'payment_' . $module_name;
$id_prefix = 'payment-' . $module_name;
$view_data_name = $module_name . '_' . 'payment_methods';

// Get ECPay payment methods
$ecpay_payment_methods = $this->config->get($setting_prefix . 'payment_methods');

if (empty($ecpay_payment_methods) === true) {
    $ecpay_payment_methods = array();
} else {
    // Get the translation of payment methods
    foreach ($ecpay_payment_methods as $name) {
        $lower_name = strtolower($name);
        $lang_key = $lang_prefix . 'text_' . $lower_name;
        $data[$view_data_name][$lower_name] = $this->language->get($lang_key);
        unset($lang_key, $lower_name);
    }
}

// Set the other view data
$data['module_name'] = $module_name;
$data['name_prefix'] = $name_prefix;
$data['id_prefix'] = $id_prefix;
?>