<?php

require_once(DIR_SYSTEM . "library/module_helper.php");
use Ecpay\module_helper;

class ecpay_logistic_helper extends module_helper
{
	private $prefix = 'shipping_';
    private $module_name = 'ecpaylogistic';
	private $setting_prefix = 'shipping_ecpaylogistic_';
	protected $db;

    /**
     * EcpayPaymentHelper constructor.
     */
    public function __construct($registry) {
        parent::__construct();
        $this->db = $registry->get('db');
    }

    public function get_logistics_type($shipping_sub_type) {
        $shipping_type = '';

        switch ($shipping_sub_type) {
            case 'FAMIC2C':
            case 'UNIMARTC2C':
            case 'HILIFEC2C':
            case 'OKMARTC2C':
            case 'FAMI':
            case 'UNIMART':
            case 'HILIFE':
            case 'OKMART':
                $shipping_type = 'CVS';
                break;
            case 'TCAT':
            case 'POST':
                $shipping_type = 'HOME';
                break;
        }

        return $shipping_type;
    }

    public function get_logistics_sub_type($shipping_method, $shipping_type) {
        switch ($shipping_method) {
            case 'fami':
            case 'fami_collection':
                $shipping_sub_type = 'FAMI';
                break;
            case 'unimart':
            case 'unimart_collection':
                $shipping_sub_type = 'UNIMART';
                break;
            case 'hilife':
            case 'hilife_collection':
                $shipping_sub_type = 'HILIFE';
                break;
            case 'okmart':
            case 'okmart_collection':
                $shipping_sub_type = 'OKMART';
                break;
        }

        if ($shipping_type == 'C2C') {
            $shipping_sub_type = $shipping_sub_type . 'C2C';
        }

        return $shipping_sub_type;
    }

