<?php
/**
 * Main plugin class for Customs Fees for WooCommerce.
 *
 * Centralizes bootstrap, includes, hooks and utilities.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class: centralizes bootstrap, includes, hooks and utilities.
 *
 * Thin global wrapper functions in the main plugin file delegate to this class to keep
 * backward-compatibility with action/filter names and reduce global scope.
 *
 * @since 1.0.0
 */
final class CFWC_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var CFWC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return CFWC_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hidden constructor.
	 */
	private function __construct() {}

	/**
	 * Bootstrap plugin after plugins_loaded.
	 *
	 * - Validate WooCommerce availability
	 * - Include files and initialize components
	 * - Register Woo-specific hooks via global wrapper for BC
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function bootstrap() {
		// Check if WooCommerce is active.
		if ( ! self::is_woocommerce_active() ) {
			add_action( 'admin_notices', 'cfwc_woocommerce_missing_notice' );
			return;
		}

		// Note: For plugins hosted on WordPress.org, translations are loaded automatically
		// since WordPress 4.6. No need to call load_plugin_textdomain().

		// Include required files and init components.
		$this->includes();
		$this->initialize_components();

		// Hooks are now registered centrally in CFWC_Loader.
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Include required plugin files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function includes() {
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

		// Product HS Code support.
		require_once CFWC_PLUGIN_DIR . 'includes/class-cfwc-products.php';
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize_components() {
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

		// Initialize product HS Code support.
		// Note: Products class currently has no init.
		new CFWC_Products();
	}

	/**
	 * WooCommerce specific initialization.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function woocommerce_init() {
		// No-op: hooks are registered via CFWC_Loader::register_hooks().
	}

	/**
	 * Calculate customs fees for the cart.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function calculate_customs_fees() {
		// Skip if disabled or in admin without AJAX.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Get the calculator instance and calculate fees.
		$calculator = new CFWC_Calculator();

		// Clear cache to ensure fresh rules.
		$calculator->clear_cache();

		$fees = $calculator->calculate_fees( WC()->cart );

		// Add fees to cart if any.
		if ( ! empty( $fees ) ) {
			// Get display mode setting - fresh from database.
			$display_mode = get_option( 'cfwc_display_mode', 'single' );

			// Debug logging for display mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
				error_log( 'CFWC Display Mode: ' . $display_mode . ', Fees count: ' . count( $fees ) );
			}

			// Store tooltip text in session for display.
			$tooltip_text = get_option( 'cfwc_tooltip_text', '' );
			if ( ! empty( $tooltip_text ) && WC()->session ) {
				WC()->session->set( 'cfwc_tooltip_text', $tooltip_text );
			}

			if ( 'breakdown' === $display_mode ) {
				// Breakdown display - show each fee separately, but combine same labels.
				$combined_fees = array();

				foreach ( $fees as $fee ) {
					$label = $fee['label'];

					if ( ! isset( $combined_fees[ $label ] ) ) {
						$combined_fees[ $label ] = array(
							'amount'   => 0,
							'taxable'  => isset( $fee['taxable'] ) ? $fee['taxable'] : true,
							'tax_class' => isset( $fee['tax_class'] ) ? $fee['tax_class'] : '',
						);
					}

					$combined_fees[ $label ]['amount'] += $fee['amount'];
				}

				// Add each unique fee to cart.
				foreach ( $combined_fees as $label => $fee_data ) {
					WC()->cart->add_fee(
						$label,
						$fee_data['amount'],
						$fee_data['taxable'],
						$fee_data['tax_class']
					);
				}
			} else {
				// Single line display - combine all fees.
				$total_fee = 0;
				$taxable   = true;
				$tax_class = '';
				$labels    = array();

				foreach ( $fees as $fee ) {
					$total_fee += $fee['amount'];

					// Collect unique labels.
					if ( ! empty( $fee['label'] ) && ! in_array( $fee['label'], $labels, true ) ) {
						$labels[] = $fee['label'];
					}

					// Use first fee's tax settings.
					if ( isset( $fee['taxable'] ) ) {
						$taxable = $fee['taxable'];
					}
					if ( isset( $fee['tax_class'] ) ) {
						$tax_class = $fee['tax_class'];
					}
				}

				// Create combined label from rule labels.
				if ( count( $labels ) > 1 ) {
					// Multiple different fees - use generic label.
					$label = __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' );
				} elseif ( count( $labels ) === 1 ) {
					// Single fee type - use its label.
					$label = $labels[0];
				} else {
					// No labels - fallback.
					$label = __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' );
				}

				// Add combined fee.
				WC()->cart->add_fee(
					$label,
					$total_fee,
					$taxable,
					$tax_class
				);
			}
		}
	}

	/**
	 * Add tooltip script to frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_tooltip_script() {
		// Deprecated: handled via enqueued assets in CFWC_Loader::enqueue_frontend_assets().
		return;
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_default_options() {
		// Main settings.
		add_option( 'cfwc_rules', array() );
		add_option( 'cfwc_display_mode', 'single' ); // single or breakdown.
		// Don't use translations here - they'll be translated when displayed.
		add_option( 'cfwc_tooltip_text', 'Estimated import duties and taxes based on destination country.' );

		// Version tracking.
		add_option( 'cfwc_version', CFWC_VERSION );
	}

	/**
	 * Create database tables if needed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function create_database_tables() {
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
	 * Activation tasks and environment checks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
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
		$this->set_default_options();

		// Create database tables if needed.
		$this->create_database_tables();

		// Clear any cached data.
		wp_cache_flush();

		// Set activation flag.
		set_transient( 'cfwc_activated', true, 30 );
	}

	/**
	 * Deactivation tasks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		// Clear any scheduled hooks.
		wp_clear_scheduled_hook( 'cfwc_daily_cleanup' );

		// Clear transients.
		delete_transient( 'cfwc_activated' );

		// Clear cache.
		wp_cache_flush();
	}

	/**
	 * Filter: Plugin action links (Settings link).
	 *
	 * @since 1.0.0
	 * @param array $links Plugin links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' ) ),
			esc_html__( 'Settings', 'customs-fees-for-woocommerce' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Filter: Plugin row meta (Docs/Support).
	 *
	 * @since 1.0.0
	 * @param array  $links Links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( CFWC_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$row_meta = array(
			'docs'    => sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( 'https://github.com/shameemreza/customs-fees-for-woocommerce/wiki' ),
				esc_html__( 'Documentation', 'customs-fees-for-woocommerce' )
			),
			'support' => sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( 'https://github.com/shameemreza/customs-fees-for-woocommerce/issues' ),
				esc_html__( 'Support', 'customs-fees-for-woocommerce' )
			),
		);

		return array_merge( $links, $row_meta );
	}

	/**
	 * Admin notice after activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activation_notice() {
		if ( get_transient( 'cfwc_activated' ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Settings page URL */
							__( 'Customs Fees for WooCommerce has been activated! <a href="%s">Configure settings</a> to start adding customs fees to checkout.', 'customs-fees-for-woocommerce' ),
							esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' ) )
						)
					);
					?>
				</p>
			</div>
			<?php
			delete_transient( 'cfwc_activated' );
		}
	}
}
