<?php

require_once(DIR_SYSTEM . "library/module_helper.php");

use Ecpay\module_helper;

class ecpay_invoice_helper extends module_helper
{
	/**
     * EcpayInvoiceHelper constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 取得綠界發票 API 介接資訊
     *
     * @param  string $action
     * @param  string $merchant_id
     * @return array  $api_info
     */
    public function get_ecpay_invoice_api_info($action = '', $merchant_id = '') {
		$api_info = [
			'action' => '',
		];

        // API URL
		if ($this->isTestMode($merchant_id)) {
			switch ($action) {
				case 'check_Love_code':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode';
					break;
				case 'check_barcode':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode';
					break;
				case 'issue':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue';
					break;
				case 'delay_issue':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue';
					break;
				case 'invalid':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid';
					break;
				case 'cancel_delay_issue':
					$api_info['action'] = 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
					break;
				default:
					break;
			}
		} else {
			switch ($action) {
				case 'check_Love_code':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode';
					break;
				case 'check_barcode':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode';
					break;
				case 'issue':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue';
					break;
				case 'delay_issue':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue';
					break;
				case 'invalid':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid';
					break;
				case 'cancel_delay_issue':
					$api_info['action'] = 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue';
					break;
				default:
					break;
			}
		}

		return $api_info;
  	}

	/**
     * 取得發票自訂編號
     *
     * @param  string $order_id
     * @param  string $order_prefix
     * @return string
     */
    public function get_relate_number($order_id, $order_prefix = '') {
		$relate_no = $order_prefix . substr(str_pad($order_id, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(hash('sha256', (string) time()), -5);
		return substr($relate_no, 0, 20);
  	}
}
