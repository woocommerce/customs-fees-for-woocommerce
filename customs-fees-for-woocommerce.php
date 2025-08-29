<?php
/**
 * Plugin Name:       Customs Fees for WooCommerce
 * Plugin URI:        https://woocommerce.com
 * Description:       Add customs and import fees to WooCommerce orders based on destination country and product origin.
 * Version: 1.0.0
 * Author:            WooCommerce
 * Author URI:        https://woocommerce.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       customs-fees-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 9.0.0
 * WC tested up to: 10.1.2
 *
 * @package CustomsFeesForWooCommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CFWC_VERSION', '1.0.0' );
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
	return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )
		|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) );
}

/**
 * Display admin notice if WooCommerce is not active.
 *
 * @since 1.0.0
 */
function cfwc_woocommerce_not_active_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Customs Fees for WooCommerce requires WooCommerce to be installed and active.', 'customs-fees-for-woocommerce' ); ?></p>
	</div>
	<?php
}

// Check if WooCommerce is active before loading the plugin.
if ( ! cfwc_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'cfwc_woocommerce_not_active_notice' );
	return;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class Customs_Fees_WooCommerce {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Customs_Fees_WooCommerce
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return Customs_Fees_WooCommerce
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
		$this->load_dependencies();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Load plugin textdomain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Add fees to cart.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_customs_fees' ) );

		// Add plugin action links.
		add_filter( 'plugin_action_links_' . CFWC_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// Add tooltip to cart/checkout.
		add_action( 'wp_footer', array( $this, 'add_tooltip_script' ) );

		// HPOS compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
	}

	/**
	 * Load dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Load core classes.
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-settings.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-calculator.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-products.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-display.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-emails.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-loader.php';
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-templates.php';
		
		// Load admin classes if in admin.
		if ( is_admin() ) {
			require_once CFWC_PLUGIN_DIR . 'includes/admin/class-cfwc-admin.php';
			$admin = new CFWC_Admin();
			$admin->init();
		}

		// Initialize classes.
		$settings = new CFWC_Settings();
		$settings->init();

		// Products class doesn't have init() - just instantiate it
		$products = new CFWC_Products();

		$display = new CFWC_Display();
		$display->init();

		$emails = new CFWC_Emails();
		$emails->init();

		$loader = new CFWC_Loader();
		$loader->init();
		
			// Initialize templates to register AJAX handlers
	$templates = new CFWC_Templates();
	$templates->init();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 * @deprecated 1.0.1 WordPress automatically loads translations for plugins hosted on WordPress.org since 4.6
	 */
	public function load_textdomain() {
		// WordPress 4.6+ automatically loads translations for plugins hosted on WordPress.org.
		// This method is kept empty for backward compatibility.
		// Translations are loaded automatically by WordPress.
	}

	/**
	 * Declare HPOS compatibility.
	 *
	 * @since 1.0.0
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
			
			// Also declare cart/checkout blocks compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}

	/**
	 * Add customs fees to cart.
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart Cart object.
	 */
	public function add_customs_fees( $cart ) {
		// Don't add fees in admin or if cart is empty.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( $cart->is_empty() ) {
			return;
		}

		// Get calculator instance.
		$calculator = new CFWC_Calculator();
		$calculator->init();

		// Calculate fees.
		$fees = $calculator->calculate_fees( $cart );

		// Debug logging for calculated fees.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
			error_log( 'CFWC Calculated Fees: ' . wp_json_encode( $fees ) );
		}

		if ( empty( $fees ) ) {
			return;
		}

		// Use default translatable tooltip text.
		if ( class_exists( 'CFWC_Settings' ) ) {
			$tooltip_text = CFWC_Settings::get_default_help_text();
			WC()->session->set( 'cfwc_tooltip_text', $tooltip_text );
		}
		
		// Always show detailed breakdown - add each fee separately
		// Make each fee truly unique by adding a counter to the label
		$fee_counter = 0;
		$fees_by_label = array();
		
		// Group fees by label to handle duplicates
		foreach ( $fees as $fee ) {
			$base_label = $fee['label'];
			if ( ! isset( $fees_by_label[ $base_label ] ) ) {
				$fees_by_label[ $base_label ] = 0;
			}
			$fees_by_label[ $base_label ]++;
			
			// For duplicate labels, append a number
			if ( $fees_by_label[ $base_label ] > 1 ) {
				$unique_label = $base_label . ' (' . $fees_by_label[ $base_label ] . ')';
			} else {
				$unique_label = $base_label;
			}
			
			$fee_counter++;
			
			// Add the fee with truly unique label
			WC()->cart->add_fee(
				$unique_label,
				$fee['amount'],
				isset( $fee['taxable'] ) ? $fee['taxable'] : true,
				isset( $fee['tax_class'] ) ? $fee['tax_class'] : ''
			);
			
			// Debug log each fee being added
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
				error_log( sprintf( 'CFWC Adding Fee #%d: %s = %s (base: %s)', $fee_counter, $unique_label, $fee['amount'], $base_label ) );
			}
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 1.0.0
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' ) . '">' . esc_html__( 'Settings', 'customs-fees-for-woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add tooltip script to frontend.
	 *
	 * @since 1.0.0
	 */
	public function add_tooltip_script() {
		// Only on cart and checkout pages.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		// Check if we should show tooltip.
		$show_tooltip = get_option( 'cfwc_show_tooltip', true );
		if ( ! $show_tooltip ) {
			return;
		}

		// Get tooltip text from session first (it's set during cart calculation).
		$tooltip_text = '';
		if ( WC()->session ) {
			$tooltip_text = WC()->session->get( 'cfwc_tooltip_text', '' );
		}
		
		// Fallback to default translatable text if not in session.
		if ( empty( $tooltip_text ) && class_exists( 'CFWC_Settings' ) ) {
			$tooltip_text = CFWC_Settings::get_default_help_text();
		}
		
		if ( empty( $tooltip_text ) ) {
			return;
		}
		?>
		<style>
		.cfwc-tooltip {
			position: relative;
			display: inline-block;
			margin-left: 5px;
			cursor: help;
		}
		.cfwc-tooltip-icon {
			display: inline-block;
			width: 16px;
			height: 16px;
			line-height: 16px;
			text-align: center;
			background: #999;
			color: #fff;
			border-radius: 50%;
			font-size: 12px;
			font-weight: bold;
		}
		.cfwc-tooltip-text {
			visibility: hidden;
			width: 250px;
			background-color: #333;
			color: #fff;
			text-align: left;
			border-radius: 6px;
			padding: 8px 10px;
			position: absolute;
			z-index: 1000;
			bottom: 125%;
			left: 50%;
			margin-left: -125px;
			opacity: 0;
			transition: opacity 0.3s;
			font-size: 13px;
			line-height: 1.4;
		}
		.cfwc-tooltip:hover .cfwc-tooltip-text {
			visibility: visible;
			opacity: 1;
		}
		.cfwc-tooltip-text::after {
			content: "";
			position: absolute;
			top: 100%;
			left: 50%;
			margin-left: -5px;
			border-width: 5px;
			border-style: solid;
			border-color: #333 transparent transparent transparent;
		}
		@media (max-width: 768px) {
			.cfwc-tooltip-text {
				width: 200px;
				margin-left: -100px;
			}
		}
		</style>
		<script>
		jQuery(document).ready(function($) {
			// Add tooltip to customs fee labels.
			function addCustomsTooltips() {
				// For cart and checkout fee rows.
				$('tr.fee').each(function() {
					var $label = $(this).find('th, td').first();
					var labelText = $label.text();
					
					// Check if this is a customs fee and doesn't already have tooltip.
					if ((labelText.indexOf('Customs') > -1 || labelText.indexOf('Import') > -1 || labelText.indexOf('Duty') > -1) 
						&& !$label.find('.cfwc-tooltip').length) {
						
						// Clean the label text by removing zero-width spaces.
						var cleanLabel = labelText.replace(/\u200B/g, '');
						
						$label.html(cleanLabel + ' <span class="cfwc-tooltip"><span class="cfwc-tooltip-icon">?</span><span class="cfwc-tooltip-text"><?php echo esc_js( $tooltip_text ); ?></span></span>');
					}
				});
			}
			
			// Initial load.
			addCustomsTooltips();
			
			// After cart/checkout updates.
			$(document.body).on('updated_cart_totals updated_checkout', function() {
				addCustomsTooltips();
			});
		});
		</script>
		<?php
	}
}

