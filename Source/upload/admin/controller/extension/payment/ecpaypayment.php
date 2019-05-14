<?php
class ControllerExtensionPaymentEcpaypayment extends Controller {
    
    private $error = array();
    private $module_name = 'ecpaypayment';
    private $module_code = '';
    private $lang_prefix = '';
    private $setting_prefix = '';
    private $name_prefix = '';
    private $id_prefix = '';
    private $module_path = '';
    private $extension_route = 'marketplace/extension';
    private $url_secure = true;
    private $validate_fields = array(
        'merchant_id',
        'hash_key',
        'hash_iv'
    );

    // Constructor
    public function __construct($registry) {
        parent::__construct($registry);

        // Set the variables
        $this->module_code = 'payment_' . $this->module_name;
        $this->lang_prefix = $this->module_name .'_';
        $this->setting_prefix = 'payment_' . $this->module_name . '_';
        $this->name_prefix = 'payment_' . $this->module_name;
        $this->id_prefix = 'payment-' . $this->module_name;
        $this->module_path = 'extension/payment/' . $this->module_name;
    }

    // Back-end config index page
    public function index() {
        // Load the translation file
        $this->load->language($this->module_path);
        
        // Set the title
        $heading_title = $this->language->get('heading_title');
        $this->document->setTitle($heading_title);
        
        // Load the Setting
        $this->load->model('setting/setting');

        // Token
        $token = $this->session->data['user_token'];
        
        // Process the saving setting
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            // Save the setting
            $this->model_setting_setting->editSetting(
                $this->module_code
                , $this->request->post
            );
            
            // Define the success message
            $this->session->data['success'] = $this->language->get($this->lang_prefix . 'text_success');
            
            // Back to the payment list
            $redirect_url = $this->url->link(
                $this->extension_route,
                'user_token=' . $token . '&type=payment',
                $this->url_secure
            );
            $this->response->redirect($redirect_url);
        }
        
        // Get the translations
        $data['heading_title'] = $heading_title;

        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        // Get ECPay translations
        $translation_names = array(
            'text_edit',
            'text_enabled',
            'text_disabled',
            'text_credit',
            'text_credit_3',
            'text_credit_6',
            'text_credit_12',
            'text_credit_18',
            'text_credit_24',
            'text_webatm',
            'text_atm',
            'text_barcode',
            'text_cvs',

            'entry_status',
            'entry_merchant_id',
            'entry_hash_key',
            'entry_hash_iv',
            'entry_payment_methods',
            'entry_create_status',
            'entry_success_status',
            'entry_failed_status',
            'entry_geo_zone',
            'entry_sort_order',
        );
        foreach ($translation_names as $name) {
            $data[$name] = $this->language->get($this->lang_prefix . $name);
        }
        unset($translation_names);

        // Get the errors
        if (isset($this->error['error_warning'])) {
            $data['error_warning'] = $this->error['error_warning'];
        } else {
            $data['error_warning'] = '';
        }

        // Get ECPay errors
        foreach ($this->validate_fields as $name) {
            $error_name = $name . '_error';
            if(isset($this->error[$name])) {
                $data[$error_name] = $this->error[$name];
            } else {
                $data[$error_name] = '';
            }
            unset($field_name, $error_name);
        }
        unset($error_fields);

        // Set the breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $token, $this->url_secure)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get($this->lang_prefix . 'text_extension'),
            'href' => $this->url->link(
                $this->extension_route,
                'user_token=' . $token . '&type=payment',
                $this->url_secure
            )
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/payment/' . $this->module_name,
                'user_token=' . $token,
                $this->url_secure
            )
        );

        // Set the form action
        $data['action'] = $this->url->link(
            $this->module_path,
            'user_token=' . $token,
            $this->url_secure
        );
        
        // Set the cancel button
        $data['cancel'] = $this->url->link(
            $this->extension_route,
            'user_token=' . $token,
            $this->url_secure
        );
        
        // Get ECPay options
        $options = array(
            'status',
            'merchant_id',
            'hash_key',
            'hash_iv',
            'payment_methods',
            'create_status',
            'success_status',
            'failed_status',
            'geo_zone_id',
            'sort_order'
        );
        foreach ($options as $name) {
            $option_name = $this->setting_prefix . $name;
            if (isset($this->request->post[$option_name])) {
                $data[$name] = $this->request->post[$option_name];
            } else {
                $data[$name] = $this->config->get($option_name);
            }
            unset($option_name);
        }
        unset($options);
        
        // Default value
        $default_values = array(
            'merchant_id' => '2000132',
            'hash_key' => '5294y06JbISpM5x9',
            'hash_iv' => 'v77hoKGq4kWxNNIS',
            'create_status' => 1,
            'success_status' => 15,
        );
        foreach ($default_values as $name => $value) {
            if (is_null($data[$name])) {
                $data[$name] = $value;
            }
        }
        
        // Set module status
        $data['module_statuses'] = array(
            array(
                'value' => '1',
                'text' => $this->language->get($this->lang_prefix . 'text_enabled')
            ),
            array(
                'value' => '0',
                'text' => $this->language->get($this->lang_prefix . 'text_disabled')
            )
        );
        
        // Get the order statuses
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        // Get the geo zones
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        
        // View's setting
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['name_prefix'] = $this->name_prefix;
        $data['id_prefix'] = $this->id_prefix;

        $view_path = $this->module_path;
        $this->response->setOutput($this->load->view($view_path, $data));
    }

    protected function validate() {
        // Premission validate
        if (!$this->user->hasPermission('modify', $this->module_path)) {
            $this->error['error_warning'] = $this->language->get($this->lang_prefix . 'error_permission');
        }
        
        // Required fields validate
        foreach ($this->validate_fields as $name) {
            $field_name = $this->setting_prefix . $name;
            if (empty($this->request->post[$field_name])) {
                $this->error[$name] = $this->language->get($this->lang_prefix . 'error_' . $name);
            }
            unset($field_name);
        }
        
        return !$this->error; 
    }

    // install
    public function install() {
        
        // card_no4 記錄信用卡後四碼提供電子發票開立使用
        // response_count AIO 回應次數

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_extend` (
              `order_id` INT(11) DEFAULT '0' NOT NULL,
              `card_no4` INT(4) DEFAULT '0' NOT NULL,
              `response_count` TINYINT(1) DEFAULT '0' NOT NULL,
              `createdate` INT(10) DEFAULT '0' NOT NULL
            ) DEFAULT COLLATE=utf8_general_ci;");
    }

    // uninstall
    public function uninstall() {
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');

        $this->model_setting_setting->deleteSetting($this->request->get['extension']);
        $this->model_setting_extension->uninstall($this->module_code, $this->request->get['extension']);

        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "order_extend`;");
    }
}
