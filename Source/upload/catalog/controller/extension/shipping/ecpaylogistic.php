<?php

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\VerifiedArrayResponse;

class ControllerExtensionShippingecpayLogistic extends Controller {

    // payment
    private $ecpay_payment_module_name = 'ecpaypayment';
    private $ecpay_payment_module_path = '';

    // Logistic
    private $prefix = 'shipping_ecpaylogistic_';
    private $ecpay_logistic_module_name = 'ecpaylogistic';
    private $ecpay_logistic_module_path = '';
    private $ecpay_logistic_payment_module_path = '';
    private $ecpay_logistic_model_name = '';

    // invoice
    private $ecpay_invoice_module_name = 'ecpayinvoice';
    private $ecpay_invoice_setting_prefix = '';

    // Constructor
    public function __construct($registry) {
        parent::__construct($registry);

        // Set the variables

        // payment
        $this->ecpay_payment_module_path = 'extension/payment/' . $this->ecpay_payment_module_name;
        $this->ecpay_logistic_payment_module_path = 'extension/payment/' . $this->ecpay_logistic_module_name;

        // invoice
        $this->ecpay_invoice_setting_prefix = 'payment_' . $this->ecpay_invoice_module_name . '_';

        // logistic
        $this->ecpay_logistic_module_path = 'extension/shipping/' . $this->ecpay_logistic_module_name;
        $this->ecpay_logistic_model_name = 'model_extension_shipping_' . $this->ecpay_logistic_module_name;

        $this->load->library('ecpay_logistic_helper');
        $this->helper = $this->registry->get('ecpay_logistic_helper');
    }

    protected function index() {
    }

