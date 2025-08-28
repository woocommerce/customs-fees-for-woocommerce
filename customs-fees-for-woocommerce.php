<?php
/**
 * Plugin Name: Customs Fees for WooCommerce
 * Plugin URI: https://github.com/shameemreza/customs-fees-for-woocommerce
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

/**
 * Check if WooCommerce is active.
 *
 * @since 1.0.0
 * @return bool True if WooCommerce is active, false otherwise.
 */
function cfwc_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice if WooCommerce is not active.
 *
 * @since 1.0.0
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
 * Main plugin initialization.
 *
 * @since 1.0.0
 */
function cfwc_init() {
	// Check if WooCommerce is active.
	if ( ! cfwc_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'cfwc_woocommerce_missing_notice' );
		return;
	}

	// Note: For plugins hosted on WordPress.org, translations are loaded automatically
	// since WordPress 4.6. No need to call load_plugin_textdomain().

	// Include required files.
	cfwc_includes();

	// Initialize plugin components.
	cfwc_initialize_components();

	// Hook into WooCommerce.
	add_action( 'woocommerce_init', 'cfwc_woocommerce_init' );
}
add_action( 'plugins_loaded', 'cfwc_init' );

/**
 * Declare HPOS and WooCommerce feature compatibility.
 * This MUST be hooked directly to before_woocommerce_init, not called from within another function.
 *
 * @since 1.0.0
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		// High-Performance Order Storage (HPOS) compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CFWC_PLUGIN_FILE, true );
		
		// Cart and Checkout Blocks compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CFWC_PLUGIN_FILE, true );
		
		// Product Block Editor compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', CFWC_PLUGIN_FILE, true );
	}
} );

/**
 * Include required plugin files.
 *
 * @since 1.0.0
 */
function cfwc_includes() {
	// Core includes.
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-loader.php';
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-settings.php';
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-calculator.php';
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-display.php';
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-templates.php';

	// Admin includes.
	if ( is_admin() ) {
		require_once CFWC_PLUGIN_DIR . 'includes/admin/class-cfwc-admin.php';
		require_once CFWC_PLUGIN_DIR . 'includes/admin/class-cfwc-ajax.php';
	}

	// Block editor support (if WooCommerce Blocks is active).
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
		require_once CFWC_PLUGIN_DIR . 'includes/blocks/class-cfwc-blocks.php';
	}

	// Email customizations.
	require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-emails.php';
}

/**
 * Initialize plugin components.
 *
 * @since 1.0.0
 */
function cfwc_initialize_components() {
	// Initialize main loader.
	$loader = new CFWC_Loader();
	$loader->init();

	// Initialize settings.
	$settings = new CFWC_Settings();
	$settings->init();

	// Initialize calculator.
	$calculator = new CFWC_Calculator();
	$calculator->init();

	// Initialize display handler.
	$display = new CFWC_Display();
	$display->init();

	// Initialize templates.
	$templates = new CFWC_Templates();
	$templates->init();

	// Initialize admin if in admin area.
	if ( is_admin() ) {
		$admin = new CFWC_Admin();
		$admin->init();

		// Initialize AJAX handlers.
		$ajax = new CFWC_Ajax();
		$ajax->init();
	}

	// Initialize block support if available.
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
		$blocks = new CFWC_Blocks();
		$blocks->init();
	}

	// Initialize email customizations.
	$emails = new CFWC_Emails();
	$emails->init();
}

/**
 * WooCommerce specific initialization.
 *
 * @since 1.0.0
 */
function cfwc_woocommerce_init() {
	// Add fee calculation to cart.
	add_action( 'woocommerce_cart_calculate_fees', 'cfwc_calculate_customs_fees', 20 );
}

/**
 * Calculate customs fees for the cart.
 *
 * This is the main function that adds customs fees to the cart.
 *
 * @since 1.0.0
 */
