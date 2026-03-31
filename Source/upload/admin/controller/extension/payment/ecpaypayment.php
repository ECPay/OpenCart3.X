<?php
class ControllerExtensionPaymentEcpaypayment extends Controller {

    private $error = array();
    private $module_name = 'ecpaypayment';
    private $module_code = '';
    private $model_name  = '';
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
        $this->id_prefix   = 'payment-' . $this->module_name;
        $this->module_path = 'extension/payment/' . $this->module_name;
        $this->model_name  = 'model_extension_payment_' . $this->module_name;
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
            'text_weixin',
            'text_twqr',
            'text_bnpl',
            'text_applepay',
            'text_dca',
            'text_jkopay',

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
            'entry_dca_period_type',
            'entry_dca_frequency',
            'entry_dca_exec_times',
            'entry_test_mode',
            'entry_test_mode_info',
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

        if (isset($this->error['ecpay_setting_error'])) {
            $data['ecpay_setting_error'] = $this->error['ecpay_setting_error'];
        } else {
            $data['ecpay_setting_error'] = [];
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
            'test_mode',
            'payment_methods',
            'create_status',
            'success_status',
            'failed_status',
            'geo_zone_id',
            'sort_order',
            'dca_period_type',
            'dca_frequency',
            'dca_exec_times',
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
            'status' => '0',
            'merchant_id' => '3002607',
            'hash_key' => 'pwFHCqoQZGmho4w6',
            'hash_iv' => 'EkRm7iFT261dpevs',
            'test_mode' => 1,
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

        $data['test_modes'] = array();
		$data['test_modes'][] = array(
			'value' => '0',
			'text' => $this->language->get($this->lang_prefix . 'text_disabled')
		);
		$data['test_modes'][] = array(
			'value' => '1',
			'text' => $this->language->get($this->lang_prefix . 'text_enabled')
		);

        // DCA options
        $data['dca_period_types'] = ['Y', 'M', 'D'];

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
                $this->error['ecpay_setting_error'][$name] = $this->language->get($this->lang_prefix . 'error_' . $name);
            }
            unset($field_name);
        }

        // 定期定額欄位驗證
        $dca_frequency = $this->request->post[$this->setting_prefix . 'dca_frequency'];
        $dca_exec_times = $this->request->post[$this->setting_prefix . 'dca_exec_times'];
        if ($dca_frequency != '' && $dca_exec_times != '') {
            switch ($this->request->post[$this->setting_prefix . 'dca_period_type']) {
                case 'Y':
                    if ($dca_frequency != '1') {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-frequency'] = $this->language->get($this->lang_prefix . 'error_dca_frequency_y');
                    }
                    if ($dca_exec_times < 2 || $dca_exec_times > 99) {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-exec-times'] = $this->language->get($this->lang_prefix . 'error_dca_exec_times_y');
                    }
                    break;
                case 'M':
                    if ($dca_frequency < 1 || $dca_frequency > 12) {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-frequency'] = $this->language->get($this->lang_prefix . 'error_dca_frequency_m');
                    }
                    if ($dca_exec_times < 2 || $dca_exec_times > 999) {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-exec-times'] = $this->language->get($this->lang_prefix . 'error_dca_exec_times_m');
                    }
                    break;
                case 'D':
                    if ($dca_frequency < 1 || $dca_frequency > 365) {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-frequency'] = $this->language->get($this->lang_prefix . 'error_dca_frequency_d');
                    }
                    if ($dca_exec_times < 2 || $dca_exec_times > 999) {
                        $this->error['ecpay_setting_error'][$this->id_prefix . '-dca-exec-times'] = $this->language->get($this->lang_prefix . 'error_dca_exec_times_d');
                    }
                    break;
            }
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

		// 記錄訂單額外資訊(送往AIO前)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ecpay_order_extend` (
                `order_id` INT(11) DEFAULT '0' NOT NULL,
                `goods_weight` DECIMAL(15,3) NOT NULL DEFAULT '0.000',
                `createdate` INT(10) DEFAULT '0' NULL
            ) DEFAULT COLLATE=utf8_general_ci;"
        );

        // 紀錄綠界付款資訊
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ecpay_payment_response_info` (
            `id`                     INT(11)      NOT NULL AUTO_INCREMENT,
            `order_id`              INT(11)      NOT NULL,
            `payment_method`        VARCHAR(60)  NOT NULL,
            `merchant_trade_no`     VARCHAR(60)  NOT NULL DEFAULT '',
            `payment_status`        INT(10)      NOT NULL DEFAULT 0,
            `is_completed_duplicate` INT(1)       NOT NULL DEFAULT 0,
            `MerchantID`           VARCHAR(10)   NULL,
            `MerchantTradeNo`      VARCHAR(20)   NULL,
            `StoreID`              VARCHAR(20)   NULL,
            `RtnCode`              INT(10)       NULL,
            `RtnMsg`               VARCHAR(200)  NULL,
            `TradeNo`              VARCHAR(20)   NULL,
            `TradeAmt`             INT(10)       NULL,
            `PaymentDate`          VARCHAR(20)   NULL,
            `PaymentType`          VARCHAR(20)   NULL,
            `PaymentTypeChargeFee` INT(10)       NULL,
            `PlatformID`           VARCHAR(20)   NULL,
            `TradeDate`            VARCHAR(20)   NULL,
            `SimulatePaid`         INT(1)        NULL,
            `CustomField1`         VARCHAR(50)   NULL,
            `CustomField2`         VARCHAR(50)   NULL,
            `CustomField3`         VARCHAR(50)   NULL,
            `CustomField4`         VARCHAR(50)   NULL,
            `CheckMacValue`        VARCHAR(200)  NULL,
            `eci`                  INT(10)       NULL,
            `card4no`              VARCHAR(4)    NULL,
            `card6no`              VARCHAR(6)    NULL,
            `process_date`         VARCHAR(20)   NULL,
            `auth_code`            VARCHAR(6)    NULL,
            `stage`                INT(10)       NULL,
            `stast`                INT(10)       NULL,
            `red_dan`              INT(10)       NULL,
            `red_de_amt`           INT(10)       NULL,
            `red_ok_amt`           INT(10)       NULL,
            `red_yet`              INT(10)       NULL,
            `gwsr`                 INT(10)       NULL,
            `PeriodType`           VARCHAR(1)    NULL,
            `Frequency`            INT(10)       NULL,
            `ExecTimes`            INT(10)       NULL,
            `amount`               INT(10)       NULL,
            `ProcessDate`          VARCHAR(20)   NULL,
            `AuthCode`             VARCHAR(6)    NULL,
            `FirstAuthAmount`      INT(10)       NULL,
            `TotalSuccessTimes`    INT(10)       NULL,
            `BankCode`             VARCHAR(3)    NULL,
            `vAccount`             VARCHAR(16)   NULL,
            `ATMAccNo`             VARCHAR(5)    NULL,
            `ATMAccBank`           VARCHAR(3)    NULL,
            `WebATMBankName`       VARCHAR(10)   NULL,
            `WebATMAccNo`          VARCHAR(5)    NULL,
            `WebATMAccBank`        VARCHAR(3)    NULL,
            `PaymentNo`            VARCHAR(14)   NULL,
            `ExpireDate`           VARCHAR(20)   NULL,
            `Barcode1`             VARCHAR(20)   NULL,
            `Barcode2`             VARCHAR(20)   NULL,
            `Barcode3`             VARCHAR(20)   NULL,
            `BNPLTradeNo`          VARCHAR(64)   NULL,
            `BNPLInstallment`      VARCHAR(2)    NULL,
            `TWQRTradeNo`          VARCHAR(64)   NULL,
            `response_count`       TINYINT(1)    NOT NULL DEFAULT '0',
            `updated_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
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
