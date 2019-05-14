<?php
class ModelExtensionPaymentEcpaypayment extends Model {
    private $module_name = 'ecpaypayment';
    private $lang_prefix = '';
    private $module_path = '';
    private $setting_prefix = '';
	private $libraryList = array('EcpayPaymentHelper.php');
	private $helper = null;
    private $extend_table_name = 'order_extend';

    // Constructor
    public function __construct($registry) {
        parent::__construct($registry);

        // Set the variables
        $this->lang_prefix = $this->module_name .'_';
        $this->setting_prefix = 'payment_' . $this->module_name . '_';
        $this->module_path = 'extension/payment/' . $this->module_name;
        $this->loadLibrary();
        $this->helper = $this->getHelper();
    }

	public function getMethod($address, $total) {
        // Condition check
        $ecpay_geo_zone_id = $this->config->get($this->setting_prefix . 'geo_zone_id');
        $sql = 'SELECT * FROM `' . DB_PREFIX . 'zone_to_geo_zone`';
        $sql .= ' WHERE geo_zone_id = "' . (int)$ecpay_geo_zone_id . '"';
        $sql .= ' AND country_id = "' . (int)$address['country_id'] . '"';
        $sql .= ' AND (zone_id = "' . (int)$address['zone_id'] . '" OR zone_id = "0")';
        $query = $this->db->query($sql);
        unset($sql);

        $status = false;
        if ($total <= 0) {
            $status = false;
        } elseif (!$ecpay_geo_zone_id) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        // Set the payment method parameters
        $this->load->language($this->module_path);
        $method_data = array();
        if ($status === true) {
            $method_data = array(
                'code' => $this->module_name,
                'title' => $this->language->get($this->lang_prefix . 'text_title'),
                'terms' => '',
                'sort_order' => $this->config->get($this->setting_prefix . 'sort_order')
            );
        }
        return $method_data;
    }
	
    // Load the libraries
	public function loadLibrary() {
		foreach ($this->libraryList as $path) {
			include_once($path);
		}
	}

    // Get the helper
    public function getHelper() {
        $merchant_id = $this->config->get($this->setting_prefix . 'merchant_id');
        $helper = new EcpayPaymentHelper();
        $helper->setMerchantId($merchant_id);

        return $helper;
    }

    // Check if AIO responsed
    public function isResponsed($order_id = 0) {
        if (empty($order_id) === true) {
            return false;
        }
        $select_sql = 'SELECT order_id FROM `%s`';
        $select_sql .= ' WHERE order_id = %d';
        $select_sql .= ' LIMIT 1';
        $table = DB_PREFIX . $this->extend_table_name;
        $result = $this->db->query(sprintf(
            $select_sql,
            $table,
            $order_id
        ));

        return ($result->num_rows > 0);
    }

    // Save AIO response
    public function saveResponse($order_id = 0, $feedback = array()) {
        if (empty($order_id) === true) {
            return false;
        }

        $white_list = array('card4no');
        $inputs = $this->helper->only($feedback, $white_list);

        if (empty($inputs['card4no']) === false) {
            $card_4 = $inputs['card4no'];
        } else {
            $card_4 = '';
        }
        $insert_sql = 'INSERT INTO `%s`';
        $insert_sql .= ' (`order_id`, `card_no4`, `response_count`, `createdate`)';
        $insert_sql .= ' VALUES (%d, %d, %d, %d)';
        $table = DB_PREFIX . $this->extend_table_name;
        $response_count = 1;
        $now_time  = time() ;
        return $this->db->query(sprintf(
            $insert_sql,
            $table,
            $order_id,
            $card_4,
            $response_count,
            $now_time
        ));
    }

}
