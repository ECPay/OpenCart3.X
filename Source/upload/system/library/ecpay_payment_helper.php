<?php

require_once(DIR_SYSTEM . "library/module_helper.php");
use Ecpay\module_helper;

class ecpay_payment_helper extends module_helper
{
    private $module_name = 'ecpaypayment';
    protected $prefix = '';
    protected $lang_prefix = '';
    protected $registry;
    protected $config;
    protected $language;
    protected $db;
    protected $model_checkout_order;

    /**
     * EcpayPaymentHelper constructor.
     */
    public function __construct($registry)
    {
        parent::__construct();
        $this->prefix = 'payment_' . $this->module_name . '_';
        $this->lang_prefix = $this->module_name . '_';
        $this->registry = $registry;
        $this->config = $registry->get('config');
        $this->language = $registry->get('language');
        $this->db = $registry->get('db');

        $this->registry->get('load')->model('checkout/order');
        $this->model_checkout_order = $this->registry->get('model_checkout_order');
    }

    /**
     * 依付款方式新增額外資訊
     * @param  array $input
     * @param  string $choosePayment
     * @return array|false
     */
    public function add_type_info($input, $choosePayment)
    {
        if (empty($input['ChoosePayment']) === true || empty($choosePayment) === true) {
            return false;
        }

        $choosePaymentArray = explode('_', $choosePayment);
        switch ($choosePaymentArray[0]) {
            case 'credit':
                // 信用卡分期
                if (isset($choosePaymentArray[1]) === true && in_array($choosePaymentArray[1], ['3', '6', '12', '18', '24', '30'])) {
                    $input['CreditInstallment'] = ($choosePaymentArray[1] == '30') ? '30N' : $choosePaymentArray[1];
                }
                break;

            case 'atm':
                $input['ExpireDate'] = 3;
                break;

            case 'barcode':
                $input['StoreExpireDate'] = 3;
                break;

            case 'cvs':
                $input['StoreExpireDate'] = 10080;
                break;

            case 'dca':
                $input['PeriodAmount'] = $input['TotalAmount'];
                break;
        }

        return $input;
    }

    /**
     * 取得 API URL
     * @param  string $action
     * @param  string $mid
     * @return string|false
     */
    public function get_ecpay_payment_api_info($action = '', $test_mode = false)
    {
        $api_info = [
            'action' => '',
        ];

        if ($test_mode) {
            $api_info['merchantId'] = '3002607';
            $api_info['hashKey']    = 'pwFHCqoQZGmho4w6';
            $api_info['hashIv']     = 'EkRm7iFT261dpevs';

            switch ($action) {
                case 'QueryTradeInfo':
                    $api_info['action'] = 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
                    break;

                case 'AioCheckOut':
                    $api_info['action'] = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';
                    break;

                default:
                    break;
            }
        }
        else {

            $api_info['merchantId'] = $this->config->get($this->prefix . 'merchant_id');
            $api_info['hashKey']    = $this->config->get($this->prefix . 'hash_key');
            $api_info['hashIv']     = $this->config->get($this->prefix . 'hash_iv');

            switch ($action) {
                case 'QueryTradeInfo':
                    $api_info['action'] = 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
                    break;

                case 'AioCheckOut':
                    $api_info['action'] = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';
                    break;

                default:
                    break;
            }
        }

        return $api_info;
    }

