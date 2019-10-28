<?php
class ControllerExtensionShippingecpayLogistic extends Controller 
{
	private $error = array();
	private $module_name = 'ecpaylogistic';
	private $prefix = 'shipping_ecpaylogistic_';
	private $module_path = 'extension/shipping/ecpaylogistic';
	private $extension_route = 'extension/shipping';
	private $url_secure;

	// Constructor
	public function __construct($registry) {
	parent::__construct($registry);

		$this->url_secure = ( empty($this->config->get('config_secure')) ) ? false : true ;
	}

	public function index() {
		$this->load->language($this->module_path);
		$heading_title = $this->language->get('heading_title');
		$this->document->setTitle($heading_title);
		$this->load->model('setting/setting');

		// Token
		$token = $this->session->data['user_token'];
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$shipping_type_list = array(
				'unimart_collection',
				'fami_collection',
				'hilife_collection',
				'fami',
				'unimart',
				'hilife',
			);
			foreach ($shipping_type_list as $type_name) {
				if ($this->request->post[$this->prefix . $type_name . '_status'] != '1') {
	                unset($this->request->post[$this->prefix . $type_name . '_fee']);
	            }
			}
			unset($shipping_type_list);

			$module_settings = $this->request->post;
			$this->model_setting_setting->editSetting('shipping_' . $this->module_name, $module_settings);

			$payment_status_name = str_replace('shipping', 'payment', $this->prefix) . 'status';
			$payment_status_value = $module_settings[$this->prefix . 'status'];
			$this->model_setting_setting->editSetting('payment_' . $this->module_name, array(
				$payment_status_name => $payment_status_value
			));
					
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=shipping', true));
		}
		
		$data['heading_title'] = $heading_title;
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_general'] = $this->language->get('text_general');
		$data['text_unimart_collection'] = $this->language->get('text_unimart_collection');
		$data['text_fami_collection'] = $this->language->get('text_fami_collection');
		$data['text_unimart'] = $this->language->get('text_unimart');
		$data['text_fami'] = $this->language->get('text_fami');
		$data['text_hilife_collection'] = $this->language->get('text_hilife_collection');
		$data['text_hilife'] = $this->language->get('text_hilife');

		$data['text_sender_cellphone'] = $this->language->get('text_sender_cellphone');

		$data['entry_mid'] = $this->language->get('entry_mid');
		$data['entry_hashkey'] = $this->language->get('entry_hashkey');
		$data['entry_hashiv'] = $this->language->get('entry_hashiv');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_FreeShippingAmount'] = $this->language->get('entry_FreeShippingAmount');
		$data['entry_MinAmount'] = $this->language->get('entry_MinAmount');
		$data['entry_MaxAmount'] = $this->language->get('entry_MaxAmount');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_sender_name'] = $this->language->get('entry_sender_name');
		$data['entry_sender_cellphone'] = $this->language->get('entry_sender_cellphone');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['entry_UNIMART_Collection_fee'] = $this->language->get('entry_UNIMART_Collection_fee');
		$data['entry_FAMI_Collection_fee'] = $this->language->get('entry_FAMI_Collection_fee');
		$data['entry_HILIFE_Collection_fee'] = $this->language->get('entry_HILIFE_Collection_fee');
		$data['entry_UNIMART_fee'] = $this->language->get('entry_UNIMART_fee');
		$data['entry_FAMI_fee'] = $this->language->get('entry_FAMI_fee');
		$data['entry_HILIFE_fee'] = $this->language->get('entry_HILIFE_fee');
	
		if (isset($this->error['error_warning'])) {
			$data['error_warning'] = $this->error['error_warning'];
		} else {
			$data['error_warning'] = '';
		}

