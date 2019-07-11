<?php
class ControllerExtensionPaymentecpaylogistic extends Controller {
	private $module_name = 'ecpaylogistic';
	private $lang_prefix = '';
	private $module_path = '';
	private $id_prefix = '';
	private $setting_prefix = '';

	private $name_prefix = '';

	private $helper = null;
	private $ecpay_invoice_module_name = 'ecpayinvoice';
	private $ecpay_invoice_setting_prefix = '';

	// Logistic
	private $ecpay_logistic_module_name = 'ecpaylogistic';
	private $ecpay_logistic_module_path = '';

	// Constructor
	public function __construct($registry) {
		parent::__construct($registry);

		// Set the variables
		$this->lang_prefix = $this->module_name .'_';
		$this->id_prefix = 'shipping-' . $this->module_name;
		$this->setting_prefix = 'shipping_' . $this->module_name . '_';
		$this->module_path = 'extension/payment/' . $this->module_name;
		
		$this->name_prefix = 'shipping_' . $this->module_name;
		$this->load->model($this->module_path);	

		// invoice
		$this->ecpay_invoice_setting_prefix = 'payment_' . $this->ecpay_invoice_module_name . '_';

		 // logistic
        	$this->ecpay_logistic_module_path = 'extension/shipping/' . $this->ecpay_logistic_module_name;
	}

	public function index() {

		// PAYMENT
	        if(true)
	        {
			// Get the translations
			$this->load->language($this->module_path);
			$data['text_checkout_button'] = $this->language->get($this->lang_prefix . 'text_checkout_button');
			$data['text_title'] = $this->language->get($this->lang_prefix . 'text_title');

			// Set the view data
			$data['id_prefix'] = $this->id_prefix;
			$data['module_name'] = $this->module_name;
			$data['name_prefix'] = $this->name_prefix;
			

		}
		
		 // INVOICE
	        if(true)
	        {
	            // 判斷電子發票模組是否啟用 1.啟用 0.未啟用
	            $ecpayInvoiceStatus = $this->config->get($this->ecpay_invoice_setting_prefix . 'status');
	            $data['ecpay_invoce_status'] = $ecpayInvoiceStatus ;

	            $data['ecpay_invoce_text_title'] = $this->language->get($this->ecpay_invoice_module_name . '_text_title');
	        }

	        // 轉導至門市選擇 
                $data['redirect_url'] = $this->url->link(
                    $this->ecpay_logistic_module_path . '/express_map',
                    '',
                    $this->url_secure
                );


		// Load the template
	        $view_path = $this->module_path;
	        return $this->load->view($this->module_path, $data);
	}

	// 異動訂單狀態
	public function update_order_status(){
		$this->load->model('checkout/order');

		$order_id = $this->request->get['order_id'];

		// Update order status
		$status_id = $this->config->get($this->setting_prefix . 'order_status');

		// Clear the cart
		$this->cart->clear();

		$this->model_checkout_order->addOrderHistory($order_id, $status_id);
		$this->response->redirect($this->url->link('checkout/success'));
	}

	// 依照物流過濾付款方式(EVENT)
	public function chk_payment_method(&$route, &$data, &$output) {

		
		if($data[0] == 'payment')
		{
		        $delivery_method_collection = array('ecpaylogistic.unimart_collection','ecpaylogistic.fami_collection','ecpaylogistic.hilife_collection');
		        $delivery_method = array('ecpaylogistic.unimart','ecpaylogistic.fami','ecpaylogistic.hilife');

		        if( isset( $this->session->data['shipping_method']['code']) ) {
		            
		            // 判斷貨到付款
		            if( in_array( $this->session->data['shipping_method']['code'], $delivery_method_collection) ) {
		                
		                // 只留下貨到付款
		                foreach($output as $key => $payment ) {
		                    if( $payment['code'] != 'ecpaylogistic' ) {
		                    	unset($output[$key]);
		                    }
		                }
		            }

		            // 判斷貨到不付款
		            if( in_array( $this->session->data['shipping_method']['code'], $delivery_method) ) {
		                // 只留下ecpayAIO
		                foreach($output as $key => $payment ) {
		                    if( $payment['code'] != 'ecpaypayment' ) {
		                    	unset($output[$key]);
		                    }
		                }
		            }

		            // 判斷非綠物
		            if( !in_array( $this->session->data['shipping_method']['code'], $delivery_method) && !in_array( $this->session->data['shipping_method']['code'], $delivery_method_collection)) {
		            	foreach($output as $key => $payment ) {
		                    if( $payment['code'] == 'ecpaylogistic' ) {
		                    	unset($output[$key]);
		                    }
		                }
		            }
		        }

		        if (empty($data['payment_methods'])) {
				$data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
			}
		}	
	}


	
}
