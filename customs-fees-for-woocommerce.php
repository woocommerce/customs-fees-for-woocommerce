<?php
/**
 * Plugin Name:       Customs Fees for WooCommerce
 * Plugin URI:        https://woocommerce.com/products/customs-fees
 * Description:       Add customs and import fees to WooCommerce orders based on destination country and product origin.
 * Version:           1.1.1
 * Author:            WooCommerce
 * Author URI:        https://woocommerce.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       customs-fees-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * Requires at least: 6.0
 * Tested up to:      6.8
 * Requires PHP:      7.4
 * WC requires at least: 9.0.0
 * WC tested up to:   10.1.2
 *
 * @package CustomsFeesForWooCommerce
 */

/**
 * Bootstrap file for Customs Fees for WooCommerce.
 *
 * Loads the main plugin class and registers activation/deactivation hooks.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CFWC_VERSION', '1.1.1' );
define( 'CFWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CFWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CFWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CFWC_PLUGIN_FILE', __FILE__ );

// Load main plugin class file.
require_once CFWC_PLUGIN_DIR . 'includes/class-customs-fees-woocommerce.php';

/**
 * Returns the main instance of Customs_Fees_WooCommerce.
 *
 * This is a helper for backwards compatibility and convenient access.
 *
 * @since 1.0.0
 * @return Customs_Fees_WooCommerce Main instance.
 */
function cfwc() {
	return Customs_Fees_WooCommerce::instance();
}

// Hook registrations.
add_action( 'plugins_loaded', array( 'Customs_Fees_WooCommerce', 'plugin_init' ), 10 );

// Register lifecycle hooks.
register_activation_hook( __FILE__, array( 'Customs_Fees_WooCommerce', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Customs_Fees_WooCommerce', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Customs_Fees_WooCommerce', 'uninstall' ) );
