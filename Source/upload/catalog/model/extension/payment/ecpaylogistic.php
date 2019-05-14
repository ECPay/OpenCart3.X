<?php
class ModelExtensionPaymentecpaylogistic extends Model {
	
	/**
	* 顯示付款方式
	* @access	public
	*/
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/ecpaylogistic');	
		
		$method_data = array();
		$method_data = array( 
			'code'  => 'ecpaylogistic',
			'title' => $this->language->get('text_title'),
			'terms' => '',
			'sort_order' => 1
      		);
		
		return $method_data;
	}
}
?>
