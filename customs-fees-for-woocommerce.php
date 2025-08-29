<?php
/**
 * Plugin Name: Customs Fees for WooCommerce
 * Plugin URI: https://woocommerce.com
 * Description: Add transparent customs and import fee calculations to WooCommerce checkout. Simple fee tables similar to tax settings, no complex API integrations required.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: customs-fees-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 9.0
 * WC tested up to: 10.1.2
 * Requires Plugins: woocommerce
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CFWC_VERSION', '1.0.0' );
define( 'CFWC_PLUGIN_FILE', __FILE__ );
define( 'CFWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CFWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CFWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load main plugin class.
require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-plugin.php';

/**
 * Back-compat: Check if WooCommerce is active.
 *
 * @since 1.0.0
 * @return bool
 */
function cfwc_is_woocommerce_active() {
	return CFWC_Plugin::is_woocommerce_active();
}

/**
 * Display admin notice if WooCommerce is not active.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin name */
					__( '<strong>Customs Fees for WooCommerce</strong> requires %s to be installed and activated.', 'customs-fees-for-woocommerce' ),
					'<strong>WooCommerce</strong>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Global wrapper: plugins_loaded bootstrap.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_init() {
	CFWC_Plugin::instance()->bootstrap();
}
add_action( 'plugins_loaded', 'cfwc_init' );

/**
 * Declare HPOS and WooCommerce feature compatibility.
 * This MUST be hooked directly to before_woocommerce_init, not called from within another function.
 *
 * @since 1.0.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			// High-Performance Order Storage (HPOS) compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CFWC_PLUGIN_FILE, true );

			// Cart and Checkout Blocks compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CFWC_PLUGIN_FILE, true );

			// Product Block Editor compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', CFWC_PLUGIN_FILE, true );
		}
	}
);

/**
 * Global wrapper: WooCommerce specific initialization.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_woocommerce_init() {
	CFWC_Plugin::instance()->woocommerce_init();
}

/**
 * Global wrapper: Calculate customs fees (keeps hook name stable).
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_calculate_customs_fees() {
	CFWC_Plugin::instance()->calculate_customs_fees();
}

/**
 * Activation hook.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_activate() {
	CFWC_Plugin::instance()->activate();
}
register_activation_hook( CFWC_PLUGIN_FILE, 'cfwc_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_deactivate() {
	CFWC_Plugin::instance()->deactivate();
}
register_deactivation_hook( CFWC_PLUGIN_FILE, 'cfwc_deactivate' );

/**
 * Global wrapper: Set default plugin options.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_set_default_options() {
	CFWC_Plugin::instance()->set_default_options();
}

/**
 * Global wrapper: Create database tables if needed.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_create_database_tables() {
	CFWC_Plugin::instance()->create_database_tables();
}

/**
 * Global wrapper: Plugin action links.
 *
 * @since 1.0.0
 * @param array $links Plugin action links.
 * @return array
 */
function cfwc_plugin_action_links( $links ) {
	return CFWC_Plugin::instance()->plugin_action_links( $links );
}

/**
 * Global wrapper: Plugin meta links.
 *
 * @since 1.0.0
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file.
 * @return array
 */
function cfwc_plugin_row_meta( $links, $file ) {
	return CFWC_Plugin::instance()->plugin_row_meta( $links, $file );
}

/**
 * Global wrapper: Add tooltip script.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_add_tooltip_script() {
	CFWC_Plugin::instance()->add_tooltip_script();
}

/**
 * Global wrapper: Show activation notice.
 *
 * @since 1.0.0
 * @return void
 */
function cfwc_activation_notice() {
	CFWC_Plugin::instance()->activation_notice();
}
