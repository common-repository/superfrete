<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php
/**
  * Plugin Name:          SuperFrete
  * Plugin URI:           https://superfrete.com
  * Description:          Plugin de cotação e compra de fretes.
  * Version:              1.1.1
  * Author:               SuperFrete
  * License:              GPLv2 or later
  * Tested up to:         6.6
  * Requires PHP:         7.2
  * WC requires at least: 4.7
  * WC tested up to:      9.1
 */
global $superfrete_plugin_url, $superfrete_plugin_dir;

$superfrete_plugin_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$superfrete_plugin_url = plugins_url(). "/" . basename($superfrete_plugin_dir) . "/";

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
});

include_once $superfrete_plugin_dir . 'includes/class-superfrete-shipping-method.php';
include_once $superfrete_plugin_dir . 'includes/class-superfreteshipping.php';