    /**
     * Get comment
     * @param  string $pattern  Message pattern
     * @param  array  $feedback AIO feedback
     * @return string
     */
    public function getComment($pattern = '', $feedback = array(), $type = 0)
    {
        // Filter inputs
        $undefinedMessage = 'undefined';
        if (empty($pattern) === true) {
            return $undefinedMessage;
        }

        $list = array(
            'PaymentType',
            'RtnCode',
            'RtnMsg',
            'BankCode',
            'vAccount',
            'ExpireDate',
            'PaymentNo',
            'Barcode1',
            'Barcode2',
            'Barcode3',
            'BNPLTradeNo',
            'BNPLInstallment',
            'PeriodAmount',
            'PeriodType',
            'Frequency',
            'ExecTimes'
        );
        $inputs = $this->only($feedback, $list);

        $paymentTypeArray = explode('_', $inputs['PaymentType']);
        if ($type == 0) {
            return sprintf(
                $pattern,
                $inputs['PaymentType'],
                $inputs['RtnCode'],
                $inputs['RtnMsg']
            );
        }
        else {
            switch($paymentTypeArray[0]) {
                case 'Credit':
                case 'WeiXin':
                case 'TWQR':
                case 'ApplePay':
                case 'Flexible':
                case 'DigitalPayment':
                    if (isset($inputs['PeriodType']) && $inputs['PeriodType'] != '') {
                        return sprintf(
                            $pattern,
                            $inputs['RtnCode'],
                            $inputs['RtnMsg'],
                            $inputs['PeriodAmount'],
                            $inputs['PeriodType'],
                            $inputs['Frequency'],
                            $inputs['ExecTimes']
                        );
                    }
                    else {
                        return sprintf(
                            $pattern,
                            $inputs['PaymentType'],
                            $inputs['RtnCode'],
                            $inputs['RtnMsg']
                        );
                    }
                    break;
                case 'ATM':
                    return sprintf(
                        $pattern,
                        $inputs['RtnCode'],
                        $inputs['RtnMsg'],
                        $inputs['BankCode'],
                        $inputs['vAccount'],
                        $inputs['ExpireDate']
                    );
                    break;
                case 'WebATM':
                    return sprintf(
                        $pattern,
                        $inputs['RtnCode'],
                        $inputs['RtnMsg'],
                    );
                    break;
                case 'CVS':
                    return sprintf(
                        $pattern,
                        $inputs['RtnCode'],
                        $inputs['RtnMsg'],
                        $inputs['PaymentNo'],
                        $inputs['ExpireDate']
                    );
                    break;
                case 'BARCODE':
                    return sprintf(
                        $pattern,
                        $inputs['RtnCode'],
                        $inputs['RtnMsg'],
                        $inputs['ExpireDate'],
                        $inputs['Barcode1'],
                        $inputs['Barcode2'],
                        $inputs['Barcode3']
                    );
                    break;
                case 'BNPL':
                    return sprintf(
                        $pattern,
                        $inputs['RtnCode'],
                        $inputs['RtnMsg'],
                        $inputs['BNPLTradeNo'],
                        $inputs['BNPLInstallment'],
                        $this->language->get($this->lang_prefix . 'text_' . strtolower($inputs['PaymentType']))
                    );
                    break;
                default:
                    break;
            }
        }

        return $undefinedMessage;
    }

    /**
     * 取得對應的 SDK 付款方式
     * @param  string $choose_payment
     * @return string|false
     */
    public function getSdkPayment ($choose_payment) {
        if (empty($choose_payment) === true) {
            return false;
        }

        $sdkPayment = '';
        $choosePaymentArray = explode('_', $choose_payment);
        switch ($choosePaymentArray[0]) {
            case 'dca':
            case 'credit':
                $sdkPayment = 'Credit';
                break;
            case 'webatm':
                $sdkPayment = 'WebATM';
                break;
            case 'atm':
                $sdkPayment = 'ATM';
                break;
            case 'cvs':
                $sdkPayment = 'CVS';
                break;
            case 'barcode':
                $sdkPayment = 'BARCODE';
                break;
            case 'weixin':
                $sdkPayment = 'WeiXin';
                break;
            case 'twqr':
                $sdkPayment = 'TWQR';
                break;
            case 'bnpl':
                $sdkPayment = 'BNPL';
                break;
            case 'applepay':
                $sdkPayment = 'ApplePay';
                break;
            case 'jkopay':
                $sdkPayment = 'Jkopay';
                break;
            case 'ipassmoney':
                $sdkPayment = 'iPASS';
                break;
        }

        return $sdkPayment;
    }

