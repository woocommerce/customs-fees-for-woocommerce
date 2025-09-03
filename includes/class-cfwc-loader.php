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
	 * Calculator instance.
	 *
	 * @since 1.0.0
	 * @var CFWC_Calculator|null
	 */
	private $calculator = null;

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
		// Load dependencies first.
		$this->load_dependencies();

		// Initialize classes.
		$this->init_classes();

		// Register hooks.
		$this->register_hooks();
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Get plugin directory with defensive check for PHPStan.
		$plugin_dir = defined( 'CFWC_PLUGIN_DIR' ) ? CFWC_PLUGIN_DIR : plugin_dir_path( __DIR__ );

		// Load core classes.
		require_once $plugin_dir . 'includes/class-cfwc-settings.php';
		require_once $plugin_dir . 'includes/class-cfwc-rule-matcher.php';
		require_once $plugin_dir . 'includes/class-cfwc-calculator.php';
		require_once $plugin_dir . 'includes/class-cfwc-products.php';
		require_once $plugin_dir . 'includes/class-cfwc-display.php';
		require_once $plugin_dir . 'includes/class-cfwc-emails.php';
		require_once $plugin_dir . 'includes/class-cfwc-templates.php';

		// Load admin classes if in admin.
		if ( is_admin() ) {
			require_once $plugin_dir . 'includes/admin/class-cfwc-admin.php';
			// Include export/import handler for CSV functionality.
			require_once $plugin_dir . 'includes/class-cfwc-export-import.php';
			// Include onboarding handler.
			require_once $plugin_dir . 'includes/admin/class-cfwc-onboarding.php';
		}
	}

	/**
	 * Initialize classes.
	 *
	 * @since 1.0.0
	 */
	private function init_classes() {
		// Initialize settings.
		$settings = new CFWC_Settings();
		$settings->init();

		// Initialize products (doesn't have init method).
		new CFWC_Products();

		// Initialize display (constructor handles initialization).
		new CFWC_Display();

		// Initialize emails.
		$emails = new CFWC_Emails();
		$emails->init();

		// Initialize templates.
		$templates = new CFWC_Templates();
		$templates->init();

		// Initialize admin if in admin.
		if ( is_admin() ) {
			$admin = new CFWC_Admin();
			$admin->init();

			// Initialize export/import handler.
			new CFWC_Export_Import();

			// Initialize onboarding handler.
			new CFWC_Onboarding();
		}

		// Initialize calculator for later use.
		$this->calculator = new CFWC_Calculator();
		$this->calculator->init();
	}

	/**
	 * Register plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		// Core plugin hooks.
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		// Cart calculation hooks.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_customs_fees' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . CFWC_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

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
	 * Declare compatibility with WooCommerce features.
	 *
	 * @since 1.0.0
	 */
	public function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			// HPOS compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				CFWC_PLUGIN_FILE,
				true
			);

			// Cart/Checkout blocks compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				CFWC_PLUGIN_FILE,
				true
			);

			// Product block editor compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'product_block_editor',
				CFWC_PLUGIN_FILE,
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

		// Calculate fees using calculator.
		$fees = $this->calculator->calculate_fees( $cart );

		// Debug logging for calculated fees.
		$this->debug_log( 'Calculated Fees', $fees );

		if ( empty( $fees ) ) {
			// Clear the breakdown from session if no fees.
			WC()->session->set( 'cfwc_fees_breakdown', array() );
			return;
		}

		// Store tooltip text in session.
		if ( class_exists( 'CFWC_Settings' ) ) {
			$tooltip_text = CFWC_Settings::get_default_help_text();
			WC()->session->set( 'cfwc_tooltip_text', $tooltip_text );
		}

		// Store the fee breakdown in session for display.
		WC()->session->set( 'cfwc_fees_breakdown', $fees );

		// Calculate total customs fees.
		$total_amount = 0;
		$any_taxable  = false;
		$tax_class    = '';

		foreach ( $fees as $fee ) {
			$total_amount += $fee['amount'];
			if ( isset( $fee['taxable'] ) && $fee['taxable'] ) {
				$any_taxable = true;
			}
			// Use the first tax class found.
			if ( empty( $tax_class ) && isset( $fee['tax_class'] ) ) {
				$tax_class = $fee['tax_class'];
			}
		}

		// Add a single combined fee for all customs.
		WC()->cart->add_fee(
			__( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ),
			$total_amount,
			$any_taxable,
			$tax_class
		);

		// Debug log.
		$this->debug_log(
			'Added Combined Fee',
			array(
				'total'           => $total_amount,
				'breakdown_count' => count( $fees ),
			)
		);
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
	 * Debug logging helper.
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param mixed  $data    Data to log.
	 */
	private function debug_log( $message, $data = null ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$log_message = 'CFWC ' . $message;
			if ( null !== $data ) {
				$log_message .= ': ' . wp_json_encode( $data );
			}
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
			error_log( $log_message );
		}
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
