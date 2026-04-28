<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\VerifiedArrayResponse;

class ControllerExtensionPaymentEcpaypayment extends Controller {
    private $module_name = 'ecpaypayment';
    private $lang_prefix = '';
    private $module_path = '';
    private $id_prefix = '';
    private $setting_prefix = '';
    private $model_name = '';
    private $name_prefix = '';
    private $chosen_payment_session_name = 'chosen_payment';
    private $helper = null;

    // Invoice
    private $ecpay_invoice_module_name = 'ecpayinvoice';
    private $ecpay_invoice_setting_prefix = '';

    // Logistic
    private $ecpay_logistic_module_name = 'ecpaylogistic';
    private $ecpay_logistic_module_path = '';

    // Constructor
    public function __construct($registry) {
        parent::__construct($registry);

        // Set the variables

        // payment
        $this->lang_prefix = $this->module_name .'_';
        $this->id_prefix = 'payment-' . $this->module_name;
        $this->setting_prefix = 'payment_' . $this->module_name . '_';
        $this->module_path = 'extension/payment/' . $this->module_name;
        $this->model_name = 'model_extension_payment_' . $this->module_name;
        $this->name_prefix = 'payment_' . $this->module_name;

        $this->load->model($this->module_path);

        $this->load->library('ecpay_payment_helper');
        $this->helper = $this->registry->get('ecpay_payment_helper');

        // invoice
        $this->ecpay_invoice_setting_prefix = 'payment_' . $this->ecpay_invoice_module_name . '_';

        // logistic
        $this->ecpay_logistic_module_path = 'extension/shipping/' . $this->ecpay_logistic_module_name;
    }

    // Checkout confirm order page
    public function index() {

        // PAYMENT
        if(true)
        {
            // Get the translations
            $this->load->language($this->module_path);
            $data['text_checkout_button'] = $this->language->get($this->lang_prefix . 'text_checkout_button');
            $data['text_title'] = $this->language->get($this->lang_prefix . 'text_title');
            $data['entry_payment_method'] = $this->language->get($this->lang_prefix . 'entry_payment_method');


            if (isset($this->session->data[$this->module_name][$this->chosen_payment_session_name]) === true) {
                $chosen_payment = $this->session->data[$this->module_name][$this->chosen_payment_session_name];
                $data['chosen_payemnt'] = $this->language->get($this->lang_prefix . 'text_' . $chosen_payment);
            } else {
                $data['chosen_payemnt'] = '';
            }

            // Set the view data
            $data['id_prefix'] = $this->id_prefix;
            $data['module_name'] = $this->module_name;
            $data['name_prefix'] = $this->name_prefix;
            $data['redirect_url'] = $this->url->link(
                $this->module_path . '/redirect',
                '',
                $this->url_secure
            );

            $view_data_name = $this->module_name . '_' . 'payment_methods';

            // Get ECPay payment methods
            $ecpay_payment_methods = $this->config->get($this->setting_prefix . 'payment_methods');

            // 判斷可否使用 ApplePay
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (preg_match('/iPhone|iPad|iPod/', $userAgent) !== 1) {
                unset($ecpay_payment_methods['ApplePay']);
            }

            // 判斷是否可選TWQR
            $cart_total = $this->getCartTotal();
            if (6 > $cart_total || $cart_total > 49999) {
                unset($ecpay_payment_methods['TWQR']);
            }

            // 判斷是否可選微信支付
            if (6 > $cart_total || $cart_total > 500000) {
                unset($ecpay_payment_methods['WeiXin']);
            }

            // 判斷是否可選街口支付
            if (0 > $cart_total || $cart_total > 199999) {
                unset($ecpay_payment_methods['Jkopay']);
            }

            // 判斷是否可選綠界iPASS MONEY
            if (0 > $cart_total || $cart_total > 50000) {
                unset($ecpay_payment_methods['iPassMoney']);
            }

            // 判斷是否可選定期定額
            $dca_period_type = $this->config->get($this->setting_prefix . 'dca_period_type');
            $dca_frequency   = $this->config->get($this->setting_prefix . 'dca_frequency');
            $dca_exec_times  = $this->config->get($this->setting_prefix . 'dca_exec_times');
            if (! in_array($dca_period_type, ['Y', 'M', 'D']) || $dca_frequency == '' || $dca_exec_times == '') {
                unset($ecpay_payment_methods['DCA']);
            }

            if (empty($ecpay_payment_methods) === true) {
                $ecpay_payment_methods = array();
            } else {
                // Get the translation of payment methods
                foreach ($ecpay_payment_methods as $name) {
                    $lower_name = strtolower($name);
                    $lang_key = $this->lang_prefix . 'text_' . $lower_name;
                    $data[$view_data_name][$lower_name] = $this->language->get($lang_key);
                    unset($lang_key, $lower_name);
                }
            }
        }

        // INVOICE
        if(true)
        {
            // 判斷電子發票模組是否啟用 1.啟用 0.未啟用
            $ecpayInvoiceStatus = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');
            $data['ecpay_invoce_status'] = $ecpayInvoiceStatus ;

            $data['ecpay_invoce_text_title'] = $this->language->get($this->ecpay_invoice_module_name . '_text_title');
        }

        // LOGISTIC
        if(true)
        {
            // 判斷是否為綠界物流
            $delivery_method = array('ecpaylogistic.unimart_collection','ecpaylogistic.fami_collection','ecpaylogistic.hilife_collection','ecpaylogistic.okmart_collection','ecpaylogistic.unimart','ecpaylogistic.fami','ecpaylogistic.hilife','ecpaylogistic.okmart');

            if(isset($this->session->data['shipping_method'])){

                if( in_array( $this->session->data['shipping_method']['code'], $delivery_method) ) {

                    // 轉導至門市選擇
                    $data['redirect_url'] = $this->url->link(
                        $this->ecpay_logistic_module_path . '/express_map',
                        '',
                        $this->url_secure
                    );
                }
            }
        }

        // Load the template
        $view_path = $this->module_path;
        return $this->load->view($this->module_path, $data);
    }

