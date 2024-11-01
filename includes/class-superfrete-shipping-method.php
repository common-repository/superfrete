<?php
/**
 * Superfrete Shipping Method Class File
 *
 * @package SuperfreteShippingMethod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! isset( $_SESSION ) ) {
	//session_start();
}

$woocommerce_file = 'woocommerce/woocommerce.php';

if ( ! in_array( $woocommerce_file, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	return;
}
/**
 * Initialize Superfrete Shipping Method
 */
function superfrete_shipping_method() {

	if ( class_exists( 'Superfrete_Shipping_Method' ) ) {
		return;
	}
	/**
	 * Superfrete Shipping Method Class
	 */
	class Superfrete_Shipping_Method extends WC_Shipping_Method {
		/**
		 * Shipping method code
		 *
		 * @var string
		 */
		public $code = '';
		/**
		 * Shipping company
		 *
		 * @var string
		 */
		public $company = '';
		/**
		 * Constructor for the shipping class.
		 */
                
                        
          private $superfrete_api_url;
    private $superfrete_api_sandbox_url;
		public function __construct() {

			$this->availability = 'including';
			$this->countries    = array( 'BR' );
			$this->method_title = 'Método SuperFrete';

			$superfrete_settings = get_option( 'superfrete-calculator-setting' );
			$this->enabled       = ! isset( $superfrete_settings['superfrete_enabled'] ) ? 'no' : 'yes';

			if ( ! isset( $superfrete_settings['superfrete_sandbox_enabled'] ) || ! $superfrete_settings['superfrete_sandbox_enabled'] ) {
				$this->superfrete_api_url         = 'https://api.superfrete.com';
				$this->superfrete_api_sandbox_url = $this->superfrete_api_url;

				if ( ! empty( $superfrete_settings['superfrete_token_production'] ) ) {
					$this->settings['superfrete_token'] = $superfrete_settings['superfrete_token_production'];
				}
			}

			if ( isset( $superfrete_settings['superfrete_sandbox_enabled'] ) && $superfrete_settings['superfrete_sandbox_enabled'] ) {

				$this->settings['superfrete_sandbox_enabled'] = true;

				if ( ! empty( $superfrete_settings['superfrete_token_sandbox'] ) ) {
					$this->settings['superfrete_token'] = $superfrete_settings['superfrete_token_sandbox'];
				}

				$superfrete_plugin_dir = __DIR__ . DIRECTORY_SEPARATOR;

				$this->superfrete_api_url         = 'https://sandbox.superfrete.com';
				$this->superfrete_api_sandbox_url = $this->superfrete_api_url;
			}

			$this->settings['superfrete_user_firstname']       = $superfrete_settings['superfrete_user_firstname'];
			$this->settings['superfrete_user_lastname']        = $superfrete_settings['superfrete_user_lastname'];
			$this->settings['superfrete_user_phone']           = $superfrete_settings['superfrete_user_phone'];
			$this->settings['superfrete_user_email']           = $superfrete_settings['superfrete_user_email'];
			$this->settings['superfrete_user_document']        = $superfrete_settings['superfrete_user_document'];
			$this->settings['superfrete_own_hand']             = $superfrete_settings['superfrete_own_hand'];
			$this->settings['superfrete_insurance_value']      = $superfrete_settings['superfrete_insurance_value'];
			$this->settings['superfrete_receipt']              = $superfrete_settings['superfrete_receipt'];
			$this->settings['superfrete_address_label']        = $superfrete_settings['superfrete_address_label'];
			$this->settings['superfrete_address_street']       = $superfrete_settings['superfrete_address_street'];
			$this->settings['superfrete_address_number']       = $superfrete_settings['superfrete_address_number'];
			$this->settings['superfrete_address_complement']   = $superfrete_settings['superfrete_address_complement'];
			$this->settings['superfrete_address_district']     = $superfrete_settings['superfrete_address_district'];
			$this->settings['superfrete_address_city']         = $superfrete_settings['superfrete_address_city'];
			$this->settings['superfrete_address_state']        = $superfrete_settings['superfrete_address_state'];
			$this->settings['superfrete_address_postal_code']  = $superfrete_settings['superfrete_address_postal_code'];
			$this->settings['superfrete_prazo_adicional']      = isset( $superfrete_settings['superfrete_prazo_adicional'] ) ? $superfrete_settings['superfrete_prazo_adicional'] : 0;
			$this->settings['superfrete_tipo_valor_adicional'] = isset( $superfrete_settings['superfrete_tipo_valor_adicional'] ) ? $superfrete_settings['superfrete_tipo_valor_adicional'] : '';
			$this->settings['superfrete_valor_adicional']      = isset( $superfrete_settings['superfrete_valor_adicional'] ) ? $superfrete_settings['superfrete_valor_adicional'] : 0;
		}

		/**
		 * Get product information from the product page.
		 *
		 * @param int $product_id The product ID.
		 * @param int $product_quantity The product quantity.
		 * @return array|false The product information or false if the product does not exist.
		 */
		public function getProductFromPage( $product_id, $product_quantity ) {
			$product_info = wc_get_product( $product_id );

			if ( empty( $product_info ) ) {
				return false;
			}

			$data = $product_info->get_data();

			$product_variation_id = $product_info->get_variation_id();

			$products[] = array(
				'id'              => $product_id,
				'variation_id'    => ( 0 !== $product_variation_id ) ? $product_variation_id : $product_id,
				'name'            => $product_info->get_name(),
				'price'           => $product_info->get_price(),
				'insurance_value' => $product_info->get_price(),
				'height'          => $product_info->get_height(),
				'width'           => $product_info->get_width(),
				'length'          => $product_info->get_length(),
				'weight'          => $product_info->get_weight(),
				'quantity'        => $product_quantity,
			);

			return $products;
		}
		/**
		 * Get products from the cart.
		 *
		 * @return array The products in the cart.
		 */
		public function getProductsFromCart() {
			$items = WC()->cart->get_cart();

			$products = array();
			foreach ( $items as $item_product ) {
				$product_id   = ( 0 !== $item_product['variation_id'] ) ? $item_product['variation_id'] : $item_product['product_id'];
				$product_info = wc_get_product( $product_id );
				if ( empty( $product_info ) ) {
					continue;
				}
				$data       = $product_info->get_data();
				$products[] = array(
					'id'              => $item_product['product_id'],
					'variation_id'    => $item_product['variation_id'],
					'name'            => $data['name'],
					'price'           => $product_info->get_price(),
					'insurance_value' => $product_info->get_price(),
					'height'          => $product_info->get_height(),
					'width'           => $product_info->get_width(),
					'length'          => $product_info->get_length(),
					'weight'          => $product_info->get_weight(),
					'quantity'        => ( isset( $item_product['quantity'] ) ) ? intval( $item_product['quantity'] ) : 1,
				);
			}
			return $products;
		}
		/**
		 * Calculate the price helper function.
		 *
		 * @param float $value The initial value.
		 * @param float $extra Extra value to add.
		 * @param float $percent Percentage to add.
		 * @return string The formatted price.
		 */
		public function moneyHelperPrice( $value, $extra, $percent ) {
			$value   = floatval( $value );
			$extra   = floatval( $extra );
			$percent = floatval( $percent );

			$value = $this->moneyHelperCalculateFinalValue( $value, $extra, $percent );

			return 'R$' . number_format( $value, 2, ',', '.' );
		}
		/**
		 * Calculate the cost helper function.
		 *
		 * @param float $value The initial value.
		 * @param float $extra Extra value to add.
		 * @param float $percent Percentage to add.
		 * @return float The final cost.
		 */
		public function moneyHelperCost( $value, $extra, $percent ) {
			$value   = floatval( $value );
			$extra   = floatval( $extra );
			$percent = floatval( $percent );

			return $this->moneyHelperCalculateFinalValue( $value, $extra, $percent );
		}
		/**
		 * Calculate the final value.
		 *
		 * @param float $value The initial value.
		 * @param float $extra Extra value to add.
		 * @param float $percent Percentage to add.
		 * @return float The final value.
		 */
		public function moneyHelperCalculateFinalValue( $value, $extra, $percent ) {
			$percent_extra = ( $value / 100 ) * $percent;

			$final_value = $value + $percent_extra + $extra;

			return ( $final_value > 0 ) ? $final_value : 0;
		}
		/**
		 * Convert string to float.
		 *
		 * @param mixed $value The value to convert.
		 * @return float The converted float value.
		 */
		public function moneyHelperFloatConverter( $value ) {
			if ( is_string( $value ) ) {
				$value = preg_replace( '/[^0-9,.]/', '', $value );
				$value = trim( $value );

				if ( preg_match( '/^\d*\.\d+\,\d+/', $value ) ) {
					$value = str_replace( '.', '', $value );
				} elseif ( preg_match( '/^\d*\,\d+\.\d+/', $value ) ) {
					$value = str_replace( ',', '', $value );
				}

				return (float) str_replace( ',', '.', $value );
			}

			return $value;
		}
		/**
		 * Check if the value is a discount.
		 *
		 * @param float $value The value to check.
		 * @return bool True if it is a discount, false otherwise.
		 */
		public function moneyHelperIsDiscount( $value ) {
			return $value < 0;
		}
		/**
		 * Get insurance value from products.
		 *
		 * @param array $products The products array.
		 * @return float The total insurance value.
		 */
		public function getInsuranceValue( $products ) {
			$total = 0;

			foreach ( $products as $p ) {
				$total = $total + ( $p['price'] * $p['quantity'] );
			}

			return round( $total, 2 );
		}
		/**
		 * Calculate shipping rates.
		 *
		 * @param array $package The package details.
		 * @param bool  $input_post Input post data.
		 * @return void
		 */
		public function calculate_shipping( $package = array(), $input_post = false ) {
			if ( ! $this->enabled || 'no' === $this->enabled ) {
				return;
			}
			if ( ! $this->settings['superfrete_token'] ) {
				return;
			}

			$to = str_replace( '-', '', $package['destination']['postcode'] );
			// Não é necessário validação de NONCE pois quando a function é chamada ela faz a validação.
			if ( isset( $input_post['product_id'] ) && isset( $input_post['current_qty'] ) ) {
				$product_id       = sanitize_text_field( wp_unslash( $input_post['product_id'] ) );
				$product_quantity = sanitize_text_field( wp_unslash( $input_post['current_qty'] ) );

				$product_quantity = ( $product_quantity > 0 ) ? intval( $product_quantity ) : 1;

				$products = $this->getProductFromPage( $product_id, $product_quantity );
			} else {
				$products = $this->getProductsFromCart();
			}

			$package['config']       = $this->settings;
			$from_postal_code        = ( isset( $_SESSION['superfrete']['address']['postal_code'] ) ) ? sanitize_text_field( wp_unslash( $_SESSION['superfrete']['address']['postal_code'] ) ) : $this->settings['superfrete_address_postal_code'];
			$options_own_hand        = ( isset( $_SESSION['superfrete']['own_hand'] ) ) ? sanitize_text_field( $_SESSION['superfrete']['own_hand'] ) : $this->settings['superfrete_own_hand'];
			$options_receipt         = ( isset( $_SESSION['superfrete']['receipt'] ) ) ? sanitize_text_field( $_SESSION['superfrete']['receipt'] ) : $this->settings['superfrete_receipt'];
			$options_insurance_value = ( isset( $_SESSION['superfrete']['insurance_value'] ) ) ? sanitize_text_field( $_SESSION['superfrete']['insurance_value'] ) : $this->settings['superfrete_insurance_value'];

			$payload = array(
				'from'     => array(
					'postal_code' => str_replace( '-', '', $from_postal_code ),
				),
				'to'       => array(
					'postal_code' => str_replace( '-', '', $to ),
				),
				'services' => '1,2,17',
				'products' => $products,
				'options'  => array(
					'receipt'             => (bool) json_decode( strtolower( $options_receipt ) ),
					'own_hand'            => (bool) json_decode( strtolower( $options_own_hand ) ),
					'use_insurance_value' => (bool) json_decode( strtolower( $options_insurance_value ) ),
					'insurance_value'     => $this->getInsuranceValue( $products ),
				),
			);

			$superfrete_api_path_calculator = '/api/v0/calculator';
			if ( strstr( $this->superfrete_api_url, 'localhost' ) || strstr( $this->superfrete_api_url, 'test' ) ) {
				$superfrete_api_path_calculator = '/apiIntegrationV1Calculator/api/v0/calculator';
			}

			$headers = array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->settings['superfrete_token'],
				'Platform'      => 'Woocommerce SuperFrete',
			);
			$params  = array(
				'headers'  => $headers,
				'method'   => 'POST',
				'body'     => wp_json_encode( $payload ),
				'timeout ' => 600,
			);

			$result = wp_remote_post( $this->superfrete_api_url . $superfrete_api_path_calculator, $params );

			wc_clear_notices();

			if ( 200 === $result['response']['code'] ) {
					$fretes = json_decode( $result['body'], true );

				if ( $fretes && is_array( $fretes ) ) {

					if ( ( isset( $fretes[1]['id'] ) && 17 === $fretes[1]['id'] ) || ( isset( $fretes[2]['id'] ) && 17 === $fretes[2]['id'] ) ) {
						$frete_arr_bkp = $fretes;
						if ( 17 === $fretes[2]['id'] ) {
							$fretes[0] = $fretes[2];
							$fretes[1] = $frete_arr_bkp[0];
							$fretes[2] = $frete_arr_bkp[1];
						} elseif ( 17 === $fretes[1]['id'] ) {
								$fretes[0] = $fretes[1];
								$fretes[1] = $frete_arr_bkp[0];
						}
					}

					foreach ( $fretes as $frete ) {
						if ( isset( $frete['error'] ) ) {
							continue;
						}

						if ( $this->settings['superfrete_prazo_adicional'] > 0 ) {
							$delivery_range_min = ( $frete['delivery_range']['min'] ) ? $frete['delivery_range']['min'] : 1;
							$delivery_range_max = ( intval( $frete['delivery_range']['max'] ) + intval( $this->settings['superfrete_prazo_adicional'] ) );
						} else {
							$delivery_range_min = $frete['delivery_range']['min'];
							$delivery_range_max = $frete['delivery_range']['max'];
						}
						$final_frete_name_label_dias = ( $delivery_range_max > 1 ) ? ' dias úteis' : ' dia útil';
						$final_frete_name            = "{$frete['name']} ( até {$delivery_range_max}{$final_frete_name_label_dias} )";
						$final_frete_price           = $frete['price'];
						if ( 'percent' === $this->settings['superfrete_tipo_valor_adicional'] &&
								! empty( $this->settings['superfrete_valor_adicional'] ) ) {
							$final_frete_price = $this->moneyHelperPrice( $frete['price'], 0, $this->settings['superfrete_valor_adicional'] );
						}
						if ( 'fix' === $this->settings['superfrete_tipo_valor_adicional'] &&
								! empty( $this->settings['superfrete_valor_adicional'] ) ) {
							$superfrete_valor_adicional = $this->moneyHelperFloatConverter( $this->settings['superfrete_valor_adicional'] );
							$final_frete_price          = $this->moneyHelperPrice( $frete['price'], $superfrete_valor_adicional, 0 );
						}
						$my_frete = array(
							'id'        => $frete['id'],
							'method_id' => $frete['id'],
							'label'     => 'SuperFrete ' . $final_frete_name,
							'cost'      => 'SuperFrete ' . trim( str_replace( 'R$', '', $this->moneyHelperFloatConverter( $final_frete_price ) ) ),
							'taxes'     => '',
							'calc_tax'  => 'per_order',
							'meta_data' => array(),
							'package'   => $frete['packages'][0],
						);

						$this->add_rate( $my_frete );
					}
				}
			}
		}
	}
}

add_action( 'woocommerce_shipping_init', 'superfrete_shipping_method' );
/**
 * Add Superfrete shipping method to WooCommerce.
 *
 * @param array $methods Existing shipping methods.
 * @return array Modified shipping methods.
 */
function superfrete_shipping_method_include( $methods ) {
	$methods[] = 'Superfrete_Shipping_Method';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'superfrete_shipping_method_include' );