function cfwc_calculate_customs_fees() {
	// Skip if disabled or in admin without AJAX.
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Check if fees are enabled.
	$enabled = get_option( 'cfwc_enabled', false );
	if ( ! $enabled ) {
		return;
	}

	// Get the calculator instance and calculate fees.
	$calculator = new CFWC_Calculator();
	$fees = $calculator->calculate_fees( WC()->cart );

	// Add fees to cart if any.
	if ( ! empty( $fees ) ) {
		foreach ( $fees as $fee ) {
			WC()->cart->add_fee(
				$fee['label'],
				$fee['amount'],
				$fee['taxable'],
				$fee['tax_class']
			);
		}
	}
}

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
function cfwc_activate() {
	// Check PHP version.
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( CFWC_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Customs Fees for WooCommerce requires PHP 7.4 or higher.', 'customs-fees-for-woocommerce' ),
			esc_html__( 'Plugin Activation Error', 'customs-fees-for-woocommerce' ),
			array( 'response' => 200, 'back_link' => true )
		);
	}

	// Check WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, '6.0', '<' ) ) {
		deactivate_plugins( CFWC_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Customs Fees for WooCommerce requires WordPress 6.0 or higher.', 'customs-fees-for-woocommerce' ),
			esc_html__( 'Plugin Activation Error', 'customs-fees-for-woocommerce' ),
			array( 'response' => 200, 'back_link' => true )
		);
	}

	// Set default options.
	cfwc_set_default_options();

	// Create database tables if needed.
	cfwc_create_database_tables();

	// Clear any cached data.
	wp_cache_flush();

	// Set activation flag.
	set_transient( 'cfwc_activated', true, 30 );
}
register_activation_hook( CFWC_PLUGIN_FILE, 'cfwc_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
function cfwc_deactivate() {
	// Clear any scheduled hooks.
	wp_clear_scheduled_hook( 'cfwc_daily_cleanup' );

	// Clear transients.
	delete_transient( 'cfwc_activated' );

	// Clear cache.
	wp_cache_flush();
}
register_deactivation_hook( CFWC_PLUGIN_FILE, 'cfwc_deactivate' );

/**
 * Set default plugin options.
 *
 * @since 1.0.0
 */
function cfwc_set_default_options() {
	// Main settings.
	add_option( 'cfwc_enabled', false );
	add_option( 'cfwc_rules', array() );
	add_option( 'cfwc_display_mode', 'single' ); // single or breakdown.
	add_option( 'cfwc_require_agreement', true );
	add_option( 'cfwc_disclaimer_text', __( 'Customs fees are estimates and actual fees at delivery may vary.', 'customs-fees-for-woocommerce' ) );

	// Display settings.
	add_option( 'cfwc_show_tooltip', true );
	add_option( 'cfwc_tooltip_text', __( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' ) );
	add_option( 'cfwc_show_on_cart', true );
	add_option( 'cfwc_show_on_checkout', true );

	// Version tracking.
	add_option( 'cfwc_version', CFWC_VERSION );
}

/**
 * Create database tables if needed.
 *
 * @since 1.0.0
 */
function cfwc_create_database_tables() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'cfwc_logs';
	$charset_collate = $wpdb->get_charset_collate();

	// Create logs table for tracking fee calculations (optional, for analytics).
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		order_id bigint(20) UNSIGNED DEFAULT NULL,
		country varchar(2) NOT NULL,
		cart_total decimal(10,2) NOT NULL,
		fee_amount decimal(10,2) NOT NULL,
		fee_type varchar(20) NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY order_id (order_id),
		KEY country (country),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Add plugin action links.
 *
 * @since 1.0.0
 * @param array $links Plugin action links.
 * @return array Modified plugin action links.
 */
function cfwc_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wc-settings&tab=cfwc' ) ),
		esc_html__( 'Settings', 'customs-fees-for-woocommerce' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . CFWC_PLUGIN_BASENAME, 'cfwc_plugin_action_links' );

/**
 * Add plugin meta links.
 *
 * @since 1.0.0
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file.
 * @return array Modified plugin meta links.
 */
function cfwc_plugin_row_meta( $links, $file ) {
	if ( CFWC_PLUGIN_BASENAME !== $file ) {
		return $links;
	}

	$row_meta = array(
		'docs'    => sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( 'https://github.com/shameemreza/customs-fees-for-woocommerce/wiki' ),
			esc_html__( 'Documentation', 'customs-fees-for-woocommerce' )
		),
		'support' => sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( 'https://github.com/shameemreza/customs-fees-for-woocommerce/issues' ),
			esc_html__( 'Support', 'customs-fees-for-woocommerce' )
		),
	);

	return array_merge( $links, $row_meta );
}
add_filter( 'plugin_row_meta', 'cfwc_plugin_row_meta', 10, 2 );

/**
 * Show activation notice.
 *
 * @since 1.0.0
 */
function cfwc_activation_notice() {
	if ( get_transient( 'cfwc_activated' ) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: Settings page URL */
						__( 'Customs Fees for WooCommerce has been activated! <a href="%s">Configure settings</a> to start adding customs fees to checkout.', 'customs-fees-for-woocommerce' ),
						esc_url( admin_url( 'admin.php?page=wc-settings&tab=cfwc' ) )
					)
				);
				?>
			</p>
		</div>
		<?php
		delete_transient( 'cfwc_activated' );
	}
}
add_action( 'admin_notices', 'cfwc_activation_notice' );