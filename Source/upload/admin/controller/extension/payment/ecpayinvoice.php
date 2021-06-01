<?php
class ControllerExtensionPaymentECPayInvoice extends Controller 
{
	private $error = array();
	private $module_name = 'ecpayinvoice';
	private $prefix = 'payment_ecpayinvoice_';
	private $module_path = 'extension/payment/ecpayinvoice';
	
	public function index() 
	{
		$this->load->language($this->module_path);
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		// Token
		$token = $this->session->data['user_token'];
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_' . $this->module_name, $this->request->post);		
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=payment', true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_autoissue'] = $this->language->get('text_autoissue');

		$data['entry_mid'] = $this->language->get('entry_mid');
		$data['entry_hashkey'] = $this->language->get('entry_hashkey');
		$data['entry_hashiv'] = $this->language->get('entry_hashiv');
		$data['entry_autoissue'] = $this->language->get('entry_autoissue');
		$data['entry_invoice_url'] = $this->language->get('entry_invoice_url');
		$data['entry_status'] = $this->language->get('entry_status');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
	
		if (isset($this->error['error_warning'])) {
			$data['error_warning'] = $this->error['error_warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$ecpayErrorList = array(
			'mid',
			'hashkey',
			'hashiv',
			'url',
		);
		foreach ($ecpayErrorList as $errorName) {
			if (isset($this->error[$errorName])) {
				$data['error_' . $errorName] = $this->error[$errorName];
			} else {
				$data['error_' . $errorName] = '';
			}
		}
		unset($ecpayErrorList);

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)
		);
	        
	        $data['breadcrumbs'][] = array(
	            'text' => $this->language->get('text_extension'),
	            'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=payment', true)
	        );

		$data['breadcrumbs'][] = array(
    		'text' => $this->language->get('heading_title'),
    		'href' => $this->url->link('extension/payment/ecpayinvoice', 'user_token=' . $token, true)
        	); 
		
		$data[$this->prefix . 'statuses'] = array();
		$data[$this->prefix . 'statuses'][] = array(
			'value' => '1',
			'text' => $this->language->get('text_enabled')
		);
		$data[$this->prefix . 'statuses'][] = array(
			'value' => '0',
			'text' => $this->language->get('text_disabled')
		);
		
		$data[$this->prefix . 'autoissues'] = array();
		$data[$this->prefix . 'autoissues'][] = array(
			'value' => '0',
			'text' => $this->language->get('text_disabled')
		);
		$data[$this->prefix . 'autoissues'][] = array(
			'value' => '1',
			'text' => $this->language->get('text_enabled')
		);

		$data['action'] = $this->url->link($this->module_path, 'user_token=' . $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token, true);
		
	        // Get the setting
	        $settings = array(
	            'mid',
	            'hashkey',
	            'hashiv',
	            'autoissue',
	            'status',
	            'url',
	        );

	        foreach ($settings as $name) {
	        	$variable_name = $this->prefix . $name;
	            if (isset($this->request->post[$variable_name])) {
					$data[$variable_name] = $this->request->post[$variable_name];
				} else {
					$data[$variable_name] = $this->config->get($variable_name);
				}
	        }
			
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/payment/' . $this->module_name, $data));
	}
	
	private function validate() 
	{
		if (!$this->user->hasPermission('modify', $this->module_path)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		// Required fields validate
	        $require_fields = array(
	            'mid',
	            'hashkey',
	            'hashiv',
	            'url',
	        );
	        foreach ($require_fields as $name) {
	        	if (empty($this->request->post[$this->prefix . $name])) {
					$this->error[$name] = $this->language->get('error_' . $name);
				}
	        }
	        unset($require_fields);
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	// 手動開立發票
	public function createInvoiceNo()
	{
		$this->load->language('sale/order');
		
		if (!$this->user->hasPermission('modify', 'sale/order')){

		} elseif (isset($this->request->get['order_id'])) {

			if (isset($this->request->get['order_id'])) {

				$order_id = $this->request->get['order_id'];
			} else {

				$order_id = 0;
			}
			
			$this->load->model('sale/order');
			
			// 判斷是否啟動ECPAY電子發票開立
			$nInvoice_Status = $this->config->get($this->prefix . 'status');
			
			if($nInvoice_Status == 1) {

				// 1.參數初始化
				define('WEB_MESSAGE_NEW_LINE',	'|');	// 前端頁面訊息顯示換行標示語法
				$sMsg				= '' ;
				$sMsg_P2			= '' ;		// 金額有差異提醒
				$bError 			= false ; 	// 判斷各參數是否有錯誤，沒有錯誤才可以開發票
				
				// 2.取出開立相關參數
				
				// *連線資訊
				//$sEcpayinvoice_Url_Issue	= 'http://einvoice-stage.ecpay.com.tw/Invoice/Issue';		// 一般開立網址
				
				$sEcpayinvoice_Url_Issue	= $this->config->get($this->prefix . 'url');			// 一般開立網址
				$nEcpayinvoice_Mid 		= $this->config->get($this->prefix . 'mid') ;			// 廠商代號
				$sEcpayinvoice_Hashkey 		= $this->config->get($this->prefix . 'hashkey');			// 金鑰
				$sEcpayinvoice_Hashiv 		= $this->config->get($this->prefix . 'hashiv') ;			// 向量

				// $sEcpayinvoice_Url_Issue	= $this->config->get('ecpayinvoice_url');			// 一般開立網址
				// $nEcpayinvoice_Mid 		= $this->config->get('ecpayinvoice_mid') ;			// 廠商代號
				// $sEcpayinvoice_Hashkey 		= $this->config->get('ecpayinvoice_hashkey');			// 金鑰
				// $sEcpayinvoice_Hashiv 		= $this->config->get('ecpayinvoice_hashiv') ;			// 向量
				
				// *訂單資訊
				$aOrder_Info_Tmp 		= $this->model_sale_order->getOrder($order_id);			// 訂單資訊
				$aOrder_Product_Tmp  		= $this->model_sale_order->getOrderProducts($order_id);		// 訂購商品
				$aOrder_Total_Tmp  		= $this->model_sale_order->getOrderTotals($order_id);		// 訂單金額
				
				// *統編與愛心碼資訊
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "invoice_info WHERE order_id = '" . (int)$order_id . "'" );
					
				// 3.判斷資料正確性
				if( $query->num_rows == 0 ) {

					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '開立發票資訊不存在。';

				} else {
					$aInvoice_Info = $query->rows[0] ;
				}
				
				// *URL判斷是否有值
				if($sEcpayinvoice_Url_Issue == '') {
					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '請填寫發票傳送網址。';
				}
				
				// *MID判斷是否有值
				if($nEcpayinvoice_Mid == '') {
					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '請填寫商店代號(Merchant ID)。';
				}
				
				// *HASHKEY判斷是否有值
				if($sEcpayinvoice_Hashkey == '') {
					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '請填寫金鑰(Hash Key)。';
				}
				
				// *HASHIV判斷是否有值
				if($sEcpayinvoice_Hashiv == '') {
					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '請填寫向量(Hash IV)。';
				}
				
				// 判斷是否開過發票
				if($aOrder_Info_Tmp['invoice_no'] != 0) {
					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . '已存在發票紀錄，請重新整理頁面。';
				}
	
				// 判斷商品是否存在
				if(count($aOrder_Product_Tmp) < 0) {

					$bError = true ;
					$sMsg .= ( empty($sMsg) ? '' : WEB_MESSAGE_NEW_LINE ) . ' 該訂單編號不存在商品，不允許開立發票。';
				} else {

					// 判斷商品是否含小數點
					foreach( $aOrder_Product_Tmp as $key => $value)
					{
						if( !strstr($value['price'], '.00') ) {
							$sMsg_P2 .= ( empty($sMsg_P2) ? '' : WEB_MESSAGE_NEW_LINE ) . '提醒：商品 ' . $value['name'] . ' 金額存在小數點，將以無條件進位開立發票。';
						}
					}
				}

				if(!$bError){

					$sLove_Code 			= '' ;
					$nDonation			= '0' ;
					$nPrint				= '0' ;
					$sCustomerIdentifier		= '' ;
					
					if($aInvoice_Info['invoice_type'] == 1){

						$nDonation 		= '0' ;					// 不捐贈
						$nPrint			= '0' ;
						$sCustomerIdentifier	= '' ;

					} elseif($aInvoice_Info['invoice_type'] == 2) {

						$nDonation 		= '0' ;					// 公司發票 不捐贈
						$nPrint			= '1' ;					// 公司發票 強制列印
						$sCustomerIdentifier	= $aInvoice_Info['company_write'] ;	// 公司統一編號

					} elseif($aInvoice_Info['invoice_type'] == 3) {

						$nDonation 		= '1' ;
						$nPrint			= '0' ;
						$sLove_Code 		= $aInvoice_Info['love_code'] ;
						$sCustomerIdentifier	= '' ;

					} else {

						$nDonation 		= '0' ;
						$nPrint			= '0' ;
						$sLove_Code 		= '' ;
						$sCustomerIdentifier	= '' ;	
					}	
					
					// 4.送出參數
					try {

						$sFile_Name =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'payment'.DIRECTORY_SEPARATOR.'Ecpay_Invoice.php' ;

						include_once($sFile_Name);

						$ecpay_invoice = new EcpayInvoice ;
						
						// A.寫入基本介接參數
						$ecpay_invoice->Invoice_Method 			= 'INVOICE' ;
						$ecpay_invoice->Invoice_Url 			= $sEcpayinvoice_Url_Issue ;
						$ecpay_invoice->MerchantID 			= $nEcpayinvoice_Mid ;
						$ecpay_invoice->HashKey 			= $sEcpayinvoice_Hashkey ;
						$ecpay_invoice->HashIV 				= $sEcpayinvoice_Hashiv ;
						
						// B.送出開立發票參數
						$aItems	= array();
						
						// *算出商品各別金額
						$nSub_Total_Real = 0 ;	// 實際無條進位小計
						
						foreach( $aOrder_Product_Tmp as $key => $value)
						{
							$nQuantity 	= ceil($value['quantity']) ;
							$nPrice		= ceil($value['price']) ;
							$nTotal		= $nQuantity * $nPrice	 ; 				// 各商品小計

							$nSub_Total_Real = $nSub_Total_Real + $nTotal ;				// 計算發票總金額
							
						 	$sProduct_Name 	= $value['name'] ;
						 	$sProduct_Note 	= $value['model'] . '-' . $value['product_id'] ;

						 	mb_internal_encoding('UTF-8');
						 	$nString_Limit 	= 10 ;
						 	$nSource_Length = mb_strlen($sProduct_Note);
						 	
						 	if ( $nString_Limit < $nSource_Length ) {

		                        $nString_Limit = $nString_Limit - 3;

		                        if ( $nString_Limit > 0 ) {

		                                $sProduct_Note = mb_substr($sProduct_Note, 0, $nString_Limit) . '...';
		                        }
			                }
						 	
							array_push($ecpay_invoice->Send['Items'], array('ItemName' => $sProduct_Name, 'ItemCount' => $nQuantity, 'ItemWord' => '批', 'ItemPrice' => $nPrice, 'ItemTaxType' => 1, 'ItemAmount' => $nTotal, 'ItemRemark' => $sProduct_Note )) ;
						}
						
						// *找出sub-total
						$total = 0 ;
						foreach( $aOrder_Total_Tmp as $key2 => $value2)
						{
							if($value2['title'] == 'Total') {

								$total = (int)$value2['value'];
								break;
							}	
						}

						// 其他項目計算
						if(true){

							foreach( $aOrder_Total_Tmp as $key2 => $value2)
							{
								if($value2['code'] != 'total' && $value2['code'] != 'sub_total') {

									$nSub_Total_Real = $nSub_Total_Real + (int) $value2['value'] ; // 計算發票總金額

									array_push($ecpay_invoice->Send['Items'], array('ItemName' => $value2['title'], 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => (int) $value2['value'], 'ItemTaxType' => 1, 'ItemAmount' => (int) $value2['value'], 'ItemRemark' => $value2['title'] )) ;
								}	
							}
						}
						
						// 無條件位後加總有差異
						if($total != $nSub_Total_Real ) {

							$sMsg_P2 .= ( empty($sMsg_P2) ? '' : WEB_MESSAGE_NEW_LINE ) . '綠界科技電子發票開立，實際金額 $' . $total . '， 無條件進位後 $' . $nSub_Total_Real;
						}
						
						$RelateNumber	= $order_id ;
						//$RelateNumber = 'ECPAY'. date('YmdHis') . rand(1000000000,2147483647) ; // 產生測試用自訂訂單編號
						
						$ecpay_invoice->Send['RelateNumber'] 			= $RelateNumber ;
						$ecpay_invoice->Send['CustomerID'] 			= '' ;
						$ecpay_invoice->Send['CustomerIdentifier'] 		= $sCustomerIdentifier ;
						$ecpay_invoice->Send['CustomerName'] 			= $aOrder_Info_Tmp['firstname'] ;
						$ecpay_invoice->Send['CustomerAddr'] 			= $aOrder_Info_Tmp['payment_country'] . $aOrder_Info_Tmp['payment_postcode'] . $aOrder_Info_Tmp['payment_city'] . $aOrder_Info_Tmp['payment_address_1'] . $aOrder_Info_Tmp['payment_address_2'];
						$ecpay_invoice->Send['CustomerPhone'] 			= $aOrder_Info_Tmp['telephone'] ;
						$ecpay_invoice->Send['CustomerEmail'] 			= $aOrder_Info_Tmp['email'] ;
						$ecpay_invoice->Send['ClearanceMark'] 			= '' ;
						$ecpay_invoice->Send['Print'] 				= $nPrint ;
						$ecpay_invoice->Send['Donation'] 			= $nDonation ;
						$ecpay_invoice->Send['LoveCode'] 			= $sLove_Code ;
						$ecpay_invoice->Send['CarruerType'] 			= '' ;
						$ecpay_invoice->Send['CarruerNum'] 			= '' ;
						$ecpay_invoice->Send['TaxType'] 			= 1 ;
						$ecpay_invoice->Send['SalesAmount'] 			= $nSub_Total_Real ;	
						$ecpay_invoice->Send['InvType'] 			= '07' ;
						$ecpay_invoice->Send['vat'] 				= '' ;
						$ecpay_invoice->Send['InvoiceRemark'] 			= 'OC2_ECPayInvoice' ;
						
						// C.送出與返回
						$aReturn_Info = $ecpay_invoice->Check_Out();
						
					}catch (Exception $e) {

						// 例外錯誤處理。
						$sMsg = $e->getMessage();
					}
					
					// 5.有錯誤訊息或回傳狀態RtnCode不等於1 則不寫入DB
					if( $sMsg != '' || !isset($aReturn_Info['RtnCode']) || $aReturn_Info['RtnCode'] != 1 ) {

						$sMsg .= '綠界科技電子發票手動開立訊息' ;
						$sMsg .= (isset($aReturn_Info)) ? print_r($aReturn_Info, true) : '' ; 
						

						
						// A.寫入LOG
						$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$aOrder_Info_Tmp['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($sMsg) . "', date_added = NOW()");
	
					} else {

						// 無條件進位 金額有差異，寫入LOG提醒管理員
						if( $sMsg_P2 != '' ) {

							$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$aOrder_Info_Tmp['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($sMsg_P2) . "', date_added = NOW()");
						} 
						
						// A.更新發票號碼欄位
						$invoice_no 		= $aReturn_Info['InvoiceNumber'] ;
						
						// B.整理發票號碼並寫入DB
						$sInvoice_No_Pre 	= substr($invoice_no ,0 ,2 ) ;
						$sInvoice_No 		= substr($invoice_no ,2) ; 
						
						// C.回傳資訊轉陣列提供history資料寫入
						$sReturn_Info		= '綠界科技電子發票手動開立訊息' ;
						$sReturn_Info		.= print_r($aReturn_Info, true);
						
						$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_no = '" . $sInvoice_No . "', invoice_prefix = '" . $this->db->escape($sInvoice_No_Pre) . "' WHERE order_id = '" . (int)$order_id . "'");
						$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$aOrder_Info_Tmp['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($sReturn_Info) . "', date_added = NOW()");

						return $invoice_no ;
					}

					

				} else {

					$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$aOrder_Info_Tmp['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($sMsg) . "', date_added = NOW()");
				}	
			}
		}
	}
	
	public function install() 
	{
		// EVENT ADD
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent('ecpay_invoice_create', 'admin/model/sale/order/createInvoiceNo/before', 'extension/payment/ecpayinvoice/createInvoiceNo');

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "invoice_info` (
			  `order_id` INT(11) NOT NULL,
			  `love_code` VARCHAR(50) NOT NULL,
			  `company_write` VARCHAR(10) NOT NULL,
			  `invoice_type` TINYINT(2) NOT NULL,
			  `createdate` INT(10)  NOT NULL
			) DEFAULT COLLATE=utf8_general_ci;");
			
		// 異動電子發票欄位型態
		$this->db->query(" ALTER TABLE `" . DB_PREFIX . "order` CHANGE `invoice_no` `invoice_no` VARCHAR(10) NOT NULL DEFAULT '0'; ");	
		$sFieldName = 'code';
		$sFieldValue = 'payment_' . $this->module_name;
		$query = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "setting LIKE 'code'");
		if ( $query->num_rows == 0 )
		{
			$sFieldName = 'group';
		} 

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "mid' , `value` = '2000132';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashkey' , `value` = 'ejCk326UnaZWKisg';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashiv' , `value` = 'q9jcZX8Ib9LM8wYk';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "autoissue' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "url' , `value` = 'https://einvoice-stage.ecpay.com.tw/Invoice/Issue';");
	}
}