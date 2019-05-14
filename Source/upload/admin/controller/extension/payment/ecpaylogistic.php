<?php
class ControllerExtensionPaymentecpayLogistic extends Controller 
{
	private $error = array();
	private $module_name = 'ecpaylogistic';
	private $prefix = 'shipping_ecpaylogistic_';
	private $module_path = 'extension/shipping/ecpaylogistic';

	public function index() 
	{
		$this->response->redirect($this->url->link('extension/shipping/ecpaylogistic', 'user_token=' . $this->session->data['user_token'], true));
	}
	
	private function validate() 
	{
		return true;
	}
	
	public function install() 
	{	
	}
	
	public function uninstall() 
	{
	}
}