/**
 * Activation hook.
 *
 * @since 1.0.0
 */
function cfwc_activate() {
	// Set default options.
	cfwc_set_default_options();

	// Clear transients.
	delete_transient( 'cfwc_rules_cache' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cfwc_activate' );

/**
 * Set default options.
 *
 * @since 1.0.0
 */
function cfwc_set_default_options() {
	// Main settings.
	add_option( 'cfwc_rules', array() );

	// Version tracking.
	add_option( 'cfwc_version', CFWC_VERSION );
}

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function cfwc_deactivate() {
	// Clear transients.
	delete_transient( 'cfwc_rules_cache' );

	// Clear scheduled events if any.
	wp_clear_scheduled_hook( 'cfwc_daily_cleanup' );
}
register_deactivation_hook( __FILE__, 'cfwc_deactivate' );

/**
 * Uninstall hook.
 *
 * @since 1.0.0
 */
function cfwc_uninstall() {
	// Remove options.
	delete_option( 'cfwc_rules' );
	delete_option( 'cfwc_version' );
	delete_option( 'cfwc_display_mode' );

	// Remove transients.
	delete_transient( 'cfwc_rules_cache' );

	// Remove product meta - using direct queries for complete cleanup during uninstall.
	// Note: These queries use meta_key which can be slower, but this is acceptable
	// for uninstall operations which only run once when the plugin is deleted.
	global $wpdb;
	
	// Remove HS code meta
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_cfwc_hs_code' ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	
	// Remove country of origin meta
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_cfwc_country_of_origin' ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	
	// Clear any cached data
	wp_cache_flush();
}
register_uninstall_hook( __FILE__, 'cfwc_uninstall' );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function cfwc_init() {
	Customs_Fees_WooCommerce::get_instance();
}
add_action( 'plugins_loaded', 'cfwc_init' );

/**
 * Add tooltip to frontend.
 *
 * @since 1.0.0
 */
function cfwc_add_frontend_tooltip() {
	// Only on cart and checkout pages.
	if ( ! is_cart() && ! is_checkout() ) {
		return;
	}

	// Get tooltip text from session first (it's set during cart calculation).
	$tooltip_text = '';
	if ( WC()->session ) {
		$tooltip_text = WC()->session->get( 'cfwc_tooltip_text', '' );
	}
	
	// Fallback to default translatable text if not in session.
	if ( empty( $tooltip_text ) && class_exists( 'CFWC_Settings' ) ) {
		$tooltip_text = CFWC_Settings::get_default_help_text();
	}
	
	if ( empty( $tooltip_text ) ) {
		return;
	}
	?>
	<style>
	.cfwc-tooltip {
		position: relative;
		display: inline-block;
		margin-left: 5px;
	}
	.cfwc-tooltip .cfwc-tooltiptext {
		visibility: hidden;
		width: 250px;
		background-color: #333;
		color: #fff;
		text-align: left;
		border-radius: 6px;
		padding: 8px 10px;
		position: absolute;
		z-index: 1;
		bottom: 125%;
		left: 50%;
		margin-left: -125px;
		opacity: 0;
		transition: opacity 0.3s;
	}
	.cfwc-tooltip:hover .cfwc-tooltiptext {
		visibility: visible;
		opacity: 1;
	}
	.cfwc-tooltip .cfwc-tooltiptext::after {
		content: "";
		position: absolute;
		top: 100%;
		left: 50%;
		margin-left: -5px;
		border-width: 5px;
		border-style: solid;
		border-color: #333 transparent transparent transparent;
	}
	</style>
	<?php
}
add_action( 'wp_footer', 'cfwc_add_frontend_tooltip' );