    // 電子地圖選擇門市
    public function express_map() {
        $ecpaylogisticSetting=array();

        $sFieldName = 'code';
        $sFieldValue = 'shipping_' . $this->ecpay_logistic_module_name;
        $get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");

        foreach( $get_ecpaylogistic_setting_query->rows as $value ) {
            $ecpaylogisticSetting[$value["key"]] = $value["value"];
        }

        if ( $ecpaylogisticSetting[$this->prefix . 'type'] == 'C2C' ) {
            $shippingMethod = [
                'fami' => 'FAMIC2C',
                'fami_collection' => 'FAMIC2C',
                'unimart' => 'UNIMARTC2C',
                'unimart_collection' => 'UNIMARTC2C',
                'hilife' => 'HILIFEC2C',
                'hilife_collection' => 'HILIFEC2C',
                'okmart' => 'OKMARTC2C',
                'okmart_collection' => 'OKMARTC2C'
            ];
        } else {
            $shippingMethod = [
                'fami' => 'FAMI',
                'fami_collection' => 'FAMI',
                'unimart' => 'UNIMART',
                'unimart_collection' => 'UNIMART',
                'hilife' => 'HILIFE',
                'hilife_collection' => 'HILIFE',
                'okmart' => 'OKMART',
                'okmart_collection' => 'OKMART'
            ];
        }

        $logisticSubType = explode(".", $this->session->data['shipping_method']['code']);

        if (array_key_exists($logisticSubType[1], $shippingMethod)) {
            $al_subtype = $shippingMethod[$logisticSubType[1]];
        }

        if (!isset($al_subtype)) {
            exit;
        }

        // session fix
        $sessionId = $_COOKIE[$this->config->get('session_name')];
        $dataBase64Encode = $this->sessionEncrypt($sessionId);

        $al_iscollection = 'N';
        $al_srvreply = $this->url->link($this->ecpay_logistic_module_path . '/response_map&sid='.$dataBase64Encode,'',$this->url_secure);

        try {
            $factory = new Factory([
				'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
				'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
				'hashMethod'    => 'md5',
			]);
            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

			$inputMap = array(
				'MerchantID'       => $ecpaylogisticSetting[$this->prefix . 'mid'],
				'MerchantTradeNo'  => $this->helper->getMerchantTradeNo($this->session->data['order_id']),
                'LogisticsType'    => $this->helper->get_logistics_type($al_subtype),
				'LogisticsSubType' => $al_subtype,
				'IsCollection'     => $al_iscollection,
				'ServerReplyURL'   => $al_srvreply,
				'ExtraData'        => '',
			);

            $api_info = $this->helper->get_ecpay_logistic_api_info('map', $al_subtype, $ecpaylogisticSetting);
            $form_map = $autoSubmitFormService->generate($inputMap, $api_info['action'], 'ecpay_map');

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        
        echo $form_map;
    }

    // 電子地圖選擇門市回傳
    public function response_map() {
        $order_id = $this->helper->getOrderIdByMerchantTradeNo(['MerchantTradeNo' => $_POST['MerchantTradeNo']]);
        $shipping_address_1 = (isset($_POST['CVSStoreID'])) ? $_POST['CVSStoreID'] : '' ;
        $shipping_address_2 = (isset($_POST['CVSStoreName'])) ? $_POST['CVSStoreName'] : '' ;
        $shipping_address_2 = (isset($_POST['CVSAddress'])) ? $shipping_address_2 . ' ' .$_POST['CVSAddress'] : $shipping_address_2 ;

        // session restore
        $sid =  $this->request->get['sid'] ;
        $sessionId = $this->sessionDecrypt($sid);
        setcookie($this->config->get('session_name'), $sessionId, ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));

        // 將門市資訊寫回訂單
        $this->db->query("UPDATE " . DB_PREFIX . "order SET shipping_address_1 = '".$this->db->escape($shipping_address_1)."', shipping_address_2 = '" . $this->db->escape($shipping_address_2) . "' WHERE order_id = ".(int) $order_id);

        // 取出訂單付款方式
        $order_query = $this->db->query("SELECT payment_code FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "'" );
        if ($order_query->num_rows) {

            // 判斷是否為 超商取貨付款
            if($order_query->row['payment_code'] == 'ecpaylogistic') {

                // 判斷電子發票模組是否啟用 1.啟用 0.未啟用
                $ecpayInvoiceStatus = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');
                if($ecpayInvoiceStatus == 1){
                    $this->addInvoice($order_id);
                }

                // 轉導回異動訂單狀態
                $this->response->redirect($this->url->link($this->ecpay_logistic_payment_module_path . '/update_order_status','order_id='. $order_id, $this->url_secure));
            } else {

                // 轉導ECPAY付款
                $this->response->redirect($this->url->link($this->ecpay_payment_module_path . '/redirect', '', $this->url_secure));
            }
        }
    }

    // 建立物流訂單回傳
    public function response() {
        $sFieldName = 'code';
        $sFieldValue = 'shipping_' . $this->ecpay_logistic_module_name;
        $get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
        $ecpaylogisticSetting = array();
        foreach($get_ecpaylogistic_setting_query->rows as $value)
        {
            $ecpaylogisticSetting[$value["key"]]=$value["value"];
        }

        try {
            $factory = new Factory([
				'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
				'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
				'hashMethod'    => 'md5',
			]);
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);

            $order_id = $this->helper->getOrderIdByMerchantTradeNo(['MerchantTradeNo' => $this->request->post['MerchantTradeNo']]);
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'" );
            $aOrder_Info_Tmp = $query->rows[0] ;
            $sMsg = "綠界科技廠商管理後台物流訊息:<br>" . print_r($this->request->post, true);
            $aSuccessCodes = ['2067', '3022', '300'];
            $sRtnCode = $this->request->post['RtnCode'];
            if (in_array($sRtnCode, $aSuccessCodes)) {
                if ($sRtnCode == '300') {
                    $aOrder_Info_Tmp['order_status_id'] = 5;
                } else {
                    $aOrder_Info_Tmp['order_status_id'] = 3;
                }

                $shippingCode = explode('.', $aOrder_Info_Tmp['shipping_code']);
                $shippingMethod = array(
                    'fami_collection',
                    'unimart_collection',
                    'hilife_collection',
                    'okmart_collection'
                );

                if ( in_array($shippingCode[1], $shippingMethod) && ( $sRtnCode == 2067 || $sRtnCode == 3022 ) ) {

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

                        if($invoice_autoissue == 1) {

                            $this->{$invoice_model_name}->createInvoiceNo($order_id);
                        }
                    }
                }
            }

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . $order_id . "', order_status_id = '" . (int)$aOrder_Info_Tmp['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($sMsg) . "', date_added = NOW()");

            echo '1|OK';
        }
        catch(Exception $e) {
            echo '0|' . $e->getMessage();
        }
    }

