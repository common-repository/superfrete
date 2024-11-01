<?php
/**
 * SuperfreteShipping Class File
 *
 * @package SuperfreteShipping
 */
if (!defined('ABSPATH')) {
    exit; /* Exit if accessed directly */
}

if (!isset($_SESSION)) {
    //session_start();
}

if (!class_exists('SuperfreteShipping')) {

    include_once ABSPATH . 'wp-includes/pluggable.php';

    /**
     * Class SuperfreteShipping
     */
    class SuperfreteShipping {

        /**
         * Superfrete settings
         *
         * @var array
         */
        private $superfrete_settings;

        /**
         * Plugin URL
         *
         * @var string
         */
        private static $plugin_url;

        /**
         * Plugin Directory
         *
         * @var string
         */
        private static $plugin_dir;

        /**
         * Plugin Title
         *
         * @var string
         */
        private static $plugin_title = 'SuperFrete';
        
        
          /**
         * Input Post
         *
         * @var string
         */
        private static $input_post;

        /**
         * Plugin Slug
         *
         * @var string
         */
        private static $plugin_slug = 'superfrete';

        /**
         * Superfrete Option Key
         *
         * @var string
         */
        private static $superfrete_option_key = 'superfrete-calculator-setting';

        /**
         * Calculator Meta Key
         *
         * @var string
         */
        public static $calculator_metakey = '__calculator_hide';
        
          private $superfrete_api_url;
    private $superfrete_api_sandbox_url;

        /**
         * SuperfreteShipping constructor.
         */
        public function __construct() {
            global $superfrete_plugin_dir, $superfrete_plugin_url;

            self::$input_post =  filter_input_array(INPUT_POST);
            self::$plugin_dir = $superfrete_plugin_dir;
            self::$plugin_url = $superfrete_plugin_url;

            $this->superfrete_settings = get_option(self::$superfrete_option_key);   
             
             if (!isset($this->superfrete_settings['superfrete_sandbox_enabled']) || !$this->superfrete_settings['superfrete_sandbox_enabled']) {
                $this->superfrete_api_url = 'https://api.superfrete.com';
                $this->superfrete_api_sandbox_url = $this->superfrete_api_url;

                if (!empty($this->superfrete_settings['superfrete_token_production'])) {
                    $this->superfrete_settings['superfrete_token'] = $this->superfrete_settings['superfrete_token_production'];
                }
            }

            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('wp_ajax_nopriv_update_shipping_method', array($this, 'superfrete_update_shipping_method'));
            add_action('wp_ajax_update_shipping_method', array($this, 'superfrete_update_shipping_method'));
            add_action('wp_footer', array($this, 'wp_footer'));
            add_action('wp_head', array($this, 'wp_head'));
            add_action('admin_enqueue_scripts', array($this, 'superfrete_admin_script'));
            add_shortcode('shipping-calculator', array($this, 'superfrete_srt_shipping_calculator'));
            add_action('woocommerce_product_options_general_product_data', array($this, 'superfrete_custom_price_box_include'));
            add_action('woocommerce_process_product_meta', array($this, 'superfrete_custom_woocommerce_process_product_meta'), 2);

            add_action('woocommerce_single_product_summary', array(&$this, 'superfrete_display_shipping_calculator'), 8);
            add_action('woocommerce_product_bulk_edit_save', array($this, 'superfrete_save_bulk_shipping_fields'));
            add_action('manage_product_posts_custom_column', array($this, 'superfrete_output_quick_shipping_values'));
            add_action('woocommerce_product_quick_edit_end', array($this, 'superfrete_output_quick_shipping_fields'));
            add_action('woocommerce_product_quick_edit_save', array($this, 'superfrete_save_quick_shipping_fields'));
            add_action('woocommerce_calculated_shipping', array($this, 'superfrete_apply_fields_on_shipping'));

            add_action('template_redirect', array($this, 'superfrete_rp_callback'));
            // SuperFrete Enabled!
            add_action('woocommerce_cart_totals_after_shipping', array($this, 'superfrete_get_shippings_to_cart'));
            add_action('woocommerce_review_order_before_order_total', array($this, 'superfrete_get_shippings_to_cart'));

            add_action('add_meta_boxes', array($this, 'superfrete_register_metabox'), 10, 2);
            add_action('woocommerce_thankyou', array($this, 'superfrete_payment_complete'));
            add_filter('wp_ajax_cotation_product_page', '__return_false');

            add_filter(
                    'woocommerce_shipping_method_add_rate_args',
                    function ($args) {
                        if ($args['package'] && !isset($args['package']['contents'])) {
                            $args['package']['contents'] = array();
                        }
                        return $args;
                    }
            );

            if (!defined('SUPERFRETE_VERSION')) {
                define('SUPERFRETE_VERSION', '1.0.0'); /* substitua '1.0.0' pela versão atual do seu plugin ou tema */
            }

            /* GET IMPORTANT CONFIG */
           

            if (isset($this->superfrete_settings['superfrete_sandbox_enabled']) && $this->superfrete_settings['superfrete_sandbox_enabled']) {

                if (!empty($this->superfrete_settings['superfrete_token_sandbox'])) {
                    $this->superfrete_settings['superfrete_token'] = $this->superfrete_settings['superfrete_token_sandbox'];
                }

                $superfrete_plugin_dir = __DIR__ . DIRECTORY_SEPARATOR;
                $this->superfrete_api_url = 'https://sandbox.superfrete.com';
                $this->superfrete_api_sandbox_url = $this->superfrete_api_url;
            }

            if (( isset(  self::$input_post['nonce_field']) && wp_verify_nonce(  self::$input_post['nonce_field'], 'validateOnce'))) {
                if (isset($_GET['save_settings'])) {
                    $this->save_setting();
                    exit;
                }
            }
            if (isset($_GET['validate_token']) && isset($_GET['token']) && isset($_GET['environment'])) {
                $token = sanitize_text_field(wp_unslash($_GET['token']));
                $environment = sanitize_text_field(wp_unslash($_GET['environment']));
                $this->superfrete_validate_token($token, $environment);
                exit;
            }
            if (isset($_GET['get_user_info'])) {
                $this->get_superfrete_user_info();
                exit;
            }
            if (isset($_GET['get_addresses'])) {
                $this->get_superfrete_addresses();
                exit;
            }
            if (isset($_GET['get_user_app_info'])) {

                $this->get_superfrete_user_app_info();
                exit;
            }
            if (isset($_GET['resend_order'])) {
                $resend_order = sanitize_text_field(wp_unslash($_GET['resend_order']));
                $this->superfrete_reenviar_pedido($resend_order);
                exit;
            }
            if (isset($_GET['verify_order_print_url'])) {
                $verify_order_print_url = sanitize_text_field(wp_unslash($_GET['verify_order_print_url']));
                $this->superfrete_verificar_etiqueta($verify_order_print_url);
                exit;
            }
        }

        /**
         * Callback function to reset shipping and set customer location.
         */
        public function superfrete_rp_callback() {
            if (is_cart() || is_product()) {
                WC()->shipping->reset_shipping();
                WC()->customer->set_location('BR', '', '', '');
                WC()->customer->save();
            }
        }

        /**
         * Get products by order data.
         *
         * @param array $items Order items.
         * @return array List of products with their details.
         */
        public function get_products_by_order_data($items) {
            $products = array();
            foreach ($items as $item_product) {
                $product_id = ( 0 !== $item_product['variation_id'] ) ? $item_product['variation_id'] : $item_product['product_id'];
                $product_info = wc_get_product($product_id);
                if (empty($product_info)) {
                    continue;
                }
                $data = $product_info->get_data();
                $products[] = array(
                    'id' => $item_product['product_id'],
                    'variation_id' => $item_product['variation_id'],
                    'name' => $data['name'],
                    'unitary_value' => $product_info->get_price(),
                    'insurance_value' => $product_info->get_price(),
                    'height' => $product_info->get_height(),
                    'width' => $product_info->get_width(),
                    'length' => $product_info->get_length(),
                    'weight' => $product_info->get_weight(),
                    'quantity' => ( isset($item_product['quantity']) ) ? intval($item_product['quantity']) : 1,
                );
            }
            return $products;
        }

        /**
         * Calculate quotation for shipping.
         *
         * @param array $payload_quotation The payload data for the quotation.
         * @return array|bool The list of quotations or false on failure.
         */
        public function calculate_quotation($payload_quotation) {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];
            $superfrete_api_path_calculator = '/api/v0/calculator';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path_calculator = '/apiIntegrationV1Calculator/api/v0/calculator';
            }
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'POST',
                'body' => wp_json_encode($payload_quotation),
                'timeout ' => 600,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path_calculator, $params);

            $quotations = array();
            if (200 === $result['response']['code']) {
                $fretes = json_decode($result['body'], true);
                if ($fretes && is_array($fretes)) {

                    if (( isset($fretes[1]['id']) && 17 === $fretes[1]['id'] ) || ( isset($fretes[2]['id']) && 17 === $fretes[2]['id'] )) {
                        $frete_arr_bkp = $fretes;
                        if (17 === $fretes[2]['id']) {
                            $fretes[0] = $fretes[2];
                            $fretes[1] = $frete_arr_bkp[0];
                            $fretes[2] = $frete_arr_bkp[1];
                        } elseif (17 === $fretes[1]['id']) {
                            $fretes[0] = $fretes[1];
                            $fretes[1] = $frete_arr_bkp[0];
                        }
                    }

                    foreach ($fretes as $frete) {
                        if ($frete['has_error']) {
                            continue;
                        }
                        $quotations[] = array(
                            'id' => $frete['id'],
                            'method_id' => $frete['id'],
                            'label' => 'SuperFrete ' . $frete['name'],
                            'cost' => 'SuperFrete ' . $frete['price'],
                            'taxes' => '',
                            'calc_tax' => 'per_order',
                            'meta_data' => array(),
                            'package' => $frete['packages'][0],
                        );
                    }
                } else {
                    return false;
                }
                return $quotations;
            }
        }

        /**
         * Get the volumes from the quotation data.
         *
         * @param array $quotation The quotation data.
         * @return array|bool The volume data or false on failure.
         */
        public function get_volumes($quotation) {
            $volumes = array();
            $quotation = $quotation[0];
            if (!isset($quotation['id'])) {
                return false;
            }
            $volumes[0] = array(
                'height' => $quotation['package']['dimensions']['height'],
                'width' => $quotation['package']['dimensions']['width'],
                'length' => $quotation['package']['dimensions']['length'],
                'weight' => $quotation['package']['weight'],
            );

            return $volumes[0];
        }

        /**
         * Calculate the insurance value for an order.
         *
         * @param int $order_id The ID of the order.
         * @return float The total insurance value rounded to 2 decimal places.
         */
        public function get_insurance_value($order_id) {    
            $order = wc_get_order($order_id);
            $total = 0;

            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $total = $total + ( $product->get_price() * $item->get_quantity() );
            }

            return round($total, 2);
        }

        /**
         * Get the chosen shipping method for an order.
         *
         * @param array $order_shipping_lines The shipping lines of the order.
         * @return string The ID of the chosen shipping method.
         */
        public function get_chosen_method($order_shipping_lines) {
            foreach ($order_shipping_lines as $order_shipping_line) {
                $chosen_method = "";
                if (strstr(strtolower($order_shipping_line->get_name()), 'pac')) {
                    $chosen_method = '1';
                }
                if (strstr(strtolower($order_shipping_line->get_name()), 'sedex')) {
                    $chosen_method = '2';
                }
                if (strstr(strtolower($order_shipping_line->get_name()), 'mini')) {
                    $chosen_method = '17';
                }
            }
            return $chosen_method;
        }

        /**
         * Get the Superfrete order information.
         *
         * @param int  $order_id      The ID of the order.
         * @param bool $retry_request Whether to retry the request in case of failure.
         * @return array|bool The order information array or false on failure.
         */
        public function get_superfrete_order_info($order_id, $retry_request = false) {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];
            $superfrete_api_path = '/api/v0/order/info';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path = '/apiIntegrationV1OrderGetInfo/api/v0/order/info';
            }
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'GET',
                'body' => '',
                'timeout ' => 600,
            );
            $superfrete_order_id = get_post_meta($order_id, 'wp_superfrete_order_id', true);
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path . '/' . $superfrete_order_id, $params);
            if ($result instanceof WP_Error) {
                if (( strstr($result->get_error_message(), 'cURL error 28') || strstr($result->get_error_message(), 'timed out after') ) && $retry_request) {
                    $this->get_superfrete_order_info($order_id, true);
                }
            }
            $result_arr = json_decode($result['body'], true);
            if (!empty($result_arr) && 200 === $result['response']['code']) {
                return $result_arr;
            } else {
                return false;
            }
        }

        /**
         * Resend Superfrete order.
         *
         * @param int  $order_id      The ID of the order.
         * @param bool $retry_request Whether to retry the request in case of failure.
         * @return void
         */
        public function superfrete_resend_order($order_id, $retry_request = true) {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];
            $superfrete_api_path_calculator = '/api/v0/calculator';
            $superfrete_api_path_cart = '/api/v0/cart';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path_calculator = '/apiIntegrationV1Calculator/api/v0/calculator';
                $superfrete_api_path_cart = '/apiIntegrationV1Cart/api/v0/cart';
            }
            $order_id = intval($order_id);
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $wp_superfrete_payload = get_post_meta($order_id, 'wp_superfrete_payload', true);
            $payload_array = json_decode($wp_superfrete_payload, true);
            $payload_quotation = array(
                'from' => array(
                    'postal_code' => str_replace('-', '', $payload_array['from']['postal_code']),
                ),
                'to' => array(
                    'postal_code' => str_replace('-', '', $payload_array['to']['postal_code']),
                ),
                'services' => $payload_array['service'],
                'products' => $payload_array['products'],
            );
            $quotation = $this->calculate_quotation($payload_quotation);
            if (!$quotation) {
                echo wp_kses_post('022');
                die;
            }
            $volumes = $this->get_volumes($quotation);
            $payload_array['volumes'] = $volumes;
            update_post_meta($order_id, 'wp_superfrete_payload', wp_json_encode($payload_array, JSON_UNESCAPED_UNICODE), true);

            $params = array(
                'headers' => $headers,
                'method' => 'POST',
                'body' => wp_json_encode($payload_array, JSON_UNESCAPED_UNICODE),
                'timeout' => 600,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path_cart, $params);

            if ($result instanceof WP_Error) {
                if (( strstr($result->get_error_message(), 'cURL error 28') || strstr($result->get_error_message(), 'timed out after') ) && $retry_request) {
                    $this->superfrete_resend_order($order_id, false);
                }
            } else {
                $data = json_decode($result['body'], true);
                if (200 !== $result['response']['code']) {
                    var_dump($result);
                    echo wp_kses_post('011');
                    die;
                } else {
                    update_post_meta($order_id, 'wp_superfrete_success', true);
                    $result_data = json_decode($result['body'], true);

                    $method_names = array(
                        1 => 'PAC',
                        2 => 'Sedex',
                        17 => 'Mini',
                    );

                    if (metadata_exists('post', $order_id, 'wp_superfrete_order_id')) {
                        update_post_meta($order_id, 'wp_superfrete_order_id', $result_data['id']);
                        update_post_meta($order_id, 'wp_superfrete_order_status', $result_data['status']);
                    } else {
                        add_post_meta($order_id, 'wp_superfrete_method_name', $method_names[$payload_array['service']], true);
                        add_post_meta($order_id, 'wp_superfrete_order_id', $result_data['id'], true);
                        add_post_meta($order_id, 'wp_superfrete_order_status', $result_data['status'], true);
                        add_post_meta($order_id, 'wp_superfrete_order_tracking', $result_data['self_tracking'], true);
                        add_post_meta($order_id, 'wp_superfrete_order_tracking_url', 'https://rastreio.superfrete.com/#/tracking/' . $result_data['self_tracking'], true);

                        $print_url = $this->get_superfreteprint_url($result_data['id']);
                        if ($print_url) {
                            add_post_meta($order_id, 'wp_superfrete_print_url', $print_url, true);
                        }
                    }

                    echo wp_kses_post('1');
                    die;
                }
            }
        }

        /**
         * Complete the Superfrete payment process.
         *
         * @param int|WC_Order $order           The order object or ID.
         * @param bool         $from_order_page Whether the request is from the order page.
         * @return void
         */
        public function superfrete_payment_complete($order, $from_order_page = false) {
            $debug_steps = '';
            $superfrete_token = $this->superfrete_settings['superfrete_token'];
            $superfrete_api_path_calculator = '/api/v0/calculator';
            $superfrete_api_path_cart = '/api/v0/cart';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path_calculator = '/apiIntegrationV1Calculator/api/v0/calculator';
                $superfrete_api_path_cart = '/apiIntegrationV1Cart/api/v0/cart';
            }
            if ($from_order_page) {
                $this->superfrete_resend_order($order, true);
                exit;
            }
            $order = wc_get_order($order);
            $order_data = $order->get_data();

            $chosen_method = $this->get_chosen_method($order_data['shipping_lines']);
            $products = $this->get_products_by_order_data($order_data['line_items']);
            $from_postal_code = ( isset($_SESSION['superfrete']['address']['postal_code']) ) ? sanitize_text_field($_SESSION['superfrete']['address']['postal_code']) : $this->superfrete_settings['superfrete_address_postal_code'];
            $payload_quotation = array(
                'from' => array(
                    'postal_code' => str_replace('-', '', $from_postal_code),
                ),
                'to' => array(
                    'postal_code' => str_replace('-', '', $order_data['shipping']['postcode']),
                ),
                'services' => $chosen_method,
                'products' => $products,
            );
            $quotation = $this->calculate_quotation($payload_quotation);
            $volumes = ( $quotation ) ? $this->get_volumes($quotation) : '';
            $from_name = ( isset($_SESSION['superfrete']['user']['fullname']) ) ? $_SESSION['superfrete']['user']['fullname'] : $this->superfrete_settings['superfrete_user_firstname'] . ' ' . $this->superfrete_settings['superfrete_user_lastname'];
            $from_address = ( isset($_SESSION['superfrete']['address']['street']) ) ? $_SESSION['superfrete']['address']['street'] : $this->superfrete_settings['superfrete_address_street'];
            $from_complement = ( isset($_SESSION['superfrete']['address']['complement']) ) ? sanitize_text_field($_SESSION['superfrete']['address']['complement']) : $this->superfrete_settings['superfrete_address_complement'];
            $from_district = ( isset($_SESSION['superfrete']['address']['district']) ) ? $_SESSION['superfrete']['address']['district'] : $this->superfrete_settings['superfrete_address_district'];
            $from_city = ( isset($_SESSION['superfrete']['address']['city']) ) ? $_SESSION['superfrete']['address']['city'] : $this->superfrete_settings['superfrete_address_city'];
            $from_state = ( isset($_SESSION['superfrete']['address']['state']) ) ? $_SESSION['superfrete']['address']['state'] : $this->superfrete_settings['superfrete_address_state'];
            $from_postal_code = ( isset($_SESSION['superfrete']['address']['postal_code']) ) ? $_SESSION['superfrete']['address']['postal_code'] : $this->superfrete_settings['superfrete_address_postal_code'];
            $from_number = ( isset($_SESSION['superfrete']['address']['number']) ) ? $_SESSION['superfrete']['address']['number'] : $this->superfrete_settings['superfrete_address_number'];
            $options_own_hand = ( isset($_SESSION['superfrete']['own_hand']) ) ? $_SESSION['superfrete']['own_hand'] : $this->superfrete_settings['superfrete_own_hand'];
            $options_receipt = ( isset($_SESSION['superfrete']['receipt']) ) ? $_SESSION['superfrete']['receipt'] : $this->superfrete_settings['superfrete_receipt'];
            $options_insurance_value = ( isset($_SESSION['superfrete']['insurance_value']) ) ? $_SESSION['superfrete']['insurance_value'] : $this->superfrete_settings['superfrete_insurance_value'];

            $meta_data_array = $order_data['meta_data'];

            function get_data($param, $order_data) {
                $meta_data_array = $order_data['meta_data'];
                foreach ($meta_data_array as $meta_data) {
                    // Use Reflection para acessar propriedades protegidas
                    $reflection = new ReflectionClass($meta_data);
                    $property = $reflection->getProperty('current_data');
                    $property->setAccessible(true);
                    $current_data = $property->getValue($meta_data);
                    $value = (!empty(get_post_meta($order_data['id'], '_shipping_' . $param, true))) ? get_post_meta($order_data['id'], '_shipping_' . $param, true) : null;
                    if (empty($value) && isset($current_data['key']) && $current_data['key'] === '_shipping_' . $param) {
                        $value = $current_data['value'];
                    }
                    if (empty($value) && isset($current_data['key']) && $current_data['key'] === '_billing_' . $param) {
                        $value = $current_data['value'];
                    }

                    if (!empty($value)) {
                        return $value;
                    }
                }
                if (empty($value)) {
                  
                 $value = isset($order_data['billing'][$param]) ? $order_data['billing'][$param] : '';

                }
                 if (empty($value)) {
                return "";
                 }
                 else{
                     return $value;
                 }
            }

            $payload = array(
                'from' => array(
                    'name' => $from_name,
                    'address' => $from_address,
                    'complement' => $from_complement,
                    'district' => $from_district,
                    'city' => $from_city,
                    'state_abbr' => $from_state,
                    'postal_code' => $from_postal_code,
                    'number' => $from_number,
                ),
                'to' => array(
                    'name' => get_data('first_name', $order_data) . ' ' . get_data('last_name', $order_data),
                    'address' => get_data('address_1', $order_data),
                    'complement' => get_data('address_2', $order_data),
                    'district' => !empty(get_data('neighborhood', $order_data)) ? get_data('neighborhood', $order_data) : "N/A",
                    'city' => get_data('city', $order_data),
                    'state_abbr' => get_data('state', $order_data),
                    'postal_code' => str_replace('-', '', get_data('postcode', $order_data)),
                    'number' => get_data('number', $order_data),
                ),
                'service' => $chosen_method,
                'products' => $products,
                'volumes' => $volumes,
                'options' => array(
                    'receipt' => (bool) json_decode(strtolower($options_receipt)),
                    'own_hand' => (bool) json_decode(strtolower($options_own_hand)),
                    'use_insurance_value' => (bool) json_decode(strtolower($options_insurance_value)),
                    'insurance_value' => $this->get_insurance_value($order),
                    'invoice' => array(
                        'number' => '',
                    ),
                ),
                'platform' => 'Woocommerce SuperFrete',
            );
           
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'POST',
                'body' => wp_json_encode($payload),
                'timeout ' => 1200,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path_cart, $params);

            add_post_meta(
                    $order_data['id'],
                    'wp_superfrete_payload',
                    wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
                    true
            );
            $wp_superfrete_success = false;

            if ($result instanceof WP_Error) {
                $error_message = $result->get_error_message();
                $debug_steps .= 'Line562;';
            } elseif (200 !== $result['response']['code']) {

                $error_message = $result;
                $debug_steps .= 'Line572;';
            } else {
                $wp_superfrete_success = true;
                add_post_meta($order_data['id'], 'wp_superfrete_success', $wp_superfrete_success, true);
                $success_message = $result;

                $result_data = json_decode($result['body'], true);
                $method_names = array(
                    1 => 'PAC',
                    2 => 'Sedex',
                    17 => 'Mini',
                );
                add_post_meta($order_data['id'], 'wp_superfrete_method_name', $method_names[$chosen_method], true);
                $debug_steps .= 'Line589;';
                add_post_meta($order_data['id'], 'wp_superfrete_order_id', $result_data['id'], true);
                $debug_steps .= 'Line592;';
                add_post_meta($order_data['id'], 'wp_superfrete_order_status', $result_data['status'], true);
                $debug_steps .= 'Line592;';
                add_post_meta($order_data['id'], 'wp_superfrete_order_tracking', $result_data['self_tracking'], true);
                $debug_steps .= 'Line595;';
                add_post_meta($order_data['id'], 'wp_superfrete_order_tracking_url', 'https://rastreio.superfrete.com/#/tracking/' . $result_data['self_tracking'], true);
                $debug_steps .= 'Line598;';
                $print_url = $this->get_superfreteprint_url($result_data['id']);
                $debug_steps .= 'Line601;';

                if ($print_url) {
                    add_post_meta($order_data['id'], 'wp_superfrete_print_url', $print_url, true);
                    $debug_steps .= 'Line604;';
                }
            }

            $superfrete_environment = '';

            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_environment = 'sandbox';
            } else {
                'production' === $superfrete_environment;
            }
            add_post_meta($order_data['id'], 'wp_superfrete_environment', $superfrete_environment, true);
            $debug_steps .= 'Line619;';
        }

        /**
         * Register the Superfrete metabox for shop orders.
         *
         * @return void
         */
        public function superfrete_register_metabox() {
            $screen = 'shop_order';

            if (defined('WC_VERSION') && version_compare(WC_VERSION, '7.1', '>=')) {
                $hpos_enabled = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
                $screen = $hpos_enabled ? wc_get_page_screen_id('shop-order') : 'shop_order';
            }
            $order_id = get_the_ID();

            add_meta_box('wc_superfrete_order', '<span><b class="superfrete-colors">SuperFrete</b></span>', array($this, 'superfrete_side_box_include'), $screen, 'side', 'default');
        }

        /**
         * Add the Superfrete side box to the shop order edit screen.
         *
         * @param WP_Post $post The current post object.
         * @return void
         */
        public function superfrete_side_box_include($post) {

            $this->include_view('admin-ajax');
            self::include_view('superfrete-side-box-orders', $this->order_has_superfrete($post->ID));
        }

        /**
         * Resend the Superfrete order.
         *
         * @param string $order The order identifier.
         * @return void
         */
        public function superfrete_reenviar_pedido($order) {
            $resend_order = sanitize_text_field(wp_unslash($order));
            $this->superfrete_payment_complete($resend_order, true);

            exit;
        }

        /**
         * Verify the Superfrete order label.
         *
         * @param string $verify_order_print_url The URL to verify the order print.
         * @return void
         */
        public function superfrete_verificar_etiqueta($verify_order_print_url) {
            // Nonce in main function.
            $order_id = sanitize_text_field(wp_unslash($verify_order_print_url));

            $order_info = $this->get_superfrete_order_info($order_id);

            if ($order_info) {
                update_post_meta($order_id, 'wp_superfrete_order_status', $order_info['status']);
                update_post_meta($order_id, 'wp_superfrete_print_url', $order_info['print']['url']);
                echo wp_kses_post($order_info['status']);
            }
            exit;
        }

        /**
         * Validate the Superfrete token.
         *
         * @param string $token       The Superfrete token.
         * @param string $environment The environment ('production' or otherwise).
         * @return void
         */
        public function superfrete_validate_token($token, $environment) {
            $superfrete_token = $token;
            $superfrete_environment = $environment;

            if ('production' === $environment) {
                $superfrete_api_url = 'https://api.superfrete.com';
            } else {
                $superfrete_api_url = $this->superfrete_api_url;
            }

            $superfrete_api_path = '/api/v0/user';

            if (strstr($superfrete_api_url, 'localhost') || strstr($superfrete_api_url, 'test')) {
                $superfrete_api_path = '/apiIntegrationV1UserGetInfo/api/v0/user';
            }

            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );

            $params = array(
                'headers' => $headers,
                'method' => 'GET',
                'body' => '',
                'timeout ' => 5600,
            );

            $result = wp_remote_post($superfrete_api_url . $superfrete_api_path, $params);
           
            
            if ($result instanceof WP_Error) {
                echo wp_kses_post('2');
                die;
            } else {
                $data = json_decode($result['body'], true);

                if (200 !== $result['response']['code']) {
                    echo wp_kses_post('033');
                } else {
                    echo wp_kses_post('1');
                }
                die;
            }
        }

        /**
         * Get Superfrete user information.
         *
         * @return void
         */
        public function get_superfrete_user_info() {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];
            $superfrete_api_path = '/api/v0/user';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path = '/apiIntegrationV1UserGetInfo/api/v0/user';
            }
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'GET',
                'body' => '',
                'timeout ' => 600,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path, $params);
            if ($result instanceof WP_Error) {
                return;
            }
            $result_arr = json_decode($result['body'], true);
            if (!empty($result_arr)) {
                $_SESSION['superfrete']['user']['fullname'] = $result_arr['firstname'] . ' ' . $result_arr['lastname'];
                $this->superfrete_settings['superfrete_user_firstname'] = $result_arr['firstname'];
                $this->superfrete_settings['superfrete_user_lastname'] = $result_arr['lastname'];
            }

            echo wp_kses_post($result['body']);
        }

        /**
         * Get Superfrete user application information.
         *
         * @return void
         */
        public function get_superfrete_user_app_info() {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];

            $superfrete_api_path = '/api/v0/user/app';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path = '/apiIntegrationV1UserGetAppInfo/api/v0/user/app';
            }
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'GET',
                'body' => '',
                'timeout ' => 600,
            );

            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path, $params);

            if ($result instanceof WP_Error) {
                echo wp_kses_post('2');
            }

            $result_arr = json_decode($result['body'], true);

            if (!empty($result_arr)) {
                if (!empty($result_array['config']['optional_services'])) {
                    $_SESSION['superfrete']['own_hand'] = $result_arr['config']['optional_services']['own_hand'];
                    $_SESSION['superfrete']['insurance_value'] = $result_arr['config']['optional_services']['insurance_value'];
                    $_SESSION['superfrete']['receipt'] = $result_arr['config']['optional_services']['receipt'];
                    $this->superfrete_settings['superfrete_own_hand'] = $result_arr['config']['optional_services']['own_hand'];
                    $this->superfrete_settings['superfrete_insurance_value'] = $result_arr['config']['optional_services']['insurance_value'];
                    $this->superfrete_settings['superfrete_receipt'] = $result_arr['config']['optional_services']['receipt'];
                }

                echo wp_kses_post($result['body']);
            } else {
                echo wp_kses_post('044');
            }
        }

        /**
         * Get Superfrete print URL.
         *
         * @param int $order_id The ID of the order.
         * @return string|false The print URL or false on failure.
         */
        public function get_superfreteprint_url($order_id) {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];

            $superfrete_api_path_addresses = '/api/v0/tag/print';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path_addresses = '/apiIntegrationV1GenerateTagLink/api/v0/tag/print';
            }
            $my_payload = array(
                'mode' => 'public',
                'orders' => array($order_id),
            );
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'POST',
                'body' => wp_json_encode($my_payload),
                'timeout ' => 600,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path_addresses, $params);
            if ($result instanceof WP_Error) {
                return false;
            }
            $result_arr = json_decode($result['body'], true);

            if (!empty($result_arr['url'])) {
                return $result_arr['url'];
            } else {
                return false;
            }
        }

        /**
         * Get Superfrete addresses and store them in the session.
         *
         * @return void
         */
        public function get_superfrete_addresses() {
            $superfrete_token = $this->superfrete_settings['superfrete_token'];

            $superfrete_api_path_addresses = '/api/v0/user/addresses';
            if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                $superfrete_api_path_addresses = '/apiIntegrationV1UserGetAddresses/api/v0/user/addresses';
            }
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $superfrete_token,
                'Platform' => 'Woocommerce SuperFrete',
            );
            $params = array(
                'headers' => $headers,
                'method' => 'GET',
                'body' => '',
                'timeout ' => 600,
            );
            $result = wp_remote_post($this->superfrete_api_url . $superfrete_api_path_addresses, $params);
            if ($result instanceof WP_Error) {
                return;
            }
            $result_arr = json_decode($result['body'], true);
            if (!empty($result_arr['data'])) {

                if (!empty($this->superfrete_settings['superfrete_address_postal_code'])) {
                    $_SESSION['superfrete']['address']['street'] = $this->superfrete_settings['superfrete_address_street'];
                    $_SESSION['superfrete']['address']['complement'] = $this->superfrete_settings['superfrete_address_complement'];
                    $_SESSION['superfrete']['address']['district'] = $this->superfrete_settings['superfrete_address_district'];
                    $_SESSION['superfrete']['address']['city'] = $this->superfrete_settings['superfrete_address_city'];
                    $_SESSION['superfrete']['address']['state'] = $this->superfrete_settings['superfrete_address_state'];
                    $_SESSION['superfrete']['address']['postal_code'] = $this->superfrete_settings['superfrete_address_postal_code'];
                }
            }

            echo wp_kses_post($result['body']);
        }

        /**
         * Get the Superfrete verification URL based on the environment settings.
         *
         * @return string The Superfrete verification URL.
         */
        public function superfrete_verify_url() {
            if (isset($this->superfrete_settings['superfrete_sandbox_enabled']) && $this->superfrete_settings['superfrete_sandbox_enabled']) {
                if (strstr($this->superfrete_api_url, 'localhost') || strstr($this->superfrete_api_url, 'test')) {
                    return 'https://test-521af.web.app/';
                } else {
                    return 'https://sandbox.superfrete.com';
                }
            } else {
                return 'https://web.superfrete.com';
            }
        }

        /**
         * Check if the order has Superfrete shipping method and retrieve relevant information.
         *
         * @param WC_Order|int $order The order object or order ID.
         * @return array An array containing Superfrete-related information.
         */
        public function order_has_superfrete($order) {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }

            $shipping_methods = $order->get_items('shipping');
            $reference = false;

            if (is_array($shipping_methods) && count($shipping_methods) > 0) {
                foreach ($shipping_methods as $shipping) {
                    if (!empty($shipping->get_formatted_meta_data())) {
                        $reference = array_search('referencia_superfrete', array_column($shipping->get_formatted_meta_data(), 'key'), true);

                        break;
                    }
                }
            }

            $superfrete_success = get_post_meta($order->get_id(), 'wp_superfrete_success', true);
            $superfrete_environment = get_post_meta($order->get_id(), 'wp_superfrete_environment', true);

            if (!$superfrete_environment) {
                $superfrete_environment = ( isset($this->superfrete_settings['superfrete_sandbox_enabled']) && $this->superfrete_settings['superfrete_sandbox_enabled'] ) ? 'sandbox' : 'production';
            }

            $superfrete_url = $this->superfrete_verify_url();
            $superfreteprint_url = get_post_meta($order->get_id(), 'wp_superfrete_print_url', true);

            if (!$superfreteprint_url) {
                $superfreteprint_url = $superfrete_url;
            }

            $superfrete_tracking_url = get_post_meta($order->get_id(), 'wp_superfrete_order_tracking_url', true);
            $superfrete_order_status = get_post_meta($order->get_id(), 'wp_superfrete_order_status', true);

            return array(
                'order_id' => $order->get_id(),
                'has_superfrete' => $reference,
                'superfrete_environment' => $superfrete_environment,
                'superfrete_success' => $superfrete_success,
                'superfrete_print_url' => $superfreteprint_url,
                'superfrete_tracking_url' => $superfrete_tracking_url,
                'superfrete_url' => $superfrete_url,
                'superfrete_order_status' => $superfrete_order_status,
            );
        }

        /**
         * Applies the shipping fields provided in the shipping calculation form.
         *
         * @return void
         */
        public function superfrete_apply_fields_on_shipping() {
            $country = 'BR';
            $cep = isset(  self::$input_post['calc_shipping_postcode']) ? sanitize_text_field(wp_unslash(  self::$input_post['calc_shipping_postcode'])) : '';
            $cep = wc_format_postcode($cep, $country);
            $state = isset(  self::$input_post['calc_shipping_state']) ? sanitize_text_field(wp_unslash(  self::$input_post['calc_shipping_state'])) : wc_get_customer_default_location()['state'];
            $city = isset(  self::$input_post['calc_shipping_city']) ? sanitize_text_field(wp_unslash(  self::$input_post['calc_shipping_city'])) : '';

            if (!empty($cep)) {
                WC()->shipping->reset_shipping();
                WC()->customer->set_location($country, $state, $cep, $city);
                WC()->customer->save();
            }
        }

        /**
         * Saves the quick shipping fields for a product.
         *
         * @param WC_Product $product Product object.
         * @return void
         */
        public function superfrete_save_quick_shipping_fields($product) {
            if (!( isset(  self::$input_post['nonce_field']) && wp_verify_nonce(  self::$input_post['nonce_field'], 'validateOnce') )) {
                wp_die('Nonce verification failed 4');
            }
            $product_id = $product->id;
            if ($product_id > 0) {
                $metavalue = isset($_REQUEST[self::$calculator_metakey]) ? 'yes' : 'no';
                update_post_meta($product_id, self::$calculator_metakey, $metavalue);
            }
        }

        /**
         * Outputs the quick shipping fields in the product settings.
         *
         * @return void
         */
        public function superfrete_output_quick_shipping_fields() {
            include self::$plugin_dir . 'view/quick-settings.php';
        }

        /**
         * Outputs the quick shipping values in the product list.
         *
         * @param string $column The column name.
         * @return void
         */
        public function superfrete_output_quick_shipping_values($column) {
            global $post;
            $product_id = $post->ID;
            if ('name' === $column) {
                $est_meta = get_post_meta($product_id, self::$calculator_metakey, true);
                ?>
                <div class="hidden" id="rpwoo_shipping_inline_<?php echo esc_html($product_id); ?>">
                    <div class="_shipping_enable">
                <?php echo esc_html($est_meta); ?>
                    </div>
                </div>
                <?php
            }
        }

        /**
         * Saves the bulk shipping fields for a product.
         *
         * @param WC_Product $product Product object.
         * @return void
         */
        public function superfrete_save_bulk_shipping_fields($product) {
            $product_id = $product->id;
            if (!( isset(  self::$input_post['nonce_field']) && wp_verify_nonce(  self::$input_post['nonce_field'], 'validateOnce') )) {
                wp_die('Nonce verification failed 51');
            }
            if ($product_id > 0) {
                $metavalue = isset($_REQUEST[self::$calculator_metakey]) ? 'yes' : 'no';
                update_post_meta($product_id, self::$calculator_metakey, $metavalue);
            }
        }

        /**
         * Processes and saves custom product meta data.
         *
         * @param int $post_id The ID of the post (product).
         * @return void
         */
        public function superfrete_custom_woocommerce_process_product_meta($post_id) {
            $metavalue = isset(  self::$input_post[self::$calculator_metakey]) ? 'yes' : 'no';
            update_post_meta($post_id, self::$calculator_metakey, $metavalue);
        }

        /**
         * Adds a custom checkbox to the product price box.
         *
         * @return void
         */
        public function superfrete_custom_price_box_include() {
            global $post;
            $post_id = $post->ID;
            $hide_calculator = 'yes';

            if (null !== $post_id) {
                $hide_calculator = get_post_meta(sanitize_text_field(wp_unslash($post_id)), self::$calculator_metakey, true);
            }

            woocommerce_wp_checkbox(
                    array(
                        'id' => self::$calculator_metakey,
                        'value' => $hide_calculator,
                        'label' => __('Hide Shipping Calculator?', 'superfrete_hide_calculator'),
                    )
            );
        }

        /**
         * Updates the shipping method based on the provided data.
         *
         * @return void
         */
        public function superfrete_update_shipping_method() {
            if (isset(  self::$input_post['nonce_field']) && wp_verify_nonce(  self::$input_post['nonce_field'], 'validateOnce')) {
                  self::$input_post['calc_shipping_country'] = 'BR';
                WC_Shortcode_Cart::calculate_shipping();
                $cart_item_key = null;

                if (isset(  self::$input_post['product_id']) && $this->check_product_incart(sanitize_text_field(wp_unslash(  self::$input_post['product_id']))) === false) {
                    $qty = ( isset(  self::$input_post['current_qty']) && sanitize_text_field(wp_unslash(  self::$input_post['current_qty'])) > 0 ) ? sanitize_text_field(wp_unslash(  self::$input_post['current_qty'])) : 1;

                    if (isset(  self::$input_post['variation_id']) && sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])) !== '' && sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])) > 0) {
                        $cart_item_key = WC()->cart->add_to_cart(sanitize_text_field(wp_unslash(  self::$input_post['product_id'])), $qty, sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])));
                    } else {
                        $cart_item_key = WC()->cart->add_to_cart(sanitize_text_field(wp_unslash(  self::$input_post['product_id'])), $qty);
                    }
                }

                self::get_shipping_methods();

                if (!empty($cart_item_key)) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            } else {
                // Trata caso o nonce esteja ausente ou inválido.
                wp_die('Erro de segurança: Nonce ausente ou inválido.');
            }

            die;
        }

        /**
         * Retrieves available shipping methods for the current cart.
         *
         * @return void
         */
        public static function get_shipping_methods() {
            $packages = WC()->cart->get_shipping_packages();
            $packages = WC()->shipping->calculate_shipping($packages);

            $available_methods = WC()->shipping->get_packages();
            $methods = array();

            if (isset($available_methods[0]['rates']) && count($available_methods[0]['rates']) > 0) {
                foreach ($available_methods as $rates) {
                    foreach ($rates['rates'] as $key => $method) {
                        $data = array(
                            'cost' => $method->cost,
                            'label' => $method->label,
                            'value' => $key,
                            'checked' => checked($key, WC()->session->chosen_shipping_method, true),
                            'deadline' => self::get_delivery_deadline($method),
                            'point_address' => !empty($method->get_meta_data()['point_address']) ? $method->meta_data['point_address'] : '',
                            'distance' => !empty($method->get_meta_data()['point_distance']) ? $method->meta_data['point_distance'] : '',
                            'point_label' => !empty($method->get_meta_data()['point_label']) ? $method->meta_data['point_label'] : '',
                            'shipping_label' => !empty($method->get_meta_data()['shipping_label']) ? $method->meta_data['shipping_label'] . '112233' : '',
                        );
                        if (!empty($method->get_meta_data()['point_code'])) {
                            $methods['pickup'][] = $data;
                        } else {
                            $methods['delivery'][] = $data;
                        }
                    }
                }
            }

            self::include_view('shipping-methods', $methods);
        }

        /**
         * Gets the delivery deadline for a given shipping method.
         *
         * @param WC_Shipping_Rate $method Shipping method object.
         * @return string Delivery deadline.
         */
        public static function get_delivery_deadline($method) {
            $deadline = !empty($method->get_meta_data()['deadline']) ? $method->meta_data['deadline'] : '';
            if (!$deadline && !empty($method->get_meta_data()['_delivery_forecast'])) {
                $delivery_forecast = $method->meta_data['_delivery_forecast'] ? intval($method->meta_data['_delivery_forecast']) : 0;
                // Translators: %d is the number of working days for delivery.
                $deadline = esc_html(sprintf(_n('Delivery within %d working day', 'Delivery within %d working days', $delivery_forecast, 'superfrete'), $delivery_forecast));
            }
            return $deadline;
        }

        /**
         * Converts a dimension value to centimeters.
         *
         * @param float $value Dimension value.
         * @return float Converted value in centimeters.
         */
        public static function dimension_converter_unit_to_centimeter($value) {
            $value = (float) $value;
            $to_unit = 'cm';
            $from_unit = strtolower(get_option('woocommerce_dimension_unit'));

            return floatval(number_format(wc_get_dimension($value, $to_unit, $from_unit), 2, '.', ''));
        }

        /**
         * Converts a weight value to kilograms.
         *
         * @param float $weight Weight value.
         * @return float Converted value in kilograms.
         */
        public static function dimension_converter_weight_unit($weight) {
            $weight = (float) $weight;
            $to_unit = 'kg';
            $from_unit = strtolower(get_option('woocommerce_weight_unit'));

            return wc_get_weight($weight, $to_unit, $from_unit);
        }

        /**
         * Retrieves the products from the WooCommerce cart.
         *
         * @return array List of products with their details.
         */
        public static function get_products_from_woo() {
            global $woocommerce;

            $items = $woocommerce->cart->get_cart();

            $products = array();

            foreach ($items as $item_product) {
                $product_id = ( 0 !== $item_product['variation_id'] ) ? $item_product['variation_id'] : $item_product['product_id'];

                $product_info = wc_get_product($product_id);

                if (empty($product_info)) {
                    continue;
                }
                $data = $product_info->get_data();

                $products[] = array(
                    'id' => $item_product['product_id'],
                    'variation_id' => $item_product['variation_id'],
                    'name' => $data['name'],
                    'price' => $product_info->get_price(),
                    'insurance_value' => $product_info->get_price(),
                    'height' => self::dimension_converter_unit_to_centimeter($product_info->get_height()),
                    'width' => self::dimension_converter_unit_to_centimeter($product_info->get_width()),
                    'length' => self::dimension_converter_unit_to_centimeter($product_info->get_length()),
                    'weight' => self::dimension_converter_weight_unit($product_info->get_weight()),
                    'quantity' => ( isset($item_product['quantity']) ) ? intval($item_product['quantity']) : 1,
                );
            }
            return $products;
        }

        /**
         * Retrieves shipping options for the current cart.
         *
         * @return void
         */
        public function superfrete_get_shippings_to_cart() {
            $packages = WC()->shipping()->get_packages();
            $first = true;
            foreach ($packages as $i => $package) {
                $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
                $product_names = array();
                if (count($packages) > 1) {
                    foreach ($package['contents'] as $item_id => $values) {
                        $product_names[$item_id] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                    }
                    $product_names = apply_filters('woocommerce_shipping_package_details_array', $product_names, $package);
                }
                $available_methods = array();
                foreach ($package['rates'] as $rate) {
                    if (!empty($rate->get_meta_data()['point_code'])) {
                        $available_methods['pickup'][] = $rate;
                    } else {
                        $available_methods['delivery'][] = $rate;
                    }
                }
                self::include_view(
                        'cart-shipping',
                        array(
                            'package' => $package,
                            'available_methods' => $available_methods,
                            'show_package_details' => count($packages) > 1,
                            'show_shipping_calculator' => is_cart() && apply_filters('woocommerce_shipping_show_shipping_calculator', $first, $i, $package),
                            'package_details' => implode(', ', $product_names),
                            // Translators: %d is the name of package for delivery.
                            'package_name' => apply_filters('woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf(_x('Shipping %d', 'shipping packages', 'woocommerce'), ( $i + 1)) : _x('Shipping', 'shipping packages', 'woocommerce'), $i, $package),
                            'index' => $i,
                            'chosen_method' => $chosen_method,
                            'formatted_destination' => $package['destination']['postcode'],
                            'has_calculated_shipping' => WC()->customer->has_calculated_shipping(),
                        )
                );
            }
        }

        /**
         * Includes a view file with optional arguments.
         *
         * @param string $view View file name.
         * @param array  $args Arguments to pass to the view.
         * @return void
         */
        public static function include_view($view, $args = array()) {
            if (!empty($args)) {
                foreach ($args as $key => $value) {
                    $$key = $value;
                }
            }
            include_once self::get_view_path($view);
        }

        /**
         * Gets the path to a view file.
         *
         * @param string $view View file name.
         * @return string Path to the view file.
         */
        public static function get_view_path($view) {
            return sprintf('%s/view/%s.php', rtrim(self::$plugin_dir, '/'), str_replace('.php', '', $view));
        }

        /**
         * Displays the shipping calculator on the product page.
         *
         * @return void
         */
        public function superfrete_display_shipping_calculator() {
            global $product;
            if (get_post_meta($product->get_id(), self::$calculator_metakey, true) !== 'yes') {
                include_once self::$plugin_dir . 'view/shipping-calculator.php';
            }
        }

        /**
         * Returns the shipping calculator HTML content.
         *
         * @return string Shipping calculator content.
         */
        public function superfrete_srt_shipping_calculator() {
            ob_start();
            include_once self::$plugin_dir . 'view/shipping-calculator.php';
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        /**
         * Checks if a product is already in the cart.
         *
         * @param int $product_id Product ID.
         * @return bool True if the product is in the cart, false otherwise.
         */
        public function check_product_incart($product_id) {
            foreach (WC()->cart->get_cart() as $values) {
                $_product = $values['data'];
                if ($product_id === $_product->id) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Retrieves the shipping text for a given shipping method and country.
         *
         * @param string $shipping_method Shipping method ID.
         * @param string $country Country code.
         * @return array Shipping text and cost.
         */
        public function get_shipping_text($shipping_method, $country) {
            global $woocommerce, $post;

            if (!( isset(  self::$input_post['nonce_field']) && wp_verify_nonce(  self::$input_post['nonce_field'], 'validateOnce') )) {

                wp_die('Nonce verification failed 4');
            }

            $return_response = array();
            WC_Shortcode_Cart::calculate_shipping();

            if (isset(  self::$input_post['product_id']) && $this->check_product_incart(sanitize_text_field(wp_unslash(  self::$input_post['product_id']))) === false) {

                $qty = ( isset(  self::$input_post['current_qty']) && sanitize_text_field(wp_unslash(  self::$input_post['current_qty'])) > 0 ) ? sanitize_text_field(wp_unslash(  self::$input_post['current_qty'])) : 1;

                if (isset(  self::$input_post['variation_id']) && sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])) !== '' && sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])) > 0) {
                    $cart_item_key = WC()->cart->add_to_cart(sanitize_text_field(wp_unslash(  self::$input_post['product_id'])), $qty, sanitize_text_field(wp_unslash(  self::$input_post['variation_id'])));
                } else {
                    $cart_item_key = WC()->cart->add_to_cart(sanitize_text_field(wp_unslash(  self::$input_post['product_id'])), $qty);
                }
                $packages = WC()->cart->get_shipping_packages();
                $packages = WC()->shipping->calculate_shipping($packages,   self::$input_post);
                $packages = WC()->shipping->get_packages();
                WC()->cart->remove_cart_item($cart_item_key);
            } else {
                $packages = WC()->cart->get_shipping_packages();
                $packages = WC()->shipping->calculate_shipping($packages,   self::$input_post);
                $packages = WC()->shipping->get_packages();
            }
            wc_clear_notices();
            if (isset($packages[0]['rates'][$shipping_method])) {

                $selected_shiiping = $packages[0]['rates'][$shipping_method];
                $final_cost = $selected_shiiping->cost;

                if (isset($selected_shiiping->taxes) && !empty($selected_shiiping->taxes)) {
                    foreach ($selected_shiiping->taxes as $taxes) {
                        $final_cost = $final_cost + $taxes;
                    }
                }

                $return_response = array(
                    'label' => $selected_shiiping->label,
                    'cost' => wc_price($final_cost),
                );
            } else {

                $all_method = WC()->shipping->load_shipping_methods();
                $selected_method = $all_method[$shipping_method];
                $flag = 0;

                if ('including' === $selected_method->availability) :
                    foreach ($selected_method->countries as $methodcountry) {
                        if ($country === $methodcountry) {
                            $flag = 1;
                        }
                    }
                    if (0 === $flag) :
                        $message = $selected_method->method_title . ' is not available in selected country.';
                        $return_response = array(
                            'code' => 'error',
                            'message' => $message,
                        );
                    endif;
                endif;
            }
            return $return_response;
        }

        /**
         * Enqueues admin scripts and styles.
         *
         * @return void
         */
        public function superfrete_admin_script() {
            if (is_admin()) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('superfrete-admin', self::$plugin_url . 'assets/js/admin.js', array('wp-color-picker'), SUPERFRETE_VERSION, true);
                wp_enqueue_style('superfrete-admin', self::$plugin_url . 'assets/css/admin.css', array(), SUPERFRETE_VERSION);
            }
        }

        /**
         * Enqueues styles and scripts in the site's head section.
         *
         * @return void
         */
        public function wp_head() {
            wp_enqueue_style('shipping-calculator', self::$plugin_url . 'assets/css/shipping-calculator.css', array(), SUPERFRETE_VERSION);
            wp_enqueue_style('superfrete-cart', self::$plugin_url . 'assets/css/superfrete-cart.css', array(), SUPERFRETE_VERSION);
            wp_enqueue_script('jquery');
            wp_nonce_url(admin_url('admin.php?page=superfrete_settings&save_settings=true'), 'superfrete_nonce_action', 'superfrete_nonce_field');

            $this->include_view('admin-ajax');
        }

        /**
         * Enqueues scripts in the site's footer section.
         *
         * @return void
         */
        public function wp_footer() {
            wp_enqueue_script('wc-country-select');
            wp_enqueue_script('shipping-calculator', self::$plugin_url . 'assets/js/shipping-calculator.js', array(), SUPERFRETE_VERSION, true);
            wp_enqueue_script('superfrete-cart', self::$plugin_url . 'assets/js/superfrete-cart.js', array(), SUPERFRETE_VERSION, true);
        }

        /**
         * Adds the plugin settings page to the admin menu.
         *
         * @return void
         */
        public function admin_menu() {
            $wc_page = 'woocommerce';
            add_menu_page(
                    self::$plugin_title,
                    self::$plugin_title,
                    'install_plugins',
                    self::$plugin_slug,
                    array($this, 'calculator_setting_page'),
                    plugins_url('superfrete/assets/images/plugin-icon.png')
            );
        }

        /**
         * Displays the settings page for the shipping calculator.
         *
         * @return void
         */
        public function calculator_setting_page() {

            if (isset(  self::$input_post[self::$plugin_slug])) {
                $this->save_setting();
                exit;
            }
            $this->include_view('admin-ajax');
            include_once self::$plugin_dir . 'view/shipping-setting.php';
        }

        /**
         * Saves the plugin settings.
         *
         * @return void
         */
        public function save_setting() {

            $array_remove = array(self::$plugin_slug, 'btn-superfrete-submit');
            $save_data = array();
              self::$input_post = filter_input_array(INPUT_POST);
            if (isset(  self::$input_post['superfrete_address_postal_code'])) {
                $_SESSION['superfrete']['address']['street'] =   self::$input_post['superfrete_address_street'];    
                $_SESSION['superfrete']['address']['complement'] =   self::$input_post['superfrete_address_complement'];
                $_SESSION['superfrete']['address']['district'] =   self::$input_post['superfrete_address_district'];
                $_SESSION['superfrete']['address']['city'] =  self::$input_post['superfrete_address_city'];
                $_SESSION['superfrete']['address']['state'] =   self::$input_post['superfrete_address_state'];
                $_SESSION['superfrete']['address']['postal_code'] =   self::$input_post['superfrete_address_postal_code'];
                $this->superfrete_settings['superfrete_address_street'] =   self::$input_post['superfrete_address_street'];
                $this->superfrete_settings['superfrete_address_complement'] =  self::$input_post['superfrete_address_complement'];
                $this->superfrete_settings['superfrete_address_district'] = self::$input_post['superfrete_address_district'];
                $this->superfrete_settings['superfrete_address_city'] =  self::$input_post['superfrete_address_city'];
                $this->superfrete_settings['superfrete_address_state'] =   self::$input_post['superfrete_address_state'];
                $this->superfrete_settings['superfrete_address_postal_code'] =  self::$input_post['superfrete_address_postal_code'];
            }

            /*
              We use this dynamic foreach, because we need to guarantee that save all
              modified fields after a changes of input in the field on the form.
             */
            foreach (  self::$input_post as $key => $value) :

                if (in_array($key, $array_remove, true)) {
                    continue;
                }

                if ('superfrete_address_postal_code' === $key) {
                    $_SESSION['superfrete']['address']['postal_code'] = sanitize_text_field($value); // FIXED.
                }

                $save_data[$key] = sanitize_text_field($value);

            endforeach;
            $input_get = filter_input_array(INPUT_GET);
            if (isset($input_get['confirm_config']) && !empty((int) $input_get['confirm_config'])) {
                $save_data['confirm_config'] = true;
            } else {
                $save_data['confirm_config'] = false;
            }

            $this->superfrete_settings = $save_data;
            update_option(self::$superfrete_option_key, $save_data);
        }

        /**
         * Retrieves a specific plugin setting value.
         *
         * @param string $key Setting key.
         * @return mixed Setting value.
         */
        public function get_setting($key) {
            if (!$key || '' === $key) {
                return;
            }
            if (!isset($this->superfrete_settings[$key])) {
                return;
            }

            $value = $this->superfrete_settings[$key];

            return $value;
        }
    }

}

new SuperfreteShipping();
