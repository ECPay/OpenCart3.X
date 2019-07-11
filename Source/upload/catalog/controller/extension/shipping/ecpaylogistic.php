<?php
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

		$this->load->model($this->ecpay_logistic_module_path);
		$this->{$this->ecpay_logistic_model_name}->loadLibrary();
		$this->helper = $this->{$this->ecpay_logistic_model_name}->getHelper();
	}

	protected function index() {
	}

	// 電子地圖選擇門市
	public function express_map() {
		
		$ecpaylogisticSetting=array();

		// 載入物流SDK
		$sSkdPath =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.'ECPay.Logistics.Integration.php' ;

		include_once($sSkdPath);


		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->ecpay_logistic_module_name;
		$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
		
		foreach( $get_ecpaylogistic_setting_query->rows as $value ) {
			$ecpaylogisticSetting[$value["key"]] = $value["value"];
		}
		
		
		if ( $ecpaylogisticSetting[$this->prefix . 'type'] == 'C2C' ) {
			$shippingMethod = [
				'fami' => LogisticsSubType::FAMILY_C2C,
				'fami_collection' => LogisticsSubType::FAMILY_C2C,
				'unimart' => LogisticsSubType::UNIMART_C2C,
				'unimart_collection' => LogisticsSubType::UNIMART_C2C,
				'hilife' => LogisticsSubType::HILIFE_C2C,
				'hilife_collection' => LogisticsSubType::HILIFE_C2C
			];
		} else {
			$shippingMethod = [
				'fami' => LogisticsSubType::FAMILY,
				'fami_collection' => LogisticsSubType::FAMILY,
				'unimart' => LogisticsSubType::UNIMART,
				'unimart_collection' => LogisticsSubType::UNIMART,
				'hilife' => LogisticsSubType::HILIFE,
				'hilife_collection' => LogisticsSubType::HILIFE
			];
		}

		$logisticSubType = explode(".", $this->session->data['shipping_method']['code']);

		if (array_key_exists($logisticSubType[1], $shippingMethod)) {
			$al_subtype = $shippingMethod[$logisticSubType[1]];
		}

		if (!isset($al_subtype)) {
			exit;
		}

		$al_iscollection = IsCollection::NO;
		$al_srvreply = $this->url->link($this->ecpay_logistic_module_path . '/response_map','',$this->url_secure);

		try {
			$AL = new ECPayLogistics();
			$AL->Send = array(
				'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
				// 'MerchantTradeNo' => 'no' . date('YmdHis'),
				'MerchantTradeNo' => $this->session->data['order_id'],

				'LogisticsSubType' => $al_subtype,
				'IsCollection' => $al_iscollection,
				'ServerReplyURL' => $al_srvreply,
				'ExtraData' => '',
				'Device' => Device::PC
			);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		$html = $AL->CvsMap('');
		echo $html;
	}

	// 電子地圖選擇門市回傳
	public function response_map() {
		

		$order_id = $_POST['MerchantTradeNo'] ;
		$shipping_address_1 = (isset($_POST['CVSStoreID'])) ? $_POST['CVSStoreID'] : '' ;
		$shipping_address_2 = (isset($_POST['CVSStoreName'])) ? $_POST['CVSStoreName'] : '' ;
		$shipping_address_2 = (isset($_POST['CVSAddress'])) ? $shipping_address_2 . ' ' .$_POST['CVSAddress'] : $shipping_address_2 ;

		// 將門市資訊寫回訂單
		$this->db->query("UPDATE " . DB_PREFIX . "order SET shipping_address_1 = '".$shipping_address_1."', shipping_address_2 = '" . $shipping_address_2 . "' WHERE order_id = ".(int) $order_id);



		// 判斷是否為 超商取貨付款
		if($this->session->data['payment_method']['code'] == 'ecpaylogistic')
		{
			// 判斷電子發票模組是否啟用 1.啟用 0.未啟用
			$ecpayInvoiceStatus = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');
			if($ecpayInvoiceStatus === 1){
			    $this->addInvoice($order_id);
			}

			// 轉導回異動訂單狀態
			$this->response->redirect($this->url->link($this->ecpay_logistic_payment_module_path . '/update_order_status','order_id='. $order_id, $this->url_secure));
		}
		else
		{
			// 轉導ECPAY付款
			$this->response->redirect($this->url->link($this->ecpay_payment_module_path . '/redirect','',$this->url_secure));
		}	
	}

	// 建立物流訂單回傳
	public function response() {
		// 載入物流SDK
		$sSkdPath =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.'ECPay.Logistics.Integration.php' ;

		include_once($sSkdPath);

		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->ecpay_logistic_module_name;
		$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
		$ecpaylogisticSetting = array();
		foreach($get_ecpaylogistic_setting_query->rows as $value)
		{
			$ecpaylogisticSetting[$value["key"]]=$value["value"];
		}

		try {

			$AL = new ECPayLogistics();
			$AL->HashKey = $ecpaylogisticSetting[$this->prefix . 'hashkey'];
			$AL->HashIV = $ecpaylogisticSetting[$this->prefix . 'hashiv'];
			$AL->CheckOutFeedback($this->request->post);
			$MerchantTradeNo = (($this->request->post['MerchantID']=='2000132') || ($this->request->post['MerchantID']=='2000933')) ? substr($this->request->post['MerchantTradeNo'], 14) : $this->request->post['MerchantTradeNo'];
			$order_id = (int)$MerchantTradeNo;
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . $order_id . "'" );
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
					'hilife_collection'
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

						if($invoice_autoissue === '1') {
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

		// 載入物流SDK
		$sSkdPath =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.'ECPay.Logistics.Integration.php' ;

		include_once($sSkdPath);

		$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'ecpaylogistic'");
		$ecpaylogisticSetting=array();
		foreach($get_ecpaylogistic_setting_query->rows as $value){
			$ecpaylogisticSetting[$value["key"]]=$value["value"];
		}
		try {
			$AL = new ECPayLogistics();
			$AL->HashKey = $ecpaylogisticSetting[$this->prefix . 'hashkey'];
			$AL->HashIV = $ecpaylogisticSetting[$this->prefix . 'hashiv'];
			$AL->CheckOutFeedback($this->request->post);
			$query = $this->db->query("SELECT * FROM `ecpaylogistic_info` WHERE AllPayLogisticsID =".$this->db->escape($this->request->post['AllPayLogisticsID']));
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
		$white_list = array('invoice_type','company_write','love_code','invoice_status');
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

		$this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
	}

	// insert Invice Info(DB)
	public function addInvoice( $order_id = '' ) {
		$nNowTime  = time() ;
		$sLoveCode = isset($this->session->data['love_code']) ? $this->session->data['love_code'] : '';
		$sCompanyWrite = isset($this->session->data['company_write']) ? $this->session->data['company_write'] : '';
		$nInvoiceType = isset($this->session->data['invoice_type']) ? $this->session->data['invoice_type'] : '';

		// 資料寫入 invoice_info 資料表
		$this->db->query("INSERT INTO `" . DB_PREFIX . "invoice_info` (`order_id`, `love_code`, `company_write`, `invoice_type`, `createdate`) VALUES ('" . $order_id . "', '" . $sLoveCode . "', '" . $sCompanyWrite . "', '" . $nInvoiceType . "', '" . $nNowTime . "' )" );

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


		$this->helper->echoJson(array('response' => 'ok', 'input'=> $inputs));
	}
}
?>