    // Ajax API to save chosen payment
    public function savePayment() {
        $function_name = __FUNCTION__;
        $white_list = array('cp');
        $inputs = $this->helper->only($_POST, $white_list);

        // Check the received variables
        if ($inputs === false) {
            $this->helper->echoJson(array('response' => $function_name . ' failed(1)'));
        }

        // Save chosen payment
        $this->session->data[$this->module_name][$this->chosen_payment_session_name] = $inputs['cp'];
        $this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
    }

    // Ajax API to clean ECPay session
    public function cleanSession() {
        if (isset($this->session->data[$this->module_name][$this->chosen_payment_session_name]) === true) {
            unset($this->session->data[$this->module_name][$this->chosen_payment_session_name]);
        }

        $this->helper->echoJson(array('response' => 'ok'));
    }

    // Redirect to AIO
    public function redirect() {
        try {
            // Load translation
            $this->load->language($this->module_path);

            // Check choose payment
            $payment_methods = $this->config->get($this->setting_prefix . 'payment_methods');
            if (isset($this->session->data[$this->module_name][$this->chosen_payment_session_name]) === false) {
                throw new Exception($this->language->get($this->setting_prefix . 'error_payment_missing'));
            }
            $choose_payment = $this->session->data[$this->module_name][$this->chosen_payment_session_name];

            // Validate choose payment
            if (in_array($choose_payment, $payment_methods) === false) {
                throw new Exception($this->language->get($this->lang_prefix . 'error_invalid_payment'));
            }

            // Validate the order id
            if (isset($this->session->data['order_id']) === false) {
                throw new Exception($this->language->get($this->lang_prefix . 'error_order_id_miss'));
            }
            $order_id = $this->session->data['order_id'];

            // Get the order info
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);
            $order_total = $order['total'];

            // Update order status and comments
            $comment = $this->language->get($this->lang_prefix . 'text_' . $choose_payment);
            $status_id = $this->config->get($this->setting_prefix . 'create_status');
            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);

