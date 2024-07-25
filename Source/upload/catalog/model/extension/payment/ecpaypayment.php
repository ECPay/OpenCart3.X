<?php
class ModelExtensionPaymentEcpaypayment extends Model {
    private $module_name = 'ecpaypayment';
    private $lang_prefix = '';
    private $module_path = '';
    private $setting_prefix = '';
	private $helper = null;
    private $extend_table_name = 'order_extend';

    // Constructor
    public function __construct($registry) {
        parent::__construct($registry);

        // Set the variables
        $this->lang_prefix = $this->module_name .'_';
        $this->setting_prefix = 'payment_' . $this->module_name . '_';
        $this->module_path = 'extension/payment/' . $this->module_name;

        $this->load->library('ecpay_payment_helper');
        $this->helper = $this->registry->get('ecpay_payment_helper');
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
            (int)$order_id
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
            (int)$order_id,
            $this->db->escape($card_4) ,
            $response_count,
            $now_time
        ));
    }

    // 新增綠界訂單額外資訊
    public function insertEcpayOrderExtend($order_id, $inputs)
    {
        if (empty($order_id) === true) {
            return false;
        }

        $insert_sql = 'INSERT INTO `%s`';
        $insert_sql .= ' (`order_id`, `goods_weight`, `createdate`)';
        $insert_sql .= " VALUES (%d, %.3f, %d)";
        $table = DB_PREFIX . 'ecpay_order_extend';
        $now_time  = time() ;

        return $this->db->query(sprintf(
            $insert_sql,
            $table,
            (int)$order_id,
            $this->db->escape($inputs['goodsWeight']),
            $now_time )
    	);
    }
}
