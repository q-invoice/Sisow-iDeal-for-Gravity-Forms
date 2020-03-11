<?php

GFForms::include_payment_addon_framework();

if (method_exists('GFForms', 'include_payment_addon_framework')) {

    class QinvoiceSisow extends GFPaymentAddOn
    {
        protected $_version = "0.0.1";
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = 'qinvoice-sisow-ideal-for-gravity-forms';
        protected $_path = 'qinvoice-sisow-ideal-for-gravity-forms/';
        protected $_full_path = __FILE__;
        protected $_title = 'Gravity Forms Sisow iDeal Add-On by q-invoice';
        protected $_short_title = 'Sisow by q-invoice';
        protected $_supports_callbacks = true;
        protected $_requires_credit_card = false;

        const CALLBACK_PAGE = 'gravity_forms_sisow_qinvoice';

        /**
         * @var object|null $_instance If available, contains an instance of this class.
         */
        private static $_instance = null;

        /**
         * Returns an instance of this class, and stores it in the $_instance property.
         *
         * @return object $_instance An instance of this class.
         */
        public static function get_instance()
        {
            if (self::$_instance == null) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
         *
         * @return array
         */
        public function plugin_settings_fields()
        {
            return array(
                array(
                    'title' => __('Sisow settings', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'fields' => array(
                        array(
                            'type' => 'helper_text',
                            'name' => 'help',
                            'label' => '',
                        ),
                        array(
                            'label' => __('Sisow Merchant ID', 'qinvoice-sisow-ideal-for-gravity-forms'),
                            'type' => 'text',
                            'name' => 'merchant_id',
                            'tooltip' => __('You can obtain your Merchant ID from your Sisow dashboard', 'qinvoice-sisow-ideal-for-gravity-forms'),
                            'class' => 'medium',
                            'feedback_callback' => array($this, 'is_valid_merchant_id'),
                        ),
                        array(
                            'label' => __('Sisow Merchant Key', 'qinvoice-sisow-ideal-for-gravity-forms'),
                            'type' => 'text',
                            'name' => 'merchant_key',
                            'tooltip' => __('You can obtain your Merchant Key from your Sisow dashboard', 'qinvoice-sisow-ideal-for-gravity-forms'),
                            'class' => 'medium',
                            'feedback_callback' => array($this, 'is_valid_merchant_key'),
                        ),
                        array(
                            'label' => __('Test mode', 'qinvoice-sisow-ideal-for-gravity-forms'),
                            'type' => 'checkbox',
                            'name' => 'test_mode',
                            'choices' => array(
                                array(
                                    'label' => esc_html__('Enabled', 'simpleaddon'),
                                    'name' => 'test_mode_enabled',
                                    'value' => 1,
                                ),
                            ),
                            'description' => sprintf(__('%sAttention%s: This setting is system wide and cannot be overriden for individual feeds!'), '<strong>', '</strong>'),
                        ),


                    ),
                ),
            );
        }

        public static function maybe_show_admin_notice_test_mode()
        {
            $q = QinvoiceSisow::get_instance();
            if ($q->get_plugin_setting('test_mode_enabled') == 1) {
                $class = 'notice notice-error';
                $intro = __("Test mode enabled!");
                $message = __('Test mode is enabled for Sisow payment through Gravity Forms. Do not forget to disable test mode after testing.', 'sample-text-domain');
                printf('<div class="%1$s"><p><strong>%2$s</strong> %3$s</p></div>', esc_attr($class), esc_html($intro), esc_html($message));
            } else {
                return;
            }
        }

        public function get_entry_meta($entry_meta, $form_id)
        {
            $entry_meta['payment_status'] = array(
                'label' => 'Payment status',
                'is_numeric' => false,
                'is_default_column' => true,
                // 'update_entry_meta_callback' => array( $this, 'update_entry_meta' ),
                'filter' => array(
                    'operators' => array('is', 'isnot'),
                ),
            );
            $entry_meta['transaction_id'] = array(
                'label' => 'Transaction ID',
                'is_numeric' => false,
                'is_default_column' => true,
                // 'update_entry_meta_callback' => array( $this, 'update_entry_meta' ),
                'filter' => array(
                    'operators' => array('is', 'isnot'),
                ),
            );
            return $entry_meta;
        }

        public function settings_helper_text()
        {
            printf(__('No Sisow account? %sClick here to create one%s.', 'qinvoice-sisow-ideal-for-gravity-forms'), '<a target="blank" href="https://www.sisow.nl/aanmelden/?r=309206">', '</a>');
        }

        public function feed_settings_fields()
        {

            $feed_settings_fields = parent::feed_settings_fields();

            $fields = array(
                array(
                    'type' => 'helper_text',
                    'name' => 'help',
                    'label' => '',
                ),
                array(
                    'label' => 'Merchant settings',
                    'type' => 'select',
                    'name' => 'override',
                    'tooltip' => __('Override settings for this feed alone', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'choices' => array(
                        array(
                            'label' => 'Pick one',
                            'value' => '',
                        ),
                        array(
                            'label' => 'Use default settings',
                            'value' => 'default',
                        ),
                        array(
                            'label' => 'Specify a merchant id and key for this feed',
                            'value' => 'override',
                        ),

                    ),
                    'onchange' => 'jQuery(this).parents("form").submit();',
                ),
                array(
                    'label' => __('Sisow Merchant ID', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'type' => 'text',
                    'name' => 'merchant_id',
                    'tooltip' => __('You can obtain your Merchant ID from your Sisow dashboard', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'class' => 'medium',
                    'feedback_callback' => array($this, 'is_valid_merchant_id'),
                    'dependency' => array('field' => 'override', 'values' => array('override')),

                ),

                array(
                    'label' => __('Sisow Merchant Key', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'type' => 'text',
                    'name' => 'merchant_key',
                    'tooltip' => __('You can obtain your Merchant Key from your Sisow dashboard', 'qinvoice-sisow-ideal-for-gravity-forms'),
                    'class' => 'medium',
                    'feedback_callback' => array($this, 'is_valid_merchant_key'),
                    'dependency' => array('field' => 'override', 'values' => array('override')),

                ),


                array(
                    'label' => 'Payment method',
                    'type' => 'select',
                    'name' => 'payment_method',
                    'choices' => array(
                        array(
                            'label' => 'iDeal',
                            'value' => 'ideal',
                        ),
                        array(
                            'label' => 'Credit card',
                            'value' => 'creditcard',
                        ),
                        array(
                            'label' => 'DIRECTebanking',
                            'value' => 'directebanking',
                        ),
                        array(
                            'label' => 'MisterCash',
                            'value' => 'mistercash',
                        ),

                    ),
                    'onchange' => 'jQuery(this).parents("form").submit();',
                ),

            );

            $feed_settings_fields = parent::add_field_after('feedName', $fields, $feed_settings_fields);

            // Override transaction type
            $feed_settings_fields = $this->replace_field('transactionType',
                array(
                    array(
                        'type' => 'hidden',
                        'name' => 'transactionType',
                        'value' => 'product',
                    ),
                ), $feed_settings_fields);

            // Remove billing information
            $feed_settings_fields = $this->replace_field('billingInformation', array(), $feed_settings_fields);

            return $feed_settings_fields;

        }

        public function option_choices()
        {
            return false;
        }

        public function redirect_url($feed, $submission_data, $form, $entry)
        {
            $credentials = $this->get_credentials($feed);
            if (!$credentials['merchant_id'] || !$credentials['merchant_key']) {
                $this->log_debug(__METHOD__ . '(): Merchant credentials are incomplete!');
                return '';
            }


            try {
                $sisow = new SisowApi($credentials['merchant_id'], $credentials['merchant_key']);
            } catch (Exception $e) {
                $this->log_debug(__METHOD__ . '(): Failed to instantiate API. Using key: .' . $credentials['merchant_key'] . '. Error: ' . htmlspecialchars($e->getMessage()));
                return false;
            }

            // build variables for later use
            $return_url = $this->return_url($form['id'], $entry['id']);
            $webhook_url = $this->webhook_url($form['id'], $entry['id']);
            $payment_amount = rgar($submission_data, 'payment_amount');
            $order_id = '';

            $payment_data = array(
                "amount" => $payment_amount * 100,
                "description" => $feed['meta']['feedName'],
                "redirectUrl" => $return_url,
                "webhookUrl" => $webhook_url,
                "orderId" => $entry['id'] .'x'. Date('Hmi')
            );

            $this->log_debug(__METHOD__ . '(): Payment data: .' . print_r($payment_data, true));

            try {

                $sisow->payment = $feed['meta']['payment_method'];
                $sisow->purchaseId = $payment_data['orderId'];
                $sisow->description = $payment_data['description'];
                $sisow->amount = $payment_data['amount'];
                $sisow->notifyUrl = $payment_data['webhookUrl'];
                $sisow->returnUrl = $payment_data['redirectUrl'];
                $sisow->cancelUrl = $payment_data['redirectUrl'];
                if($this->get_plugin_setting('test_mode_enabled') == 1) {
                    $this->log_debug(__METHOD__ . '(): Test mode ENABLED');
                    $sisow->setTestmode();
                }

                $this->log_debug(__METHOD__.' (): Reponse: '. print_r($sisow->TransactionRequest(), true));

            } catch (Exception $e) {
                $this->log_debug(__METHOD__ . '(): Failed to start payment. Error: .' . htmlspecialchars($e->getMessage()));
                return false;
            }

            // everything ok. update properties
            GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');
            GFAPI::update_entry_property($entry['id'], 'transaction_id', $sisow->trxId);
            gform_update_meta($entry['id'], 'payment_amount', $payment_amount);

            $payment_url = $sisow->issuerUrl;

            $this->log_debug(__METHOD__ . '(): Payment started. Redirecting to ' . $payment_url);
            return $payment_url;
        }

        private function return_url($form_id, $entry_id)
        {
            $url = (GFCommon::is_ssl() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            return add_query_arg('sisow_qinvoice_return', base64_encode($this->query_params($form_id, $entry_id)), $url);
        }

        private function webhook_url($form_id, $entry_id)
        {
            return (GFCommon::is_ssl() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']) . '?page=' . self::CALLBACK_PAGE . '&' . $this->query_params($form_id, $entry_id);
        }

        private function query_params($form_id, $entry_id)
        {
            return 'fid=' . $form_id . '&eid=' . $entry_id . '&hash=' . $this->get_hash($form_id, $entry_id);
        }

        private function get_hash($form_id, $entry_id)
        {
            return wp_hash($form_id, $entry_id);
        }

        private function check_hash($form_id, $entry_id, $hash)
        {
            return $hash == $this->get_hash($form_id, $entry_id);
        }

        public function is_callback_valid()
        {
            if (rgget('page') !== self::CALLBACK_PAGE) {
                return false;
            }
            return true;
        }

        public function callback()
        {

            if (!$this->is_gravityforms_supported()) {
                return false;
            }

            $this->log_debug(__METHOD__ . '(): called.');
            $this->log_debug( __METHOD__ . '(): request received. Starting to process => ' . print_r( $_POST, true ) );

            $this->log_debug( __METHOD__ . '(): request received. Starting to process GET=> ' . print_r( $_GET, true ) );

            // Check the hash
            if (!$this->check_hash(rgget('fid'), rgget('eid'), rgget('hash'))) {
                $this->log_debug(__METHOD__ . '(): Incorrect hash!');
                return false;
            }

            if (empty(rgget('trxid'))) {
                $this->log_error(__METHOD__ . '(): "trxid" is missing from request');
                return false;
            }

            //	Get the entry
            $entry_id = rgget('eid');
            $entry = GFAPI::get_entry($entry_id);

            $feed = $this->get_payment_feed($entry);

            $credentials = $this->get_credentials($feed);
            if (!$credentials) {
                $this->log_debug(__METHOD__ . '(): API key is missing.');
                return '';
            }

            try {
                $sisow = new SisowApi($credentials['merchant_id'], $credentials['merchant_key']);
            } catch (Exception $e) {
                $this->log_debug(__METHOD__ . '(): Failed to instantiate API. Using key: .' . $credentials['merchant_key'] . '. Error: ' . htmlspecialchars($e->getMessage()));
                return false;
            }

            try {
                $sisow->StatusRequest(rgget('trxid'));
            } catch (Exception $e) {
                $this->log_debug(__METHOD__ . '(): Failed to get payment: ' . htmlspecialchars($e->getMessage()));
            }


            if (strtolower($sisow->status) == 'success') {

                $this->log_debug(__METHOD__ . '(): Sisow status paid');

                $action['type'] = 'complete_payment';
                $action['transaction_id'] = $sisow->trxId;
                $action['amount'] = $sisow->amount;
                $action['entry_id'] = $entry['id'];
                $action['payment_date'] = gmdate('y-m-d H:i:s');
                $action['payment_method'] = 'Sisow ('. $feed['meta']['payment_method'] .')';
                $action['note'] = '';
                return $action;


            } else {
                /*
                 * The payment isn't paid and
                 */
                $this->log_debug(__METHOD__ . '(): Sisow status not paid');

                $action['type'] = 'fail_payment';
                $action['transaction_id'] = $sisow->trxId;
                $action['entry_id'] = $entry['id'];
                $action['amount'] = $sisow->amount;
                $action['note'] = '';
                return $action;
            }

            return false;

        }

        public static function handle_confirmation($callback)
        {
            $instance = self::get_instance();

            if (!$instance->is_gravityforms_supported()) {
                return;
            }

            if (rgget('sisow_qinvoice_return')) {
                parse_str(base64_decode(rgget('sisow_qinvoice_return')), $query);

                if (!$instance->check_hash($query['fid'], $query['eid'], $query['hash'])) {
                    return;
                }

                $form = GFAPI::get_form($query['fid']);
                $entry = GFAPI::get_entry($query['eid']);

                if (!class_exists('GFFormDisplay')) {
                    require_once(GFCommon::get_base_path() . '/form_display.php');
                }

                $confirmation = GFFormDisplay::handle_confirmation($form, $entry, false);

                if (is_array($confirmation) && isset($confirmation['redirect'])) {
                    header("Location: {$confirmation['redirect']}");
                    exit;
                }

                GFFormDisplay::$submission[$form['id']] = array('is_confirmation' => true, 'confirmation_message' => $confirmation, 'form' => $form, 'lead' => $entry);
            }
        }

        public function is_valid_merchant_key($value)
        {
            return strlen($value) == 40;
        }

        public function is_valid_merchant_id($value)
        {
            return is_int($value) && strlen($value) > 5;
        }

        private function get_credentials($feed)
        {
            if (isset($feed['meta']['override']) && $feed['meta']['override'] == 1) {
                return array(
                    'merchant_id' => $feed['meta']['merchant_id'],
                    'merchant_key' => $feed['meta']['merchant_key'],
                );
            } else {
                return array(
                    'merchant_id' => $this->get_plugin_setting('merchant_id'),
                    'merchant_key' => $this->get_plugin_setting('merchant_key'),
                );
            }
        }

        public function minimum_requirements()
        {
            array(
                // Require WordPress version 4.6.2 or higher.
                'wordpress' => array(
                    'version' => '4.6.2',
                ),

                // Require PHP version 5.3 or higher.
                'php' => array(
                    'version' => '5.3',

                    // Require specific PHP extensions.
                    'extensions' => array(

                        // Require cURL version 1.0 or higher.
                        'curl' => array(
                            'version' => '1.0',
                        ),

                        // Require any version of mbstring.
                        'mbstring',
                    ),

                    // Require specific functions to be available.
                    'functions' => array(
                        'openssl_random_pseudo_bytes',
                        'mcrypt_create_iv',
                    ),
                ),

                // Require other add-ons to be present.
                'add-ons' => array(

                    // Require any version of the Mailchimp add-on.
                    'gravityformsmailchimp',

                    // Require the Stripe add-on and ensure the name matches.
                    'gravityformsstripe' => array(
                        'name' => 'Gravity Forms Stripe Add-On',
                    ),

                    // Require the PayPal add-on version 5.0 or higher.
                    'gravityformspaypal' => array(
                        'version' => '5.0',
                    ),
                ),

                // Required plugins.
                'plugins' => array(

                    // Require the REST API.
                    'rest-api/plugin.php',

                    // Require Jetpack and ensure the name matches.
                    'jetpack/jetpack.php' => 'Jetpack by WordPress.com',
                ),

                // Any additional custom requirements via callbacks.
                array($this, 'custom_requirement'),
            );
        }
    }
}