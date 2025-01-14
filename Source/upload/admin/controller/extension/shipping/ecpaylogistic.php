<?php

use Ecpay\Sdk\Factories\Factory;

class ControllerExtensionShippingecpayLogistic extends Controller
{
	private $error = array();
	private $module_name = 'ecpaylogistic';
	private $prefix = 'shipping_ecpaylogistic_';
	private $module_path = 'extension/shipping/ecpaylogistic';
	private $extension_route = 'extension/shipping';
	private $url_secure;
    private $ecpay_logistic_model_name = '';


	// Constructor
	public function __construct($registry) {
	parent::__construct($registry);
		$this->url_secure = ( empty($this->config->get('config_secure')) ) ? false : true ;

        $this->ecpay_logistic_model_name = 'model_extension_shipping_' . $this->module_name;

		$this->load->library('ecpay_logistic_helper');
        $this->helper = $this->registry->get('ecpay_logistic_helper');
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
				'okmart_collection',
				'fami',
				'unimart',
				'hilife',
				'okmart',
				'post',
				'tcat',
			);
			foreach ($shipping_type_list as $type_name) {
				if ($this->request->post[$this->prefix . $type_name . '_status'] != '1') {
					if ($type_name !== 'post') {
						unset($this->request->post[$this->prefix . $type_name . '_fee']);
					} else {
						unset($this->request->post[$this->prefix . $type_name . '_1_fee']);
                        unset($this->request->post[$this->prefix . $type_name . '_2_fee']);
                        unset($this->request->post[$this->prefix . $type_name . '_3_fee']);
                        unset($this->request->post[$this->prefix . $type_name . '_4_fee']);
					}
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
		$data['text_okmart_collection'] = $this->language->get('text_okmart_collection');
		$data['text_okmart'] = $this->language->get('text_okmart');

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
		$data['entry_sender_zipcode'] = $this->language->get('entry_sender_zipcode');
        $data['entry_sender_address'] = $this->language->get('entry_sender_address');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['entry_UNIMART_Collection_fee'] = $this->language->get('entry_UNIMART_Collection_fee');
		$data['entry_FAMI_Collection_fee'] = $this->language->get('entry_FAMI_Collection_fee');
		$data['entry_HILIFE_Collection_fee'] = $this->language->get('entry_HILIFE_Collection_fee');
		$data['entry_OKMART_Collection_fee'] = $this->language->get('entry_OKMART_Collection_fee');
		$data['entry_UNIMART_fee'] = $this->language->get('entry_UNIMART_fee');
		$data['entry_FAMI_fee'] = $this->language->get('entry_FAMI_fee');
		$data['entry_HILIFE_fee'] = $this->language->get('entry_HILIFE_fee');
		$data['entry_OKMART_fee'] = $this->language->get('entry_OKMART_fee');
		$data['entry_POST_1_fee'] = $this->language->get('entry_POST_1_fee');
        $data['entry_POST_2_fee'] = $this->language->get('entry_POST_2_fee');
        $data['entry_POST_3_fee'] = $this->language->get('entry_POST_3_fee');
        $data['entry_POST_4_fee'] = $this->language->get('entry_POST_4_fee');
        $data['entry_TCAT_fee'] = $this->language->get('entry_TCAT_fee');

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
			'OKMART_Collection_fee',
			'FreeShippingAmount',
			'MinAmount',
			'MaxAmount',
			'UNIMART_fee',
			'FAMI_fee',
			'HILIFE_fee',
			'OKMART_fee',
			'POST_1_fee',
            'POST_2_fee',
            'POST_3_fee',
            'POST_4_fee',
            'TCAT_fee',
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
	            'okmart_collection_fee',
				'unimart_fee',
	            'fami_fee',
	            'hilife_fee',
	            'okmart_fee',
				'post_1_fee',
                'post_2_fee',
                'post_3_fee',
                'post_4_fee',
                'tcat_fee',
	            'unimart_status',
	            'fami_status',
	            'hilife_status',
	            'okmart_status',
				'post_status',
				'tcat_status',
	            'unimart_collection_status',
	            'fami_collection_status',
	            'hilife_collection_status',
	            'okmart_collection_status',
	            'geo_zone_id',
	            'status',
	            'free_shipping_amount',
	            'max_amount',
	            'min_amount',
	            'order_status',
	            'sender_name',
	            'sender_cellphone',
				'sender_zipcode',
				'sender_address',
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
			'okmart_collection' => 'OKMART_Collection',
			'fami' => 'FAMI',
			'unimart' => 'UNIMART',
			'hilife' => 'HILIFE',
			'okmart' => 'OKMART',
			'post' => 'POST',
			'tcat' => 'TCAT',
		);
		foreach ($shipping_type_list as $type_name => $error_type_name) {
			if ($this->request->post[$this->prefix . $type_name . '_status'] == '1') {
				if ($type_name !== 'post') {
					if(!is_numeric($this->request->post[$this->prefix . $type_name . '_fee']) || $this->request->post[$this->prefix . $type_name . '_fee'] < 0){
						$this->error[$error_type_name . '_fee'] = $this->language->get('error_' . $error_type_name . '_fee');
					}
				} else {
					if(!is_numeric($this->request->post[$this->prefix . $type_name . '_1_fee']) || $this->request->post[$this->prefix . $type_name . '_1_fee'] < 0){
						$this->error[$error_type_name . '_1_fee'] = $this->language->get('error_' . $error_type_name . '_1_fee');
					}
					if(!is_numeric($this->request->post[$this->prefix . $type_name . '_2_fee']) || $this->request->post[$this->prefix . $type_name . '_2_fee'] < 0){
						$this->error[$error_type_name . '_2_fee'] = $this->language->get('error_' . $error_type_name . '_2_fee');
					}
					if(!is_numeric($this->request->post[$this->prefix . $type_name . '_3_fee']) || $this->request->post[$this->prefix . $type_name . '_3_fee'] < 0){
						$this->error[$error_type_name . '_3_fee'] = $this->language->get('error_' . $error_type_name . '_3_fee');
					}
					if(!is_numeric($this->request->post[$this->prefix . $type_name . '_4_fee']) || $this->request->post[$this->prefix . $type_name . '_4_fee'] < 0){
						$this->error[$error_type_name . '_4_fee'] = $this->language->get('error_' . $error_type_name . '_4_fee');
					}
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

		$order_id = $this->request->get['order_id'];

		$ecpaylogistic_query = $this->db->query('Select * from ' . DB_PREFIX . 'ecpaylogistic_response where order_id=' . (int) $order_id);

		if (!$ecpaylogistic_query->num_rows) {

			$this->load->model('sale/order');
			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$sFieldName = 'code';
				$sFieldValue = 'shipping_' . $this->module_name;
				$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
				$ecpaylogisticSetting = array();
				foreach($get_ecpaylogistic_setting_query->rows as $value) {
					$ecpaylogisticSetting[$value['key']] = $value['value'];
				}

				$logisticSubType = explode(".", $order_info['shipping_code']);

				// 物流類型
				$logisticsType = $this->helper->get_ecpay_logistics_type($logisticSubType[1]);

				if ($ecpaylogisticSetting[$this->prefix . 'type'] == 'C2C') {
					$shippingMethod = [
						'fami' => 'FAMIC2C',
						'fami_collection' => 'FAMIC2C',
						'unimart' => 'UNIMARTC2C',
						'unimart_collection' => 'UNIMARTC2C',
						'hilife' => 'HILIFEC2C',
						'hilife_collection' => 'HILIFEC2C',
						'okmart' => 'OKMARTC2C',
						'okmart_collection' => 'OKMARTC2C',
						'post' => 'POST',
						'tcat' => 'TCAT',
					];
				}
				else {
					$shippingMethod = [
						'fami' => 'FAMI',
						'fami_collection' => 'FAMI',
						'unimart' => 'UNIMART',
						'unimart_collection' => 'UNIMART',
						'hilife' => 'HILIFE',
						'hilife_collection' => 'HILIFE',
						'post' => 'POST',
						'tcat' => 'TCAT',
					];
				}

				if (array_key_exists($logisticSubType[1], $shippingMethod)) {
					$_LogisticsSubType = $shippingMethod[$logisticSubType[1]];
				}

				$_IsCollection = 'N';
				$_CollectionAmount = 0;
				if (strpos($order_info['shipping_code'],"_collection") !== false) {
					$_IsCollection = 'Y';
					$_CollectionAmount = (int)ceil($order_info['total']);
				}

				$this->load->model('catalog/product');
				$goodsWeight = 0;
				$products = $this->model_sale_order->getOrderProducts($order_id);
				$aGoods = array();
				foreach ($products as $product) {
					$aGoods[] = $product['name'] . '(' . $product['model'] . ')';

					// 計算商品重量
					$productInfo =  $this->model_catalog_product->getProduct($product['product_id']);
                    $goodsWeight += $productInfo['weight'] * $product['quantity'];
				}

				$_Goods = '網路商品一批';
				$_SenderCellPhone = '';
				if (isset($ecpaylogisticSetting[$this->prefix . 'sender_cellphone']) && !empty($ecpaylogisticSetting[$this->prefix . 'sender_cellphone'])) {
					$_SenderCellPhone = $ecpaylogisticSetting[$this->prefix . 'sender_cellphone'];
				}

				// 回傳網址
				$server_reply_url = $this->url->link($this->extension_route .'/'. $this->module_name . '/response');
				$server_reply_url = str_replace("admin/","",$server_reply_url) ;

				$apiLogisticInfo  = $this->helper->get_ecpay_logistic_api_info('create', $_LogisticsSubType, $ecpaylogisticSetting);

				$MerchantTradeNo = $this->helper->getMerchantTradeNo($order_id);

				if ($logisticsType === 'CVS') {
					$logistics_c2c_reply_url = $this->url->link($this->extension_route .'/'. $this->module_name . '/logistics_c2c_reply');
					$logistics_c2c_reply_url = str_replace("admin/","",$logistics_c2c_reply_url) ;

					$inputLogisticOrder = array(
                        'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
                        'MerchantTradeNo' => $MerchantTradeNo,
                        'MerchantTradeDate' => date('Y/m/d H:i:s'),
                        'LogisticsType' => $logisticsType,
                        'LogisticsSubType' => $_LogisticsSubType,
                        'GoodsAmount' => (int)ceil($order_info['total']),
                        'CollectionAmount' => $_CollectionAmount,
                        'IsCollection' => $_IsCollection,
                        'GoodsName' => $_Goods,
                        'SenderName' => $ecpaylogisticSetting[$this->prefix . 'sender_name'],
                        'SenderCellPhone' => $_SenderCellPhone,
                        'ReceiverName' => $order_info['shipping_firstname'] . $order_info['shipping_lastname'],
                        'ReceiverCellPhone' => $order_info['telephone'],
                        'ReceiverEmail' => $order_info['email'],
                        'ServerReplyURL' => $server_reply_url,
                        'LogisticsC2CReplyURL' => $logistics_c2c_reply_url,
                        'Remark' => 'ecpay_module_opencart',
                        'ReceiverStoreID' => $order_info['shipping_address_1'],
                        'ReturnStoreID' => $order_info['shipping_address_1']
                    );

					if ($_LogisticsSubType != 'UNIMARTC2C' && $_LogisticsSubType != 'HILIFEC2C' && $_LogisticsSubType != 'OKMARTC2C') {
						unset($inputLogisticOrder['SenderCellPhone']);
					}

				} else if ($logisticsType === 'HOME') {

					// 收件地址
                    $receiverAddress = $order_info['shipping_city'] . $order_info['shipping_address_1'] . $order_info['shipping_address_2'];

					// 取得訂單商品重量
					$ecpayOrderExtendQuery = $this->db->query('Select * from ' . DB_PREFIX . 'ecpay_order_extend where order_id=' . (int)$order_id);
					if (!empty($ecpayOrderExtendQuery->row['goods_weight'])) {
						$goodsWeight = $ecpayOrderExtendQuery->row['goods_weight'];
					}

                    $inputLogisticOrder = array(
                        'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
                        'MerchantTradeNo' => $MerchantTradeNo,
                        'MerchantTradeDate' => date('Y/m/d H:i:s'),
                        'LogisticsType' => $logisticsType,
                        'LogisticsSubType' => $_LogisticsSubType,
                        'GoodsAmount' => (int)ceil($order_info['total']),
                        'GoodsName' => $_Goods,
                        'GoodsWeight' => $goodsWeight,
                        'SenderName' => $ecpaylogisticSetting[$this->prefix . 'sender_name'],
                        'SenderCellPhone' => $_SenderCellPhone,
                        'SenderZipCode' => $ecpaylogisticSetting[$this->prefix . 'sender_zipcode'],
                        'SenderAddress' => $ecpaylogisticSetting[$this->prefix . 'sender_address'],
                        'ReceiverName' => $order_info['shipping_firstname'] . $order_info['shipping_lastname'],
                        'ReceiverCellPhone' => $order_info['telephone'],
                        'ReceiverZipCode' => $order_info['shipping_postcode'],
                        'ReceiverAddress' => $receiverAddress,
                        'ReceiverEmail' => $order_info['email'],
                        'Temperature' => '0001',
                        'Distance' => '00',
                        'Specification' => '0001',
                        'ScheduledPickupTime' => '4',
                        'ScheduledDeliveryTime' => '4',
                        'ServerReplyURL' => $server_reply_url,
                        'Remark' => 'ecpay_module_opencart',
                    );
                }

				try {

					$factory = new Factory([
						'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
						'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
						'hashMethod'    => 'md5',
					]);
					$postService = $factory->create('PostWithCmvEncodedStrResponseService');

					if ($_IsCollection == 'N') {
						unset($inputLogisticOrder['CollectionAmount']);
					}
					$Result = $postService->post($inputLogisticOrder, $apiLogisticInfo['action']);

					// 記錄回傳資訊
					if(true) {
						$this->saveResponse($order_id, $Result);
					}

					if (isset($Result['RtnCode']) && ($Result['RtnCode'] == 300 || $Result['RtnCode'] == 2001)) {

						$sComment = "建立綠界科技物流訂單<br>綠界科技物流訂單編號: " . $Result['1|AllPayLogisticsID'];
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
						$ajax_return['msg'] = print_r($Result['RtnMsg'] , true) . "\n";

						foreach ($Result as $key => $value) {
							if ($key == 'CheckMacValue' || $key == 'RtnMsg') {
								continue;
							}
							$ajax_return['msg'] .= $key . '=' . $value . "\n";
						}

					} else {
						$ajax_return['code'] = 799;
						$ajax_return['rtn'] = '1|ok';
						$ajax_return['msg'] = print_r($Result , true) . "\n";
					}
				} catch(Exception $e) {
					$ajax_return['code'] = 701;
					$ajax_return['rtn'] = '0|fail';
					$ajax_return['msg'] = print_r($e->getMessage() , true) . "\n";
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

		// 物流單列印判斷
		$this->model_setting_event->addEvent('ecpay_logistic_print_shipping', 'admin/view/sale/order_info/before', 'extension/shipping/ecpaylogistic/chk_print_shipping');
		$this->model_setting_event->addEvent('ecpay_logistic_javascript', 'admin/view/common/header/before', 'extension/shipping/ecpaylogistic/add_javascript');


		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->module_name;

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "unimart_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "fami_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hilife_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "okmart_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "post_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "tcat_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "fami_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hilife_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "unimart_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "okmart_collection_status' , `value` = '0';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "order_status' , `value` = '1';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "mid' , `value` = '2000933';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashkey' , `value` = 'XBERn1YOvpM9nfZc';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "hashiv' , `value` = 'h1ONHk4P4yqbl5LK';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "type' , `value` = 'C2C';");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = 0 , `" . $sFieldName . "` = '" . $sFieldValue . "' , `key` = '" . $this->prefix . "sender_name' , `value` = '綠界科技';");

		// 記錄物流訂單回傳資訊
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ecpaylogistic_response` (
              `order_id` INT(11) DEFAULT '0' NOT NULL,
              `MerchantID` varchar(20) DEFAULT '0' NULL,
              `MerchantTradeNo` varchar(20) DEFAULT '0' NULL,
              `RtnCode` INT(10) DEFAULT '0' NULL,
              `RtnMsg` VARCHAR(200) DEFAULT '0' NULL,
              `AllPayLogisticsID` varchar(20) DEFAULT '0' NULL,
              `LogisticsType` varchar(20) DEFAULT '0' NULL,
              `LogisticsSubType` varchar(20) DEFAULT '0' NULL,
              `GoodsAmount` INT(10) DEFAULT '0' NULL,
              `UpdateStatusDate` varchar(20) DEFAULT '0' NULL,
              `ReceiverName` varchar(60) DEFAULT '0' NULL,
              `ReceiverPhone` varchar(20) DEFAULT '0' NULL,
              `ReceiverCellPhone` varchar(20) DEFAULT '0' NULL,
              `ReceiverEmail` varchar(50) DEFAULT '0' NULL,
              `ReceiverAddress` varchar(200) DEFAULT '0' NULL,
              `CVSPaymentNo` varchar(15) DEFAULT '0' NULL,
              `CVSValidationNo` varchar(10) DEFAULT '0' NULL,
              `BookingNote` varchar(50) DEFAULT '0' NULL,
              `createdate` INT(10) DEFAULT '0' NULL
            ) DEFAULT COLLATE=utf8_general_ci;"
        );
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
		$this->model_setting_event->deleteEventByCode('ecpay_logistic_print_shipping');
		$this->model_setting_event->deleteEventByCode('ecpay_logistic_javascript');

	}

	// 判斷後台是否產生建立物流按鈕
	public function chk_create_shipping(&$route, &$data, &$output) {

		// Token
		$token = $this->session->data['user_token'];
		$create_shipping_flag = true ;
		$order_info = $this->model_sale_order->getOrder($data['order_id']);

		// 訂單沒有物流方式時不繼續
		if (isset($order_info['shipping_code']) && $order_info['shipping_code'] != '') {
			// 判斷物流方式
			if (strpos($order_info['shipping_code'], 'ecpaylogistic.') === false) {
				$create_shipping_flag = false ;
			}

			// 物流類型
			$logisticSubType = explode('.', $order_info['shipping_code']);
			$logisticsType = $this->helper->get_ecpay_logistics_type($logisticSubType[1]);

			// 判斷物流狀態
			$ecpaylogistic_query = $this->db->query('Select * from ' . DB_PREFIX.'ecpaylogistic_response where order_id='.(int)$data['order_id']);

			// 已經建立過物流訂單
			if ($ecpaylogistic_query->num_rows) {
				$create_shipping_flag = false ;
			}

			// 顯示建立按鈕
			if ($create_shipping_flag) {
				$create_shipping_order_url = $this->url->link(
					$this->extension_route .'/'. $this->module_name . '/create_shipping_order',
					'user_token=' . $token . '&order_id=' . $data['order_id'],
					$this->url_secure
				);

				$express_map_url = $this->url->link(
					$this->extension_route .'/'. $this->module_name . '/express_map',
					'user_token=' . $token . '&order_id=' . $data['order_id'],
					$this->url_secure
				);

				// 變更門市按鈕
				if ($logisticsType === 'CVS') {
					if (isset($data['shipping_address_1'])) {
						$data['shipping_address_1'] .= "&nbsp;" . '<input type="button" id="ecpaylogistic_store" class="btn btn-primary btn-xs" value="變更門市"  onClick="ecpay_express_map(\'' . $express_map_url . '\')"/>';
					} else {
						$data['shipping_address'] .= "<br>" . '<input type="button" id="ecpaylogistic_store" class="btn btn-primary btn-xs" value="變更門市"  onClick="ecpay_express_map(\'' . $express_map_url . '\')"/>';
					}
				}

				$data['shipping_method'] .= "&nbsp;" . '<input type="button" id="ecpaylogistic" class="btn btn-primary btn-xs" value="建立物流訂單" onClick="ecpay_create_shipping(\'' . $create_shipping_order_url . '\')"/>';
			}
		}
	}

	// 判斷後台是否顯示物流單列印按鈕
	public function chk_print_shipping(&$route, &$data, &$output) {

		// Token
		$token = $this->session->data['user_token'];

		$print_logistic_flag = true ;
		$html = '' ;

        $orderInfo = $this->model_sale_order->getOrder($data['order_id']);

		// 訂單沒有物流方式時不繼續
		if (isset($orderInfo['shipping_code']) && $orderInfo['shipping_code'] != '') {

			// 判斷物流方式
			if (strpos($orderInfo['shipping_code'], 'ecpaylogistic.') === false) {
				$print_logistic_flag = false;
			}

			// 判斷物流狀態
			$ecpaylogistic_query = $this->db->query('Select * from ' . DB_PREFIX . 'ecpaylogistic_response where order_id=' . (int)$data['order_id']);

			// 尚未建立過物流訂單
			if ($ecpaylogistic_query->num_rows === 0) {
				$print_logistic_flag = false ;
			}

			// 顯示列印按鈕
			if ($print_logistic_flag) {
				$sFieldName = 'code';
				$sFieldValue = 'shipping_' . $this->module_name;
				$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");

				$ecpaylogisticSetting = array();
				foreach($get_ecpaylogistic_setting_query->rows as $value){
					$ecpaylogisticSetting[$value['key']] = $value['value'];
				}

				$factory = new Factory([
					'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
					'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
					'hashMethod'    => 'md5',
				]);

				$action = "";
				$inputPrint = array();

				$apiLogisticInfo  = $this->helper->get_ecpay_logistic_api_info('print', $ecpaylogistic_query->row['LogisticsSubType'], $ecpaylogisticSetting);



				$inputPrint = array(
					'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
					'AllPayLogisticsID' => $ecpaylogistic_query->row['AllPayLogisticsID'],
					'PlatformID' => ''
				);

				switch ($ecpaylogistic_query->row['LogisticsSubType']) {
					case 'FAMIC2C':
					case 'HILIFEC2C':
					case 'OKMARTC2C':
						try {
							$inputPrint['CVSPaymentNo'] = $ecpaylogistic_query->row['CVSPaymentNo'];
						} catch(Exception $e) {
							echo $e->getMessage();
						}
						break;
					case 'UNIMARTC2C':
						try {
							$inputPrint['CVSPaymentNo'] = $ecpaylogistic_query->row['CVSPaymentNo'];
							$inputPrint['CVSValidationNo'] = $ecpaylogistic_query->row['CVSValidationNo'];
						} catch(Exception $e) {
							echo $e->getMessage();
						}
						break;
					case 'FAMI':
					case 'UNIMART':
					case 'HILIFE':
						break;
				}

				$autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');
				$form_print =  $autoSubmitFormService->generate($inputPrint, $apiLogisticInfo['action'], '_Blank','ecpay_print');

				$form_print =  str_replace('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>', '', $form_print);
				$form_print =  str_replace('</body></html>', '', $form_print);
				$form_print =  str_replace('<script type="text/javascript">document.getElementById("ecpay_print").submit();</script>', '', $form_print);

				$data['shipping_method'] .= "&nbsp;" . '<input type="button" id="ecpaylogistic_print" class="btn btn-primary btn-xs" onclick="document.getElementById(\'ecpay_print\').submit()" id="ecpaylogistic_print" value="列印物流單" />' . $form_print;
			}
		}
	}

	// 增加javascript
	public function add_javascript(&$route, &$data, &$output) {
  		$data['scripts'][] = 'view/javascript/ecpay/js/jquery.blockUI.js';
   		$data['scripts'][] = 'view/javascript/ecpay/js/ecpaylogistic.js';
	}

	// 電子地圖選擇門市
	public function express_map() {

		$this->load->model('sale/order');

		$order_id = $this->request->get['order_id'];

		$order_info = $this->model_sale_order->getOrder($order_id);

		// Token
        $token = $this->session->data['user_token'];

		$ecpaylogisticSetting = array();

		$sFieldName = 'code';
		$sFieldValue = 'shipping_' . $this->module_name;
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

		$logisticSubType = explode(".", $order_info['shipping_code']);

		if (array_key_exists($logisticSubType[1], $shippingMethod)) {
			$al_subtype = $shippingMethod[$logisticSubType[1]];
		}

		if (!isset($al_subtype)) {
			exit;
		}

		$al_iscollection = 'N';

		//
		$al_srvreply = $this->url->link(
			$this->extension_route .'/'. $this->module_name . '/response_map',
			'user_token=' . $token ,
			$this->url_secure
		);

		try {
			$factory = new Factory([
				'hashKey'       => $ecpaylogisticSetting[$this->prefix . 'hashkey'],
				'hashIv'        => $ecpaylogisticSetting[$this->prefix . 'hashiv'],
				'hashMethod'    => 'md5',
			]);
            $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

			$inputMap = array(
				'MerchantID' => $ecpaylogisticSetting[$this->prefix . 'mid'],
                'LogisticsType'    => $this->helper->get_logistics_type($al_subtype),
				'MerchantTradeNo' => $this->helper->getMerchantTradeNo($order_id),
				'LogisticsSubType' => $al_subtype,
				'IsCollection' => $al_iscollection,
				'ServerReplyURL' => $al_srvreply,
				'ExtraData' => '',
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

	// 儲存物流訂單回覆
    public function saveResponse($order_id = 0, $feedback = array()) {

        if (empty($order_id) === true) {
            return false;
        }

        $white_list = array(
			'MerchantID',
			'MerchantTradeNo',
			'RtnCode',
			'RtnMsg',
			'1|AllPayLogisticsID',
			'LogisticsType',
			'LogisticsSubType',
			'GoodsAmount',
			'UpdateStatusDate',
			'ReceiverName',
			'ReceiverPhone',
			'ReceiverCellPhone',
			'ReceiverEmail',
			'ReceiverAddress',
			'CVSPaymentNo',
			'CVSValidationNo',
			'BookingNote',
        );

        $inputs = $this->only($feedback, $white_list);

        $insert_sql = 'INSERT INTO `%s`';
        $insert_sql .= ' (`order_id`, `MerchantID`, `MerchantTradeNo`, `RtnCode`, `RtnMsg`, `AllPayLogisticsID`, `LogisticsType`, `LogisticsSubType`, `GoodsAmount`, `UpdateStatusDate`, `ReceiverName`, `ReceiverPhone`, `ReceiverCellPhone`, `ReceiverEmail`, `ReceiverAddress`, `CVSPaymentNo`, `CVSValidationNo`, `BookingNote`, `createdate`)';
        $insert_sql .= " VALUES (%d, '%s', '%s', %d, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)";
        $table = DB_PREFIX . 'ecpaylogistic_response';
        $now_time  = time() ;

        return $this->db->query(sprintf(
            $insert_sql,
            $table,
            (int)$order_id,
            $this->db->escape($inputs['MerchantID']),
            $this->db->escape($inputs['MerchantTradeNo']),
            $this->db->escape($inputs['RtnCode']),
            $this->db->escape($inputs['RtnMsg']),
            $this->db->escape($inputs['1|AllPayLogisticsID']),
            $this->db->escape($inputs['LogisticsType']),
            $this->db->escape($inputs['LogisticsSubType']),
            $this->db->escape($inputs['GoodsAmount']),
            $this->db->escape($inputs['UpdateStatusDate']),
            $this->db->escape($inputs['ReceiverName']),
            $this->db->escape($inputs['ReceiverPhone']),
            $this->db->escape($inputs['ReceiverCellPhone']),
            $this->db->escape(str_replace(' ', '+', $inputs['ReceiverEmail'])),
            $this->db->escape($inputs['ReceiverAddress']),
            $this->db->escape($inputs['CVSPaymentNo']),
            $this->db->escape($inputs['CVSValidationNo']),
            $this->db->escape($inputs['BookingNote']),
            $now_time )
    	);
    }

    /**
     * Filter the inputs
     * @param array $source Source data
     * @param array $whiteList White list
     * @return array
     */
    public function only($source = array(), $whiteList = array())
    {
        $variables = array();

        // Return empty array when do not set white list
        if (empty($whiteList) === true) {
            return $source;
        }

        foreach ($whiteList as $name) {
            if (isset($source[$name]) === true) {
                $variables[$name] = $source[$name];
            } else {
                $variables[$name] = '';
            }
        }
        return $variables;
    }
}
