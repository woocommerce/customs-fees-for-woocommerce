<?php
/**
 * Plugin Name:       Customs Fees for WooCommerce
 * Plugin URI:        https://woocommerce.com
 * Description:       Add customs and import fees to WooCommerce orders based on destination country and product origin.
 * Version:           1.0.0
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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CFWC_VERSION', '1.0.0' );
define( 'CFWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CFWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CFWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CFWC_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
final class Customs_Fees_WooCommerce {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Customs_Fees_WooCommerce|null
	 */
	private static $instance = null;

	/**
	 * Plugin loader instance.
	 *
	 * @since 1.0.0
	 * @var CFWC_Loader|null
	 */
	private $loader = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return Customs_Fees_WooCommerce
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
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
		// Check dependencies.
		if ( ! $this->check_dependencies() ) {
			return;
		}

		// Handle activation on admin_init like AutomateWoo does.
		add_action( 'admin_init', array( $this, 'maybe_activate' ), 20 );

		// Initialize the plugin.
		$this->init();
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @since 1.0.0
	 * @return bool True if all dependencies are met.
	 */
	private function check_dependencies() {
		// Check if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0.0
	 * @return bool True if WooCommerce is active.
	 */
	private function is_woocommerce_active() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )
			|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	/**
	 * Display admin notice if WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Customs Fees for WooCommerce requires WooCommerce to be installed and active.', 'customs-fees-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	private function init() {
		// Load the loader class.
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-loader.php';
		
		// Initialize the loader.
		$this->loader = CFWC_Loader::instance();
		$this->loader->init();
	}

	/**
	 * Handle activation setup.
	 *
	 * @since 1.0.0
	 */
	public function maybe_activate() {
		if ( get_option( 'cfwc_activated' ) ) {
			// Set activation time for notice display.
			update_option( 'cfwc_activation_time', time() );
			
			// Set default options.
			if ( false === get_option( 'cfwc_rules' ) ) {
				add_option( 'cfwc_rules', array() );
			}
			if ( false === get_option( 'cfwc_version' ) ) {
				add_option( 'cfwc_version', CFWC_VERSION );
			}
			
			// Clear the activation flag.
			delete_option( 'cfwc_activated' );
		}
	}

	/**
	 * Get the plugin loader.
	 *
	 * @since 1.0.0
	 * @return CFWC_Loader|null The loader instance.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'customs-fees-for-woocommerce' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'customs-fees-for-woocommerce' ), '1.0.0' );
	}
}

/**
 * Returns the main instance of Customs_Fees_WooCommerce.
 *
 * @since 1.0.0
 * @return Customs_Fees_WooCommerce Main instance.
 */
function CFWC() {
	return Customs_Fees_WooCommerce::instance();
}

// Initialize plugin on plugins_loaded to ensure WooCommerce is available.
add_action( 'plugins_loaded', 'cfwc_init', 10 );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function cfwc_init() {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	
	// Global for backwards compatibility.
	$GLOBALS['customs_fees_woocommerce'] = CFWC();
}

/**
 * Plugin activation handler.
 *
 * @since 1.0.0
 */
function cfwc_activate() {
	// Just set activation flag - nothing else!
	add_option( 'cfwc_activated', true );
}
register_activation_hook( __FILE__, 'cfwc_activate' );

/**
 * Plugin deactivation handler.
 *
 * @since 1.0.0
 */
function cfwc_deactivate() {
	// Clear activation time and dismissed notice.
	delete_option( 'cfwc_activation_time' );
	delete_option( 'cfwc_dismissed_setup_notice' );
	
	// Clear cache transient.
	delete_transient( 'cfwc_rules_cache' );
}
register_deactivation_hook( __FILE__, 'cfwc_deactivate' );

/**
 * Plugin uninstall handler.
 *
 * @since 1.0.0
 */
function cfwc_uninstall() {
	// Check if we should remove data.
	$remove_data = apply_filters( 'cfwc_uninstall_remove_data', true );
	
	if ( ! $remove_data ) {
		return;
	}

	// Remove options.
	delete_option( 'cfwc_rules' );
	delete_option( 'cfwc_version' );

	// Remove transients.
	delete_transient( 'cfwc_rules_cache' );

	// Remove product meta - using direct queries for complete cleanup during uninstall.
	// Note: These queries use meta_key which can be slower, but this is acceptable
	// for uninstall operations which only run once when the plugin is deleted.
	global $wpdb;
	
	// Remove HS code meta.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_cfwc_hs_code' ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	
	// Remove country of origin meta.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_cfwc_country_of_origin' ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	
	// Clear any cached data.
	wp_cache_flush();
}
register_uninstall_hook( __FILE__, 'cfwc_uninstall' );

/**
 * Initialize the plugin on plugins_loaded.
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', array( 'Customs_Fees_WooCommerce', 'instance' ), 10 );