		$ecpayErrorList = array(
			'mid',
			'hashkey',
			'hashiv',
			'UNIMART_Collection_fee',
			'FAMI_Collection_fee',
			'HILIFE_Collection_fee',
			'FreeShippingAmount',
			'MinAmount',
			'MaxAmount',
			'UNIMART_fee',
			'FAMI_fee',
			'HILIFE_fee',
			'sender_name',
			'sender_cellphone',
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
            		'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=shipping', true)
		);
		$data['breadcrumbs'][] = array(
            		'text' => $heading_title,
            		'href' => $this->url->link($this->module_path, 'user_token=' . $token, true)
		);          
		
		$data[$this->prefix . 'types'] = array();
		$data[$this->prefix . 'types'][] = array(
			'value' => 'C2C',
			'text' => 'C2C'
		);
		$data[$this->prefix . 'types'][] = array(
			'value' => 'B2C',
			'text' => 'B2C'
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
		
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		$data['action'] = $this->url->link($this->module_path, 'user_token=' . $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=shipping', true);

		// Get the setting
	        $settings = array(
	            'mid',
	            'hashkey',
	            'hashiv',
	            'type',
	            'unimart_collection_fee',
	            'fami_collection_fee',
	            'hilife_collection_fee',
	            'geo_zone_id',
	            'status',
	            'unimart_status',
	            'unimart_collection_status',
	            'fami_status',
	            'hilife_status',
	            'fami_collection_status',
	            'hilife_collection_status',
	            'unimart_fee',
	            'fami_fee',
	            'hilife_fee',
	            'free_shipping_amount',
	            'max_amount',
	            'min_amount',
	            'order_status',
	            'sender_name',
	            'sender_cellphone',
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
		
		$this->response->setOutput($this->load->view('extension/shipping/' . $this->module_name, $data));
	}
	
	private function validate() {
		if (!$this->user->hasPermission('modify', $this->module_path)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

			// Required fields validate
	        $require_fields = array(
	            'mid',
	            'hashkey',
	            'hashiv',
	            'sender_name',
	        );
	        foreach ($require_fields as $name) {
	        	if (empty($this->request->post[$this->prefix . $name])) {
					$this->error[$name] = $this->language->get('error_' . $name);
				}
	        }
	        unset($require_fields);

		$bite_sender_name = $this->bite_str($this->request->post[$this->prefix . 'sender_name'],0,10);
		if ($bite_sender_name != $this->request->post[$this->prefix . 'sender_name']) {
			$this->error['sender_name'] = $this->language->get('error_sender_name_length');
		}

		if ( empty($this->request->post[$this->prefix . 'sender_cellphone'])) {
			$this->error['sender_cellphone'] = $this->language->get('error_sender_cellphone');
		}
		else{
			if( !preg_match('/^[0-9]{10}$/', $this->request->post[$this->prefix . 'sender_cellphone'] ) ) 
	        	{	
	        		$this->error['sender_cellphone'] = $this->language->get('error_sender_cellphone_length');
	        	}
		}


		// Shipping fee validation
		$shipping_type_list = array(
			'unimart_collection' => 'UNIMART_Collection',
			'fami_collection' => 'FAMI_Collection',
			'hilife_collection' => 'HILIFE_Collection',
			'fami' => 'FAMI',
			'unimart' => 'UNIMART',
			'hilife' => 'HILIFE',
		);
		foreach ($shipping_type_list as $type_name => $error_type_name) {
			if ($this->request->post[$this->prefix . $type_name . '_status'] == '1') {
	            if(!is_numeric($this->request->post[$this->prefix . $type_name . '_fee']) || $this->request->post[$this->prefix . $type_name . '_fee'] < 0){
	                $this->error[$error_type_name . '_fee'] = $this->language->get('error_' . $error_type_name . '_fee');
	            }
	        }
		}
		unset($shipping_type_list);

		if (!is_numeric($this->request->post[$this->prefix . 'min_amount']) || $this->request->post[$this->prefix . 'min_amount'] < 0){
			$this->error['MinAmount'] = $this->language->get('error_MinAmount');
		}
		if (!is_numeric($this->request->post[$this->prefix . 'free_shipping_amount']) || $this->request->post[$this->prefix . 'free_shipping_amount'] < 0){
			$this->error['FreeShippingAmount'] = $this->language->get('error_FreeShippingAmount');
		}
		if (!is_numeric($this->request->post[$this->prefix . 'max_amount']) || $this->request->post[$this->prefix . 'max_amount'] < 0 || $this->request->post[$this->prefix . 'max_amount'] <= $this->request->post[$this->prefix . 'min_amount'] || $this->request->post[$this->prefix . 'max_amount'] >= 20000){
			$this->error['MaxAmount'] = $this->language->get('error_MaxAmount');
		}
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	// 建立物流訂單
	public function create_shipping_order() {
		
		$ajax_return['code'] = 700;
		$ajax_return['rtn'] = '0|fail';
		$ajax_return['msg'] = '';

		// $this->load->language($this->module_path);
		$order_id = $this->request->get['order_id'];

		$ecpaylogistic_query = $this->db->query("Select * from ecpaylogistic_info where order_id=". (int) $order_id);

		if (!$ecpaylogistic_query->num_rows) {

			$this->load->model('sale/order');
			$order_info = $this->model_sale_order->getOrder($order_id);
			
			if ($order_info) {

				// 載入物流SDK
				$sSkdPath =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.'ECPay.Logistics.Integration.php' ;

				include_once($sSkdPath);

				$sFieldName = 'code';
				$sFieldValue = 'shipping_' . $this->module_name;
				$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
				$ecpaylogisticSetting=array();
				foreach($get_ecpaylogistic_setting_query->rows as $value){
					$ecpaylogisticSetting[$value["key"]]=$value["value"];
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

				$logisticSubType = explode(".", $order_info['shipping_code']);

				if (array_key_exists($logisticSubType[1], $shippingMethod)) {
					$_LogisticsSubType = $shippingMethod[$logisticSubType[1]];
				}

				$_IsCollection = IsCollection::NO;
				$_CollectionAmount = 0;
				if (strpos($order_info['shipping_code'],"_collection") !== false) {
					$_IsCollection = IsCollection::YES;
					$_CollectionAmount = (int)ceil($order_info['total']);
				}
				
				$products = $this->model_sale_order->getOrderProducts($order_id);
				$aGoods = array();
				foreach ($products as $product) {
					$aGoods[] = $product['name'] . '(' . $product['model'] . ')';
				}

				$_Goods = '網路商品一批';
				$_SenderCellPhone = '';
				if (isset($ecpaylogisticSetting[$this->prefix . 'sender_cellphone']) && !empty($ecpaylogisticSetting[$this->prefix . 'sender_cellphone'])) {
					$_SenderCellPhone = $ecpaylogisticSetting[$this->prefix . 'sender_cellphone'];
				}
				
				$MerchantTradeNo = (($ecpaylogisticSetting[$this->prefix . 'mid']=='2000132') || ($ecpaylogisticSetting[$this->prefix . 'mid']=='2000933')) ? (date('YmdHis') . $order_id) : $order_id;
				
				// 回傳網址
				$server_reply_url = $this->url->link($this->extension_route .'/'. $this->module_name . '/response');
				$server_reply_url = str_replace("admin/","",$server_reply_url) ;
			        
				$logistics_c2c_reply_url = $this->url->link($this->extension_route .'/'. $this->module_name . '/logistics_c2c_reply');
				$logistics_c2c_reply_url = str_replace("admin/","",$logistics_c2c_reply_url) ;

				try {
					$AL = new ECPayLogistics();
					$AL->HashKey = $ecpaylogisticSetting[$this->prefix . 'hashkey'];
					$AL->HashIV = $ecpaylogisticSetting[$this->prefix . 'hashiv'];
					$AL->Send = array(
						'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
						'MerchantTradeNo' => $MerchantTradeNo,
						'MerchantTradeDate' => date('Y/m/d H:i:s'),
						'LogisticsType' => LogisticsType::CVS,
						'LogisticsSubType' => $_LogisticsSubType,
						'GoodsAmount' => (int)ceil($order_info['total']),
						'CollectionAmount' => $_CollectionAmount,
						'IsCollection' => $_IsCollection,
						'GoodsName' => $_Goods,
						'SenderName' => $ecpaylogisticSetting[$this->prefix . 'sender_name'],
						'SenderCellPhone' => $_SenderCellPhone,
						'ReceiverName' => $order_info['shipping_lastname'] . $order_info['shipping_firstname'],
						'ReceiverCellPhone' => $order_info['telephone'],
						'ReceiverEmail' => $order_info['email'],
						'ServerReplyURL' => $server_reply_url,
						'LogisticsC2CReplyURL' => $logistics_c2c_reply_url,
						'Remark' => 'ecpay_module_opencart',
					);
					$AL->SendExtend = array(
						'ReceiverStoreID' => $order_info['shipping_address_1'],
						'ReturnStoreID' => $order_info['shipping_address_1']
					);
					if ($_IsCollection == IsCollection::NO) {
						unset($AL->Send['CollectionAmount']);
					}
					if ($_LogisticsSubType != LogisticsSubType::UNIMART_C2C && $_LogisticsSubType != LogisticsSubType::HILIFE_C2C) {
						unset($AL->Send['SenderCellPhone']);
					}
					
					$Result = $AL->BGCreateShippingOrder();

					if ($Result['ResCode'] == 1) {
						$this->db->query("INSERT INTO `ecpaylogistic_info` SET `order_id` =" . (int) $order_id .", `AllPayLogisticsID` = '" . $this->db->escape($Result['AllPayLogisticsID']) ."';");
						$sComment = "建立綠界科技物流訂單<br>綠界科技物流訂單編號: " . $Result['AllPayLogisticsID'];
						if (isset($Result["CVSPaymentNo"]) && !empty($Result["CVSPaymentNo"])) {
							$sComment .= "<br>寄貨編號: " . $Result["CVSPaymentNo"];
						}
						
						if (isset($Result["CVSValidationNo"]) && !empty($Result["CVSValidationNo"])) {
							$sComment .= $Result["CVSValidationNo"];
						}

						$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = 3, notify = '0', comment = '" . $this->db->escape($sComment) . "', date_added = NOW()");
						
						$this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = 3 WHERE order_id = ". (int) $order_id);
						

						$ajax_return['code'] = 799;
						$ajax_return['rtn'] = '1|ok';
						$ajax_return['msg'] .= $Result['RtnMsg'] .  "\n";

						foreach ($Result as $key => $value) {
							if ($key == 'CheckMacValue' || $key == 'RtnMsg') {
								continue;
							}
							$ajax_return['msg'] .= $key . '=' . $value .  "\n";
						}

					} else {
						$ajax_return['code'] = 799;
						$ajax_return['rtn'] = '1|ok';
						$ajax_return['msg'] = print_r($Result , true) .  "\n";
					}
				} catch(Exception $e) {
					//echo $e->getMessage();

					$ajax_return['code'] = 701;
					$ajax_return['rtn'] = '0|fail';
					$ajax_return['msg'] = print_r($e->getMessage() , true) .  "\n";
				}

			} else {
				echo $this->language->get('error_order_info');
			}
		} else {
			echo $this->language->get('error_shipping_order_exists');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($ajax_return));
	}
	
	private function bite_str($string, $start, $len, $byte=3){
		$str     = "";
		$count   = 0;
		$str_len = strlen($string);
		for ($i=0; $i<$str_len; $i++) {
			if (($count+1-$start)>$len) {
				$str  .= "...";
				break;
			} elseif ((ord(substr($string,$i,1)) <= 128) && ($count < $start)) {
				$count++;
			} elseif ((ord(substr($string,$i,1)) > 128) && ($count < $start)) {
				$count = $count+2;
				$i     = $i+$byte-1;
			} elseif ((ord(substr($string,$i,1)) <= 128) && ($count >= $start)) {
				$str  .= substr($string,$i,1);
				$count++;
			} elseif ((ord(substr($string,$i,1)) > 128) && ($count >= $start)) {
				$str  .= substr($string,$i,$byte);
				$count = $count+2;
				$i     = $i+$byte-1;
			}
		}
		return $str;
	}

	public function install() {
		$this->model_setting_extension->install('payment', 'ecpaylogistic');
		$this->load->model('user/user_group');
		 $this->load->controller('common/extension/extension/payment');
		if (method_exists($this->user,"getGroupId")) {
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/ecpaylogistic');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/ecpaylogistic');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', $this->module_path);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $this->module_path);
		} 

		// EVENT ADD
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent('ecpay_logistic_payment_method', 'catalog/model/setting/extension/getExtensions/after', 'extension/payment/ecpaylogistic/chk_payment_method');
		$this->model_setting_event->addEvent('ecpay_logistic_create_shipping', 'admin/view/sale/order_info/before', 'extension/shipping/ecpaylogistic/chk_create_shipping');
		$this->model_setting_event->addEvent('ecpay_logistic_javascript', 'admin/view/common/header/before', 'extension/shipping/ecpaylogistic/add_javascript');


		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->module_name;

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "unimart_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "fami_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hilife_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "fami_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hilife_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "unimart_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "order_status' , `value` = '1';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "mid' , `value` = '2000933';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashkey' , `value` = 'XBERn1YOvpM9nfZc';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashiv' , `value` = 'h1ONHk4P4yqbl5LK';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "type' , `value` = 'C2C';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "sender_name' , `value` = '綠界科技';");
		$this->db->query("
				CREATE TABLE IF NOT EXISTS `ecpaylogistic_info` (
				  `order_id` INT(11) NOT NULL,
				  `AllPayLogisticsID` VARCHAR(50) NOT NULL,
				  KEY `order_id` (`order_id`)
				) DEFAULT COLLATE=utf8_general_ci;");
	}
	
	public function uninstall() {
		$this->model_setting_extension->uninstall('payment', 'ecpaylogistic');
		$this->load->model('user/user_group');
		if (method_exists($this->user,"getGroupId")) {
			$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/payment/ecpaylogistic');
			$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/payment/ecpaylogistic');
			$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', $this->module_path);
			$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', $this->module_path);
		} 

		// delete event
		$this->model_setting_event->deleteEventByCode('ecpay_logistic_payment_method');
		$this->model_setting_event->deleteEventByCode('ecpay_logistic_create_shipping');
		$this->model_setting_event->deleteEventByCode('ecpay_logistic_javascript');

	}

	// 判斷後台是否產生建立物流按鈕
	public function chk_create_shipping(&$route, &$data, &$output) {

		// Token
        	$token = $this->session->data['user_token'];

		$create_shipping_flag = true ;

	        $order_info = $this->model_sale_order->getOrder($data['order_id']);

	        // 判斷物流方式
	        if ( strpos($order_info['shipping_code'], "ecpaylogistic.") === false) {
	        	$create_shipping_flag = false ;
	        }

	        // // 判斷物流狀態
	        $ecpaylogistic_query = $this->db->query("Select * from ecpaylogistic_info where order_id=". (int) $data['order_id']);
	        if ( $ecpaylogistic_query->num_rows ) {
	        	$create_shipping_flag = false ; // 已經建立過物流訂單
	        }

	        // 顯示
	        if($create_shipping_flag) {
	        	
	        	$create_shipping_order_url = $this->url->link(
		                $this->extension_route .'/'. $this->module_name . '/create_shipping_order',
		                'user_token=' . $token . '&order_id='.$data['order_id'],
		                $this->url_secure
		        );

	        	$express_map_url = $this->url->link(
		                $this->extension_route .'/'. $this->module_name . '/express_map',
		                'user_token=' . $token . '&order_id='.$data['order_id'],
		                $this->url_secure
		            );


	        	if (isset($data['shipping_address_1'])) {
				$data['shipping_address_1'] .= "&nbsp;" . '<input type="button" id="ecpaylogistic_store" class="btn btn-primary btn-xs" value="變更門市"  onClick="ecpay_express_map(\''.$express_map_url.'\')"/>';
			} else {
				$data['shipping_address'] .= "<br>" . '<input type="button" id="ecpaylogistic_store" class="btn btn-primary btn-xs" value="變更門市"  onClick="ecpay_express_map(\''.$express_map_url.'\')"/>';
			}
			$data['shipping_method'] .= "&nbsp;" . '<input type="button" id="ecpaylogistic" class="btn btn-primary btn-xs" value="建立物流訂單" onClick="ecpay_create_shipping(\''.$create_shipping_order_url.'\')"/>';
		}
	}

	// 增加javascript
	public function add_javascript(&$route, &$data, &$output) {
  				        
		$this->document->addScript('view/javascript/ecpay/js/jquery.blockUI.js');
		$this->document->addScript('view/javascript/ecpay/js/ecpaylogistic.js');

		$data['scripts'] = $this->document->getScripts();
	}

	// 電子地圖選擇門市
	public function express_map() {
		
		$this->load->model('sale/order');

		$order_id = $this->request->get['order_id'];

		$order_info = $this->model_sale_order->getOrder($order_id);

		// Token
        	$token = $this->session->data['user_token'];

		$ecpaylogisticSetting = array();

		// 載入物流SDK
		$sSkdPath =  dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'extension'.DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.'ECPay.Logistics.Integration.php' ;

		include_once($sSkdPath);

		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->module_name;
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

		


		$logisticSubType = explode(".", $order_info['shipping_code']);

		if (array_key_exists($logisticSubType[1], $shippingMethod)) {
			$al_subtype = $shippingMethod[$logisticSubType[1]];
		}

		if (!isset($al_subtype)) {
			exit;
		}

		$al_iscollection = IsCollection::NO;

		// 
		$al_srvreply = $this->url->link(
	                $this->extension_route .'/'. $this->module_name . '/response_map',
	                'user_token=' . $token ,
	                $this->url_secure
	        );

		try {
			$AL = new ECPayLogistics();
			$AL->Send = array(
				'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
				// 'MerchantTradeNo' => 'no' . date('YmdHis'),
				'MerchantTradeNo' => $order_id,

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


		$token = $_GET['user_token'] ;


		// 將門市資訊寫回訂單
		$this->db->query("UPDATE " . DB_PREFIX . "order SET shipping_address_1 = '". $this->db->escape($shipping_address_1) ."', shipping_address_2 = '" . $this->db->escape($shipping_address_2) . "' WHERE order_id = ".(int) $order_id);


		// 轉導訂單資訊
		$order_view_url = $this->url->link(
	                'sale/order/info',
	                'user_token=' . $token . '&order_id='.$order_id,
	                $this->url_secure
	        );

		$this->response->redirect($order_view_url);	
	}	
}