    // Server端物流回傳網址
    public function logistics_c2c_reply() {
        $get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'ecpaylogistic'");
        $ecpaylogisticSetting=array();
        foreach($get_ecpaylogistic_setting_query->rows as $value){
            $ecpaylogisticSetting[$value["key"]]=$value["value"];
        }
        try {
            $factory = new Factory([
				'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
				'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
				'hashMethod'    => 'md5',
			]);
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);

            $query = $this->db->query('Select * from ' . DB_PREFIX . 'ecpaylogistic_response where AllPayLogisticsID=' . $this->db->escape($this->request->post['1|AllPayLogisticsID']));
            if ($query->num_rows) {
                $aAL_info = $query->rows[0];
                $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = 1 WHERE order_id = ".(int)$aAL_info['order_id']);
                $sMsg = "綠界科技廠商管理後台更新門市通知:<br>" . print_r($this->request->post, true);
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$aAL_info['order_id'] . "', order_status_id = '1', notify = '0', comment = '" . $this->db->escape($sMsg) . "', date_added = NOW()");
                echo '1|OK';
            } else {
                echo '0|AllPayLogisticsID not found';
            }
        } catch(Exception $e) {
            echo '0|' . $e->getMessage();
        }
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

    // insert Invice Info(DB)
    public function addInvoice( $order_id = '' ) {
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
    public function clearInvoice() {
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

    // 刪除發票資訊(SESSION)
    public function delInvoice() {
        $function_name = __FUNCTION__;
        $white_list = array('invoice_status');
        $inputs = $this->helper->only($_POST, $white_list);

        // Check the received variables
        if ($inputs === false) {
            $this->helper->echoJson(array('response' => $function_name . ' failed(1)'));
        }

        // Del invoice
        $this->session->data[$this->ecpay_invoice_module_name]['invoice_type'] = '';
        $this->session->data[$this->ecpay_invoice_module_name]['company_write'] = '';
        $this->session->data[$this->ecpay_invoice_module_name]['love_code'] = '';
        $this->session->data[$this->ecpay_invoice_module_name]['invoice_status'] = 0;

        $this->session->data[$this->ecpay_invoice_module_name]['carrier_type'] = '';
        $this->session->data[$this->ecpay_invoice_module_name]['carrier_num'] = '';


        $this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
    }


    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    /**
     * SessionId解密
     * @param  string $data
     * @return string
     */
    public function sessionDecrypt($data)
    {
        $hashKey = $this->config->get($this->prefix . 'hashkey');
        $hashIv  = $this->config->get($this->prefix . 'hashiv');

        $dataBase64Decode = $this->base64Decode($data);
        $dataAesDecrypt = $this->aesDecrypt($dataBase64Decode, $hashKey, $hashIv) ;
        $sessionId = $this->urlDecode($dataAesDecrypt);

        return $sessionId;
    }

    /**
     * SessionId加密
     * @param  string $sessionId
     * @return string
     */
    public function sessionEncrypt($sessionId)
    {
        $hashKey = $this->config->get($this->prefix . 'hashkey');
        $hashIv  = $this->config->get($this->prefix . 'hashiv');

        $dataEncrypt = $this->aesEncrypt($sessionId, $hashKey, $hashIv);
        $dataBase64Encode = $this->base64Encode($dataEncrypt);
        $dataUrlEncode = $this->urlEncode($dataBase64Encode);

        return $dataUrlEncode;
    }

    /**
     * AES 解密
     * @param  string $data
     * @param  string $key
     * @param  string $iv
     * @return string
     */
    public function aesDecrypt($data, $key, $iv)
    {
        $decrypted = openssl_decrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    /**
     * AES 加密
     * @param  string $data
     * @param  string $key
     * @param  string $iv
     * @return string
     */
    public function aesEncrypt($data, $key, $iv)
    {
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $encrypted;
    }

    /**
     * Base64編碼
     * @param  string $encode
     * @return array
     */
    public function base64Encode($data)
    {
        return base64_encode($data);
    }

    /**
     * Base64解碼
     * @param  string $encoded
     * @return array
     */
    public function base64Decode($encoded)
    {
        return base64_decode($encoded);
    }

    /**
     * urlencode
     * @param  string $data
     * @return string
     */
    public function urlEncode($data)
    {
        return urlencode($data);


    }

    /**
     * urldecode
     * @param  string $encoded
     * @return string
     */
    public function urlDecode($encoded)
    {
        return urldecode ($encoded);
    }
}
?>