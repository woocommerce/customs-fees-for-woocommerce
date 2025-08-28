<?php
/**
 * Plugin loader class.
 *
 * Handles the plugin initialization and hook registration.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Loader class.
 *
 * @since 1.0.0
 */
class CFWC_Loader {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var CFWC_Loader|null
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return CFWC_Loader Main instance.
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
	public function __construct() {
		// Constructor intentionally left empty.
		// Use init() method for initialization.
	}

	/**
	 * Initialize the loader.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Register hooks.
		$this->register_hooks();

		// Load dependencies if needed.
		$this->load_dependencies();
	}

	/**
	 * Register plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Admin hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX hooks.
		add_action( 'wp_ajax_cfwc_save_rules', array( $this, 'ajax_save_rules' ) );
		add_action( 'wp_ajax_cfwc_delete_rule', array( $this, 'ajax_delete_rule' ) );
		add_action( 'wp_ajax_cfwc_load_template', array( $this, 'ajax_load_template' ) );

		// Add custom cron schedules if needed.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Load any third-party libraries if needed.
		// Currently no external dependencies.
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_assets() {
		// Only load on cart and checkout pages.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		// Check if fees are enabled.
		if ( ! get_option( 'cfwc_enabled', false ) ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'cfwc-frontend',
			CFWC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			CFWC_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'cfwc-frontend',
			CFWC_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			CFWC_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'cfwc-frontend',
			'cfwc_params',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'cfwc-frontend' ),
				'tooltip_text'      => cfwc_get_tooltip_text(),
				'show_tooltip'      => get_option( 'cfwc_show_tooltip', true ),
				'require_agreement' => get_option( 'cfwc_require_agreement', true ),
				'disclaimer_text'   => cfwc_get_disclaimer_text(),
				'i18n'              => array(
					'fee_details' => __( 'View fee details', 'customs-fees-for-woocommerce' ),
					'loading'     => __( 'Loading...', 'customs-fees-for-woocommerce' ),
					'error'       => __( 'An error occurred. Please try again.', 'customs-fees-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on WooCommerce settings page.
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

		// Check if we're on our settings tab.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tab'] ) || 'cfwc' !== $_GET['tab'] ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'cfwc-admin',
			CFWC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CFWC_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'cfwc-admin',
			CFWC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-sortable' ),
			CFWC_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'cfwc-admin',
			'cfwc_admin',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'cfwc-admin' ),
				'countries' => WC()->countries->get_countries(),
				'currency'  => get_woocommerce_currency_symbol(),
				'templates' => $this->get_available_templates(),
				'i18n'      => array(
					'confirm_delete'   => __( 'Are you sure you want to delete this rule?', 'customs-fees-for-woocommerce' ),
					'confirm_template' => __( 'This will replace all existing rules. Continue?', 'customs-fees-for-woocommerce' ),
					'saving'           => __( 'Saving...', 'customs-fees-for-woocommerce' ),
					'saved'            => __( 'Settings saved!', 'customs-fees-for-woocommerce' ),
					'error'            => __( 'An error occurred. Please try again.', 'customs-fees-for-woocommerce' ),
					'add_rule'         => __( 'Add Rule', 'customs-fees-for-woocommerce' ),
					'edit_rule'        => __( 'Edit Rule', 'customs-fees-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Get available fee templates.
	 *
	 * @since 1.0.0
	 * @return array Available templates.
	 */
	private function get_available_templates() {
		return array(
			'us_general'     => __( 'U.S. General Import (2025 rules)', 'customs-fees-for-woocommerce' ),
			'us_electronics' => __( 'U.S. Electronics Import', 'customs-fees-for-woocommerce' ),
			'us_textiles'    => __( 'U.S. Textiles & Apparel', 'customs-fees-for-woocommerce' ),
			'eu_to_us'       => __( 'EU to U.S. Standard', 'customs-fees-for-woocommerce' ),
			'uk_to_us'       => __( 'UK to U.S. Standard', 'customs-fees-for-woocommerce' ),
			'simplified'     => __( 'Simple 10% All Countries', 'customs-fees-for-woocommerce' ),
		);
	}

	/**
	 * AJAX handler for saving rules.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_rules() {
		// Security checks will be handled in the AJAX class.
		wp_die( 'Handler not implemented yet.' );
	}

	/**
	 * AJAX handler for deleting a rule.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_rule() {
		// Security checks will be handled in the AJAX class.
		wp_die( 'Handler not implemented yet.' );
	}

	/**
	 * AJAX handler for loading a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_load_template() {
		// Security checks will be handled in the AJAX class.
		wp_die( 'Handler not implemented yet.' );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @since 1.0.0
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_cron_schedules( $schedules ) {
		// Add weekly schedule if not exists.
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'customs-fees-for-woocommerce' ),
			);
		}

		return $schedules;
	}
}