    // 新增綠界金流資訊
    public function insertEcpayResponsePaymentInfo($order_id, $choose_payment, $merchant_trade_no, $payment_status = 0)
    {
        if (empty($order_id) === true || empty($choose_payment) === true || empty($merchant_trade_no) === true) {
            return false;
        }

        // 取得訂單資訊
        $order_info = $this->model_checkout_order->getOrder($order_id);

        // 驗證訂單存在，且使用綠界金流
        if ($order_info && $order_info['payment_code'] === $this->module_name) {

            // 檢查是否已存在相同的 opencart 訂單和廠商訂單編號
            $is_exist = $this->isEcpayPaymentResponseInfoExist($order_id, $merchant_trade_no);

            if (!$is_exist) {
                // 如果不存在，則插入新的綠界金流資訊
                $insert_sql = 'INSERT INTO `%s`';
                $insert_sql .= ' (`order_id`, `payment_method`, `merchant_trade_no`, `payment_status`, `created_at`)';
                $insert_sql .= ' VALUES (%d, "%s", "%s", %d, %d)';
                $table = DB_PREFIX . 'ecpay_payment_response_info';
                $now_time = date('Y-m-d H:i:s');

                return $this->db->query(sprintf(
                    $insert_sql,
                    $table,
                    (int)$order_id,
                    $this->db->escape($choose_payment),
                    $this->db->escape($merchant_trade_no),
                    (int)$payment_status,
                    $now_time
                ));
            }
        }
    }