    public function get_ecpay_logistic_api_info($action = '', $shipping_method = '', $ecpaylogisticSetting = [])
    {
        $api_info = [
            'action' => '',
        ];

        // URL位置判斷
        if ($ecpaylogisticSetting[$this->setting_prefix . 'test_mode']) {
            if ($ecpaylogisticSetting[$this->setting_prefix . 'type'] == 'B2C') {
                $api_info['merchantId'] = '2000132';
                $api_info['hashKey'] = '5294y06JbISpM5x9';
                $api_info['hashIv'] = 'v77hoKGq4kWxNNIS';
            }
            else {
                $api_info['merchantId'] = '2000933';
                $api_info['hashKey'] = 'XBERn1YOvpM9nfZc';
                $api_info['hashIv'] = 'h1ONHk4P4yqbl5LK';
            }

            switch ($action) {
                case 'map':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/map';
                    break;
                case 'create':
                    $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/Create';
                    break;
                case 'print':
                    if ($ecpaylogisticSetting[$this->setting_prefix . 'type'] == 'C2C') {
                        switch ($shipping_method) {
                            case 'UNIMARTC2C':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo';
                                break;
                            case 'FAMIC2C':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo';
                                break;
                            case 'HILIFEC2C':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo';
                                break;
                            case 'OKMARTC2C':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo';
                                break;
                            case 'POST':
                            case 'TCAT':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument';
                                break;
                            default:
                                $api_info['action'] = '';
                                break;
                        }

                    } else if ($ecpaylogisticSetting[$this->setting_prefix . 'type'] == 'B2C') {
                        switch ($shipping_method) {
                            case 'UNIMART':
                            case 'FAMI':
                            case 'HILIFE':
                            case 'TCAT':
                            case 'POST':
                                $api_info['action'] = 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument';
                                break;
                            default:
                                $api_info['action'] = '';
                                break;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        else {
            $api_info['merchantId'] = $ecpaylogisticSetting[$this->setting_prefix . 'mid'];
            $api_info['hashKey'] = $ecpaylogisticSetting[$this->setting_prefix . 'hashkey'];
            $api_info['hashIv'] = $ecpaylogisticSetting[$this->setting_prefix . 'hashiv'];

            switch ($action) {
                case 'map':
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/map';
                    break;
                case 'create':
                    $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/Create';
                    break;
                case 'print':
                    if ($ecpaylogisticSetting[$this->setting_prefix . 'type'] == 'C2C') {
                        switch ($shipping_method) {
                            case 'UNIMARTC2C':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo';
                                break;
                            case 'FAMIC2C':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo';
                                break;
                            case 'HILIFEC2C':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo';
                                break;
                            case 'OKMARTC2C':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/Express/PrintOKMARTC2COrderInfo';
                                break;
                            case 'TCAT':
                            case 'POST':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument';
                                break;
                            default:
                                $api_info['action'] = '';
                                break;
                        }
                    }
                    else if ($ecpaylogisticSetting[$this->setting_prefix . 'type'] == 'B2C') {
                        switch ($shipping_method) {
                            case 'UNIMART':
                            case 'FAMI':
                            case 'HILIFE':
                            case 'OKMART':
                            case 'TCAT':
                            case 'POST':
                                $api_info['action'] = 'https://logistics.ecpay.com.tw/helper/printTradeDocument';
                                break;
                            default:
                                $api_info['action'] = '';
                                break;
                        }
                    }
                    break;
                default:
                    break;
            }
        }

        return $api_info;
    }

    /**
     * 計算中華郵政重量運費
     * @param $weight_class 1.KG 2.Gram 3.Pound 4.Ounce
     * @return string
     */
    public function cal_home_post_shipping_cost($weight, $weight_class)
    {
        // 判斷重量單位
        $weight_diff = 1;
        switch ($weight_class) {
            case '2':
                $weight_diff = 0.001;
                break;
            case '3':
                $weight_diff = 0.45359237;
                break;
            case '4':
                $weight_diff = 0.0283495231;
                break;
        }

        // 轉換重量為公斤制
        $weight = $weight * $weight_diff;

        if ($weight <= 5) {
            return '1';
        } else if ($weight > 5 && $weight <= 10) {
            return '2';
        } else if ($weight > 10 && $weight <= 15) {
            return '3';
        } else if ($weight > 15 && $weight <= 20) {
            return '4';
        } else  {
			return '4';
		}
    }

    /**
     * 取得綠界物流
     * @return array $ecpayAllLogistics
     */
    public function get_ecpay_all_logistics()
    {
        $ecpayAllLogistics = array_merge($this->get_ecpay_cvs_logistics(), $this->get_ecpay_home_logistics());
        return $ecpayAllLogistics;
    }

    /**
     * 取得綠界宅配物流
     * @return array
     */
    public function get_ecpay_home_logistics()
    {
        return [
            'tcat',
            'tcat_collection',
            'post',
        ];
    }

    /**
     * 取得綠界超商物流
     * @return array
     */
    public function get_ecpay_cvs_logistics()
    {
        return [
            'unimart',
            'unimart_collection',
            'fami',
            'fami_collection',
            'hilife',
            'hilife_collection',
            'okmart',
            'okmart_collection'
        ];
    }

    /**
     * 取得綠界物流類型
     * @return string|bool
     */
    public function get_ecpay_logistics_type(string $shippingMethod)
    {
        if ($this->is_ecpay_home_logistics($shippingMethod)) {
            return 'HOME';
        } else if ($this->is_ecpay_cvs_logistics($shippingMethod)) {
            return 'CVS';
        } else {
            return false;
        }
    }

    /**
     * 判斷是否為綠界物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function is_ecpay_logistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->get_ecpay_all_logistics());
    }

    /**
     * 判斷是否為綠界宅配物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function is_ecpay_home_logistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->get_ecpay_home_logistics());
    }

    /**
     * 判斷是否為綠界超商物流
     * @param  string $shippingMethod
     * @return bool
     */
    public function is_ecpay_cvs_logistics(string $shippingMethod)
    {
        return in_array($shippingMethod, $this->get_ecpay_cvs_logistics());
    }

    // 取得物流設定
	public function get_logistic_settings() {
		$ecpaylogisticSetting = array();
		$sFieldName = 'code';
		$sFieldValue = $this->prefix . $this->module_name;
		$get_ecpaylogistic_setting_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `" . $sFieldName . "` = '" . $sFieldValue . "'");
		$ecpaylogisticSetting = array();
		foreach($get_ecpaylogistic_setting_query->rows as $value) {
			$ecpaylogisticSetting[$value['key']] = $value['value'];
		}
		return $ecpaylogisticSetting;
	}
}