            // 儲存訂單商品重量
            $weight = $this->cart->getWeight();
            $payment_test_mode = $this->config->get($this->setting_prefix . 'test_mode');
            $this->{$this->model_name}->insertEcpayOrderExtend($order_id, ['goodsWeight' => $weight]);

            // Clear the cart
            $this->cart->clear();

            // Add to activity log
            $this->load->model('account/activity');
            if (empty($this->customer->isLogged()) === false) {
                $activity_key = 'order_account';
                $activity_data = array(
                    'customer_id' => $this->customer->getId(),
                    'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
                    'order_id'    => $order_id
                );
            } else {
                $activity_key = 'order_guest';
                $guest = $this->session->data['guest'];
                $activity_data = array(
                    'name'     => $guest['firstname'] . ' ' . $guest['lastname'],
                    'order_id' => $order_id
                );
            }
            $this->model_account_activity->addActivity($activity_key, $activity_data);

            // Clean the session
            $session_list = array(
                'shipping_method',
                'shipping_methods',
                'payment_method',
                'payment_methods',
                'guest',
                'comment',
                'order_id',
                'coupon',
                'reward',
                'voucher',
                'vouchers',
                'totals',
                'error',
            );
            foreach ($session_list as $name) {
                unset($this->session->data[$name]);
            }

            // 判斷電子發票模組是否啟用 1.啟用 0.未啟用
            $ecpayInvoiceStatus = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');
            if($ecpayInvoiceStatus == 1)
            {
                $this->addInvoice($order_id);
            }

            $apiPaymentInfo = $this->helper->get_ecpay_payment_api_info('AioCheckOut', $payment_test_mode);
            $factory = new Factory([
                'hashKey' => $apiPaymentInfo['hashKey'],
                'hashIv'  => $apiPaymentInfo['hashIv'],
            ]);

            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

            // 取得 SDK ChoosePayment
            $sdkPayment = $this->helper->getSdkPayment($choose_payment);

            // 組合送往 AIO 參數
            $encryptedOrderId = $this->helper->encryptForUrlParam($apiPaymentInfo, $order_id);
            $clientBackQueryString = 'order_id=' . $encryptedOrderId . ($payment_test_mode ? '&test=1' : '');

            $input = array(
                'MerchantID'        => $apiPaymentInfo['merchantId'],
                'MerchantTradeNo'   => $this->helper->getMerchantTradeNo($order_id),
                'MerchantTradeDate' => date('Y/m/d H:i:s'),
                'PaymentType'       => 'aio',
                'TotalAmount'       => (int)$order_total,
                'TradeDesc'         => 'opencart3x',
                'ItemName'          => $this->language->get($this->lang_prefix . 'text_item_name'),
                'ChoosePayment'     => $sdkPayment,
                'EncryptType'       => 1,
                'ReturnURL'         => $this->url->link($this->module_path . '/response', '', true),
                'ClientBackURL'     => htmlspecialchars_decode($this->url->link($this->module_path . '/client_back', $clientBackQueryString, true)),
                'PaymentInfoURL'    => $this->url->link($this->module_path . '/response', '', true),
                'NeedExtraPaidInfo' => 'Y',
            );

            // 街口、綠界iPASS MONEY另外處理選擇付款方式
            if ($sdkPayment == 'Jkopay' || $sdkPayment == 'iPASS') {
                $input['ChoosePayment'] = 'DigitalPayment';
                $input['ChooseSubPayment'] = $sdkPayment;
            }

            // 取得額外參數
            if ($choose_payment == 'dca') {
                $input['PeriodReturnURL'] = $this->url->link($this->module_path . '/response', '', true);
                $input['Frequency']       = $this->config->get($this->setting_prefix . 'dca_frequency');
                $input['ExecTimes']       = $this->config->get($this->setting_prefix . 'dca_exec_times');
                $input['PeriodType']      = $this->config->get($this->setting_prefix . 'dca_period_type');
            }
            $input = $this->helper->add_type_info($input, $choose_payment);