    // 更新綠界回傳付款結果
    public function updateEcpayResponsePaymentInfo($order_id, $response_info)
    {
        try {
            if (empty($order_id) === true || empty($response_info) === true) {
                return false;
            }

            // 模擬付款不更新付款狀態
            if (isset($response_info['SimulatePaid']) && $response_info['SimulatePaid'] == 0) {
                $fields = [
                    'payment_status' => isset($response_info['RtnCode']) ? (int)$response_info['RtnCode'] : null,
                    'MerchantID' => isset($response_info['MerchantID']) ? $this->db->escape($response_info['MerchantID']) : null,
                    'MerchantTradeNo' => isset($response_info['MerchantTradeNo']) ? $this->db->escape($response_info['MerchantTradeNo']) : null,
                    'StoreID' => isset($response_info['StoreID']) ? $this->db->escape($response_info['StoreID']) : null,
                    'RtnCode' => isset($response_info['RtnCode']) ? (int)$response_info['RtnCode'] : null,
                    'RtnMsg' => isset($response_info['RtnMsg']) ? $this->db->escape($response_info['RtnMsg']) : null,
                    'TradeNo' => isset($response_info['TradeNo']) ? $this->db->escape($response_info['TradeNo']) : null,
                    'TradeAmt' => isset($response_info['TradeAmt']) ? (int)$response_info['TradeAmt'] : null,
                    'PaymentDate' => isset($response_info['PaymentDate']) ? $this->db->escape($response_info['PaymentDate']) : null,
                    'PaymentType' => isset($response_info['PaymentType']) ? $this->db->escape($response_info['PaymentType']) : null,
                    'PaymentTypeChargeFee' => isset($response_info['PaymentTypeChargeFee']) ? (int)$response_info['PaymentTypeChargeFee'] : null,
                    'PlatformID' => isset($response_info['PlatformID']) ? $this->db->escape($response_info['PlatformID']) : null,
                    'TradeDate' => isset($response_info['TradeDate']) ? $this->db->escape($response_info['TradeDate']) : null,
                    'SimulatePaid' => isset($response_info['SimulatePaid']) ? (int)$response_info['SimulatePaid'] : null,
                    'CustomField1' => isset($response_info['CustomField1']) ? $this->db->escape($response_info['CustomField1']) : null,
                    'CustomField2' => isset($response_info['CustomField2']) ? $this->db->escape($response_info['CustomField2']) : null,
                    'CustomField3' => isset($response_info['CustomField3']) ? $this->db->escape($response_info['CustomField3']) : null,
                    'CustomField4' => isset($response_info['CustomField4']) ? $this->db->escape($response_info['CustomField4']) : null,
                    'CheckMacValue' => isset($response_info['CheckMacValue']) ? $this->db->escape($response_info['CheckMacValue']) : null,
                    'eci' => isset($response_info['eci']) ? (int)$response_info['eci'] : null,
                    'card4no' => isset($response_info['card4no']) ? $this->db->escape($response_info['card4no']) : null,
                    'card6no' => isset($response_info['card6no']) ? $this->db->escape($response_info['card6no']) : null,
                    'process_date' => isset($response_info['process_date']) ? $this->db->escape($response_info['process_date']) : null,
                    'auth_code' => isset($response_info['auth_code']) ? $this->db->escape($response_info['auth_code']) : null,
                    'stage' => isset($response_info['stage']) ? (int)$response_info['stage'] : null,
                    'stast' => isset($response_info['stast']) ? (int)$response_info['stast'] : null,
                    'red_dan' => isset($response_info['red_dan']) ? (int)$response_info['red_dan'] : null,
                    'red_de_amt' => isset($response_info['red_de_amt']) ? (int)$response_info['red_de_amt'] : null,
                    'red_ok_amt' => isset($response_info['red_ok_amt']) ? (int)$response_info['red_ok_amt'] : null,
                    'red_yet' => isset($response_info['red_yet']) ? (int)$response_info['red_yet'] : null,
                    'gwsr' => isset($response_info['gwsr']) ? (int)$response_info['gwsr'] : null,
                    'PeriodType' => isset($response_info['PeriodType']) ? $this->db->escape($response_info['PeriodType']) : null,
                    'Frequency' => isset($response_info['Frequency']) ? (int)$response_info['Frequency'] : null,
                    'ExecTimes' => isset($response_info['ExecTimes']) ? (int)$response_info['ExecTimes'] : null,
                    'amount' => isset($response_info['amount']) ? (int)$response_info['amount'] : null,
                    'ProcessDate' => isset($response_info['ProcessDate']) ? $this->db->escape($response_info['ProcessDate']) : null,
                    'AuthCode' => isset($response_info['AuthCode']) ? $this->db->escape($response_info['AuthCode']) : null,
                    'FirstAuthAmount' => isset($response_info['FirstAuthAmount']) ? (int)$response_info['FirstAuthAmount'] : null,
                    'TotalSuccessTimes' => isset($response_info['TotalSuccessTimes']) ? (int)$response_info['TotalSuccessTimes'] : null,
                    'BankCode' => isset($response_info['BankCode']) ? $this->db->escape($response_info['BankCode']) : null,
                    'vAccount' => isset($response_info['vAccount']) ? $this->db->escape($response_info['vAccount']) : null,
                    'ATMAccNo' => isset($response_info['ATMAccNo']) ? $this->db->escape($response_info['ATMAccNo']) : null,
                    'ATMAccBank' => isset($response_info['ATMAccBank']) ? $this->db->escape($response_info['ATMAccBank']) : null,
                    'WebATMBankName' => isset($response_info['WebATMBankName']) ? $this->db->escape($response_info['WebATMBankName']) : null,
                    'WebATMAccNo' => isset($response_info['WebATMAccNo']) ? $this->db->escape($response_info['WebATMAccNo']) : null,
                    'WebATMAccBank' => isset($response_info['WebATMAccBank']) ? $this->db->escape($response_info['WebATMAccBank']) : null,
                    'PaymentNo' => isset($response_info['PaymentNo']) ? $this->db->escape($response_info['PaymentNo']) : null,
                    'ExpireDate' => isset($response_info['ExpireDate']) ? $this->db->escape($response_info['ExpireDate']) : null,
                    'Barcode1' => isset($response_info['Barcode1']) ? $this->db->escape($response_info['Barcode1']) : null,
                    'Barcode2' => isset($response_info['Barcode2']) ? $this->db->escape($response_info['Barcode2']) : null,
                    'Barcode3' => isset($response_info['Barcode3']) ? $this->db->escape($response_info['Barcode3']) : null,
                    'BNPLTradeNo' => isset($response_info['BNPLTradeNo']) ? $this->db->escape($response_info['BNPLTradeNo']) : null,
                    'BNPLInstallment' => isset($response_info['BNPLInstallment']) ? $this->db->escape($response_info['BNPLInstallment']) : null,
                    'TWQRTradeNo' => isset($response_info['TWQRTradeNo']) ? $this->db->escape($response_info['TWQRTradeNo']) : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $set_sql = '';
                foreach ($fields as $key => $val) {
                    if (is_int($val)) {
                        $set_sql .= " $key = $val,";
                    } else {
                        $set_sql .= " $key = \"$val\",";
                    }
                }
                $set_sql = rtrim($set_sql, ',');
                $table = '`' . DB_PREFIX . 'ecpay_payment_response_info' . '`';
                $where_order_id = (int)$order_id;
                $where_merchant_trade_no = $response_info['MerchantTradeNo'];
                $sql = "UPDATE $table SET $set_sql WHERE order_id = $where_order_id AND merchant_trade_no = \"$where_merchant_trade_no\" AND is_completed_duplicate = 0";
                return $this->db->query($sql);
            }
        } catch (\Throwable $th) {
            error_log('Error updating ECPay response payment info: ' . $th->getMessage());
        }
    }

    // 檢查是否存在綠界金流回傳資訊
    public function isEcpayPaymentResponseInfoExist($order_id, $merchant_trade_no) {
        if (empty($order_id) === true || empty($merchant_trade_no) === true) {
            return false;
        }

        $select_sql = 'SELECT order_id FROM `%s`';
        $select_sql .= ' WHERE order_id = %d AND merchant_trade_no = "%s"';
        $select_sql .= ' LIMIT 1';
        $table = DB_PREFIX . 'ecpay_payment_response_info';
        $result = $this->db->query(sprintf(
            $select_sql,
            $table,
            (int)$order_id,
            $this->db->escape($merchant_trade_no)
        ));

        return ($result->num_rows > 0);
    }

    // 檢查定期定額付款方式的最大成功次數
    public function checkDcaMaxTotalSuccessTimes($merchant_trade_no) {
        // 改寫上述程式碼為 opencart 的方式
        $select_sql = 'SELECT TotalSuccessTimes FROM `%s`';
        $select_sql .= ' WHERE MerchantTradeNo = "%s" AND payment_method = "%s"';
        $select_sql .= ' ORDER BY TotalSuccessTimes DESC LIMIT 1';
        $table = DB_PREFIX . 'ecpay_payment_response_info';

        $result = $this->db->query(sprintf(
            $select_sql,
            $table,
            $this->db->escape($merchant_trade_no),
            'dca'
        ));

        if ($result->num_rows > 0) {
            return (int)$result->row['TotalSuccessTimes'];
        }
        return 0;
    }

    public function encryptForUrlParam(array $apiPaymentInfo, mixed $param)
    {
        if (is_array($param)) {
            $param = json_encode($param, JSON_UNESCAPED_UNICODE);
        }
        $encrypted = openssl_encrypt($param, 'AES-128-CBC', $apiPaymentInfo['hashKey'], OPENSSL_RAW_DATA, $apiPaymentInfo['hashIv']);

        if ($encrypted === false) {
            return $param;
        }

        return rawurlencode(base64_encode($encrypted));
    }

    public function decryptForUrlParam(array $apiPaymentInfo, string $encrypted)
    {
        $urlDecoded = rawurldecode($encrypted);
        $urlDecoded = str_replace(' ', '+', $urlDecoded);

        $decoded = base64_decode($urlDecoded, true);
        if ($decoded === false) {
            return null;
        }

        $decrypted = openssl_decrypt($decoded, 'AES-128-CBC', $apiPaymentInfo['hashKey'], OPENSSL_RAW_DATA,$apiPaymentInfo['hashIv']);

        // 如果內容原本是 JSON，嘗試解回陣列
        $json = json_decode($decrypted, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : $decrypted;
    }
}