            // 紀錄綠界付款資訊
            $result = $this->helper->insertEcpayResponsePaymentInfo($order_id, $choose_payment, $input['MerchantTradeNo'], 0);

            $generateForm = $autoSubmitFormService->generate($input, $apiPaymentInfo['action']);
            echo $generateForm;

        } catch (Exception $e) {
            // Process the exception
            $this->session->data['error'] = $e->getMessage();
            $this->response->redirect($this->url->link('checkout/checkout', '', $this->url_secure));
        }
    }

    // Process AIO response
    public function response() {
        // Load the model and translation
        $this->load->language($this->module_path);
        $this->load->model('checkout/order');

        // Set the default result message
        $result_message = '1|OK';
        $order_id = null;
        $order = null;

        try {
            $payment_test_mode = $this->config->get($this->setting_prefix . 'test_mode');
            $apiPaymentInfo    = $this->helper->get_ecpay_payment_api_info('', $payment_test_mode);

            $factory = new Factory([
                'hashKey' => $apiPaymentInfo['hashKey'],
                'hashIv'  => $apiPaymentInfo['hashIv'],
            ]);

            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);
            $info = $checkoutResponse->get($_POST);

            $order_id = $this->helper->getOrderIdByMerchantTradeNo($info);

            // Get the cart order info
            $order = $this->model_checkout_order->getOrder($order_id);
            $order_status_id = $order['order_status_id'];
            $create_status_id = $this->config->get($this->setting_prefix . 'create_status');
            $order_total = $order['total'];

            $TradeAmt = 0;
            if (isset($info['Amount'])) {
                // 定期定額付款結果時 Amount 會有值
                $TradeAmt = $info['Amount'];
            } else if (isset($info['TradeAmt'])) {
                // 其他付款結果時 TradeAmt 會有值
                $TradeAmt = $info['TradeAmt'];
            }

            // Check the amounts
            if (round($TradeAmt, 0) == round($order_total, 0)) {
                if (isset($info['SimulatePaid']) && $info['SimulatePaid'] == 1) {
                    // 模擬付款 僅執行備註寫入
                    $status_id = $order_status_id;
                    $comment   = $this->language->get($this->lang_prefix . 'text_simulate_paid');
                    $this->model_checkout_order->addHistory($order_id, $status_id, $comment, false, false);
                    unset($status_id, $comment);

                } else {

                    // 計算定期定額付款結果回傳交易成功最大次數
                    $maxSuccessTimes = $this->helper->checkDcaMaxTotalSuccessTimes($info['MerchantTradeNo']);

                    // 將綠界回傳付款結果存至 DB
                    $this->helper->updateEcpayResponsePaymentInfo($order_id, $info);

                    // Update the order status
                    switch($info['RtnCode']) {
                        // Paid
                        case 1:
                            $status_id = $this->config->get($this->setting_prefix . 'success_status');

                            // 判斷是否為定期定額訂單
                            if ($info['PeriodType'] == 'Y' || $info['PeriodType'] == 'M' || $info['PeriodType'] == 'D') {

                                // 確認訂單狀態存在
                                $is_exist = $this->helper->isEcpayPaymentResponseInfoExist($order_id, $info['MerchantTradeNo']);

                                if ($is_exist) {
                                    $dca_success_comment = '綠界定期定額訂單第' .$info['TotalSuccessTimes']. '次付款結果回傳';

                                    // 確認定期定額訂單最後交易成功次數
                                    if ($maxSuccessTimes == 0 && $info['TotalSuccessTimes'] == 1) {
                                        // 第一次
                                        $dca_success_comment .= '(Master)';
                                    }
                                    else {
                                        // 非第一次
                                        // 判斷是否已接收過定期定額付款結果，若重複則不處理
                                        if ($maxSuccessTimes < $info['TotalSuccessTimes']) {
                                            $order_id = $this->create_dca_order($info, $order_id);
                                        }
                                    }

                                    // 增加定期定額分期資訊
                                    $dca_pattern = $this->language->get($this->lang_prefix . 'text_dca_comment');
                                    $comment = $this->helper->getComment($dca_pattern, $info, 1);
                                    $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);
                                    unset($dca_pattern, $comment);

                                    // 增加定期定額成功次數資訊
                                    $this->model_checkout_order->addOrderHistory($order_id, $status_id, $dca_success_comment, true, false);
                                }
                            }

                            // 付款結果資訊
                            $pattern = $this->language->get($this->lang_prefix . 'text_payment_result_comment');
                            $comment = $this->helper->getComment($pattern, $info);
                            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);
                            unset($status_id, $pattern, $comment);

                            // Save AIO response
                            $result = $this->{$this->model_name}->saveResponse($order_id, $info);

                            // Check E-Invoice model
                            $ecpay_invoice_status = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');

                            // Get E-Invoice model name
                            $invoice_module_name = '';
                            $invoice_setting_prefix = '';

                            if ($ecpay_invoice_status === '1') {
                                $invoice_module_name = $this->ecpay_invoice_module_name;
                                $invoice_setting_prefix = $this->ecpay_invoice_setting_prefix;
                            }

                            // E-Invoice auto issuel
                            if ($invoice_module_name !== '') {

                                // 載入電子發票 Model
                                $invoice_model_name = 'model_extension_payment_' . $invoice_module_name;
                                $invoice_module_path = 'extension/payment/' . $invoice_module_name;
                                $this->load->model($invoice_module_path);

                                // 取得自動開立設定值
                                $invoice_autoissue = $this->config->get($invoice_setting_prefix . 'autoissue');

                                if($invoice_autoissue === '1') {
                                    $this->{$invoice_model_name}->createInvoiceNo($order_id);
                                }
                            }
                            break;

                        // 2 => ATM 取號成功結果通知
                        // 10100073 => CVS、BARCODE 取號成功結果通知
                        case 2:
                        case 10100073:
                            $status_id = $order_status_id;
                            $payment_type = explode('_', $info['PaymentType']);
                            $pattern = $this->language->get($this->lang_prefix . 'text_' . strtolower($payment_type[0]) . '_comment');
                            $comment = $this->helper->getComment($pattern, $info, 1);
                            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);
                            unset($status_id, $pattern, $comment);
                        break;

                        // State error
                        case 5:
                            if ($this->{$this->model_name}->isResponsed($order_id) === false) {
                                // Update payment result
                                $status_id = $order_status_id;
                                $pattern = $this->language->get($this->lang_prefix . 'text_payment_result_comment');
                                $comment = $this->helper->getComment($pattern, $info, 1);
                                $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);

                                // Save AIO response
                                $result = $this->{$this->model_name}->saveResponse($order_id, $info);
                            }
                            break;

                        // Simulate paid
                        case 6:
                            $status_id = $order_status_id;
                            $comment = $this->language->get($this->lang_prefix . 'text_simulate_paid');
                            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, false, false);
                            unset($status_id, $comment);
                            break;

                        default:
                            $status_id = $this->config->get($this->setting_prefix . 'failed_status');
                            $pattern = $this->language->get($this->lang_prefix . 'text_failure_comment');
                            $comment = sprintf(
                                $pattern,
                                $info['RtnCode']
                            );
                            $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);
                    }
                }
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            if (!is_null($order_id)) {
                $status_id = $this->config->get($this->setting_prefix . 'failed_status');
                $pattern = $this->language->get($this->lang_prefix . 'text_failure_comment');
                $comment = sprintf(
                    $pattern,
                    $info['PaymentType'],
                    $info['RtnCode'],
                    $info['RtnMsg']
                );
                $this->model_checkout_order->addOrderHistory($order_id, $status_id, $comment, true, false);

                unset($status_id, $pattern, $comment);
            }

            // Set the failure result
            $result_message = '0|' . $error;
        }

        $this->helper->echoAndExit($result_message);
    }

    /**
     * AIO 返回商店按鈕轉導結果頁
     */
    public function client_back()
    {
        if (isset($_GET['order_id'])) {
            $this->load->model('checkout/order');
            $test = (isset($_GET['test']) && $_GET['test'] == 1) ? true : false;
            $apiPaymentInfo = $this->helper->get_ecpay_payment_api_info('AioCheckOut', $test);
            $orderId = $this->helper->decryptForUrlParam($apiPaymentInfo, $_GET['order_id']);

            if (!$orderId) {
                $this->response->redirect($this->url->link('common/home', '', true));
            }

            $order = $this->model_checkout_order->getOrder($orderId);
            $orderStatusId = $order['order_status_id'];

            // 訂單狀態為取消
            if ($orderStatusId == '7') {
                $this->response->redirect($this->url->link('checkout/failure', '', true));
            }
        }

        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    // 取得購物車總金額(包含所有項目)
    private function getCartTotal() {
        $cart_total = 0;
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = array();

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        foreach ($totals as $total) {
            if ($total['code'] === 'total') {
                $cart_total = $total['value'];
                break;
            }
        }

        return $cart_total;
    }

    /**
     * 建立定期定額新訂單
     * @param array $info
     * @param int $order_id
     */
    public function create_dca_order($info, $order_id)
    {
        $this->load->model('checkout/order');
        $this->load->model('account/order');
        $this->load->model('catalog/product');
        $this->load->model('localisation/language');
        $this->load->model('localisation/currency');
        $this->load->model('localisation/order_status');

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if ($order_info) {
            $order_products = $this->model_account_order->getOrderProducts($order_id);

            foreach ($order_products as $key => $product) {
                $option_data = [];
                $options     = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

                if (!empty($options)) {
                    foreach ($options as $option) {
                        $option_data[] = [
                            'product_option_id'       => $option['product_option_id'],
                            'product_option_value_id' => $option['product_option_value_id'],
                            'option_id'               => $option['option_id'] ?? '',
                            'option_value_id'         => $option['option_value_id'] ?? '',
                            'name'                    => $option['name'],
                            'value'                   => $option['value'],
                            'type'                    => $option['type'],
                        ];
                    }
                }

                $order_products[$key]['option'] = $option_data;
                $order_products[$key]['total']  = $product['total'];
            }

            $order_totals = $this->model_account_order->getOrderTotals($order_id);
            $order_vouchers = [];

            $new_order_data = [
                'invoice_prefix'        => $order_info['invoice_prefix'],
                'store_id'              => $order_info['store_id'],
                'store_name'            => $order_info['store_name'],
                'store_url'             => $order_info['store_url'],
                'customer_id'           => $order_info['customer_id'],
                'customer_group_id'     => $order_info['customer_group_id'] ?? 0,
                'firstname'             => $order_info['firstname'],
                'lastname'              => $order_info['lastname'],
                'email'                 => $order_info['email'],
                'telephone'             => $order_info['telephone'],
                'fax'                   => $order_info['fax'] ?? '',
                'custom_field'          => $order_info['custom_field'],
                'payment_firstname'     => $order_info['payment_firstname'],
                'payment_lastname'      => $order_info['payment_lastname'],
                'payment_company'       => $order_info['payment_company'],
                'payment_address_1'     => $order_info['payment_address_1'],
                'payment_address_2'     => $order_info['payment_address_2'],
                'payment_city'          => $order_info['payment_city'],
                'payment_postcode'      => $order_info['payment_postcode'],
                'payment_country'       => $order_info['payment_country'],
                'payment_country_id'    => $order_info['payment_country_id'],
                'payment_zone'          => $order_info['payment_zone'],
                'payment_zone_id'       => $order_info['payment_zone_id'],
                'payment_address_format'=> $order_info['payment_address_format'],
                'payment_custom_field'  => $order_info['payment_custom_field'],
                'payment_method'        => $order_info['payment_method'],
                'payment_code'          => $order_info['payment_code'],
                'shipping_firstname'    => $order_info['shipping_firstname'],
                'shipping_lastname'     => $order_info['shipping_lastname'],
                'shipping_company'      => $order_info['shipping_company'],
                'shipping_address_1'    => $order_info['shipping_address_1'],
                'shipping_address_2'    => $order_info['shipping_address_2'],
                'shipping_city'         => $order_info['shipping_city'],
                'shipping_postcode'     => $order_info['shipping_postcode'],
                'shipping_country'      => $order_info['shipping_country'],
                'shipping_country_id'   => $order_info['shipping_country_id'],
                'shipping_zone'         => $order_info['shipping_zone'],
                'shipping_zone_id'      => $order_info['shipping_zone_id'],
                'shipping_address_format'=> $order_info['shipping_address_format'],
                'shipping_custom_field' => $order_info['shipping_custom_field'],
                'shipping_method'       => $order_info['shipping_method'],
                'shipping_code'         => $order_info['shipping_code'],
                'products'              => $order_products,
                'vouchers'              => $order_vouchers,
                'totals'                => $order_totals,
                'comment'               => $order_info['comment'],
                'total'                 => $order_info['total'],
                'affiliate_id'          => $order_info['affiliate_id'],
                'commission'            => $order_info['commission'],
                'marketing_id'          => $order_info['marketing_id'] ?? 0,
                'tracking'              => $order_info['tracking'] ?? '',
                'language_id'           => $order_info['language_id'],
                'currency_id'           => $order_info['currency_id'],
                'currency_code'         => $order_info['currency_code'],
                'currency_value'        => $order_info['currency_value'],
                'ip'                    => $order_info['ip'],
                'forwarded_ip'          => $order_info['forwarded_ip'],
                'user_agent'            => $order_info['user_agent'],
                'accept_language'       => $order_info['accept_language'],
            ];

            $new_order_id = $this->model_checkout_order->addOrder($new_order_data);

            // 處理發票資訊
            $query_old_invoice = $this->db->query("SELECT * FROM " . DB_PREFIX . "invoice_info WHERE order_id = '" . (int) $order_id . "'");
            $query_new_invoice = $this->db->query("SELECT * FROM " . DB_PREFIX . "invoice_info WHERE order_id = '" . (int) $new_order_id . "'");
            if ($query_old_invoice->num_rows > 0 && $query_new_invoice->num_rows == 0) {
                $order_invoice = $query_old_invoice->rows[0];

                // 新訂單新增發票資訊
                $this->db->query("INSERT INTO `" . DB_PREFIX . "invoice_info` (`order_id`, `love_code`, `company_write`, `invoice_type`, `carrier_type`, `carrier_num`, `createdate`) VALUES ('" . $new_order_id . "', '" . $this->db->escape($order_invoice['love_code']) . "', '" . $this->db->escape($order_invoice['company_write']) . "', '" . $this->db->escape($order_invoice['invoice_type']) . "', '" . $this->db->escape($order_invoice['carrier_type']) . "', '" . $this->db->escape($order_invoice['carrier_num']) . "', '" . time() . "' )");
            }

            // 新舊訂單歷程
            $this->model_checkout_order->addOrderHistory($new_order_id, $order_info['order_status_id'], '定期定額付款第' . $info['TotalSuccessTimes'] . '次繳費成功，原始訂單編號: ' . $order_id, true, false);
            $this->model_checkout_order->addOrderHistory($order_id, $order_info['order_status_id'], '定期定額付款第' . $info['TotalSuccessTimes'] . '次繳費成功，新訂單號: ' . $new_order_id, true, false);

            return $new_order_id;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE
    |--------------------------------------------------------------------------
    */

    // Ajax API to save chosen payment(SESSION)
    public function saveInvoice() {
        $function_name = __FUNCTION__;
        $white_list = array('invoice_type','company_write','love_code','invoice_status', 'carrier_type', 'carrier_num');
        $inputs = $this->helper->only($_POST, $white_list);

        // Check the received variables
        if ($inputs === false) {
            $this->helper->echoJson(array('response' => $function_name . ' failed(1)'));
        }

        // Save invoice
        $this->session->data['invoice_type'] = $inputs['invoice_type'];
        $this->session->data['company_write'] = $inputs['company_write'];
        $this->session->data['love_code'] = $inputs['love_code'];
        $this->session->data['invoice_status'] = $inputs['invoice_status'];

        $this->session->data['carrier_type'] = $inputs['carrier_type'];
        $this->session->data['carrier_num'] = $inputs['carrier_num'];

        $this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
    }

    // 刪除發票資訊(SESSION)
    public function delInvoice()
    {
        $function_name = __FUNCTION__;
        $white_list = array('invoice_status');
        $inputs = $this->helper->only($_POST, $white_list);

        // Check the received variables
        if ($inputs === false) {
            $this->helper->echoJson(array('response' => $function_name . ' failed(1)'));
        }

        // Del invoice
        $this->session->data['invoice_type'] = '';
        $this->session->data['company_write'] = '';
        $this->session->data['love_code'] = '';
        $this->session->data['invoice_status'] = 0;
        $this->session->data['carrier_type'] = '';
        $this->session->data['carrier_num'] = '';

        $this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
    }

    // insert Invice Info(DB)
    public function addInvoice($order_id = '')
    {
        $nNowTime  = time() ;
        $sLoveCode = isset($this->session->data['love_code']) ? $this->session->data['love_code'] : '';
        $sCompanyWrite = isset($this->session->data['company_write']) ? $this->session->data['company_write'] : '';
        $nInvoiceType = isset($this->session->data['invoice_type']) ? $this->session->data['invoice_type'] : '';

        $carrierType = isset($this->session->data['carrier_type']) ? $this->session->data['carrier_type'] : '';
        $carrierNum = isset($this->session->data['carrier_num']) ? $this->session->data['carrier_num'] : '';

        // 資料寫入 invoice_info 資料表
        $this->db->query("INSERT INTO `" . DB_PREFIX . "invoice_info` (`order_id`, `love_code`, `company_write`, `invoice_type`, `carrier_type`, `carrier_num`, `createdate`) VALUES ('" . (int)$order_id . "', '" . $this->db->escape($sLoveCode) . "', '" . $this->db->escape($sCompanyWrite) . "', '" . $this->db->escape($nInvoiceType) . "', '" . $this->db->escape($carrierType) . "', '" . $this->db->escape($carrierNum) . "', '" . $nNowTime . "' )" );

        // housekeeping
        $this->clearInvoice();
    }

    // 刪除發票資訊(DB)
    public function clearInvoice()
    {
        $nNowTime  = time() ;
        $nPass_Time = time() - ( 86400 * 30 );
        $sPass_Time = date('Y-m-d H:i:s', $nPass_Time);

        // 1.判斷是否有訂單沒有成立 還卡在暫存狀態 取出order_id
        $order_query_tmp = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE date_added < '" . $sPass_Time . "' AND order_status_id = 0 ORDER BY date_added LIMIT 5 " );
        $order_query_tmp = $order_query_tmp->rows ;

        // 2.整理 otrder_id
        $sOrder_Id  = '' ;
        $sOrder_Id_Pro  = '' ;
        foreach($order_query_tmp as $key => $value)
        {
            $sOrder_Id_Pro = ($sOrder_Id == '' ) ? '' : ',' ;
            $sOrder_Id .= $sOrder_Id_Pro . (int)$value['order_id'] ;
        }

        // 3.刪除超過一個月的紀錄
        if($sOrder_Id != '')
        {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "invoice_info` WHERE `order_id` IN ( " . $sOrder_Id . " ) AND createdate < " . $nPass_Time );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGISTIC
    |--------------------------------------------------------------------------
    */


}
?>
