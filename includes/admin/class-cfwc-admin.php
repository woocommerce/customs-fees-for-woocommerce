<?php
/**
 * Admin functionality handler.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Admin class.
 *
 * Handles admin-specific functionality.
 *
 * @since 1.0.0
 */
class CFWC_Admin {

	/**
	 * Initialize the admin handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Add admin menu items.
		add_action( 'admin_menu', array( $this, 'add_menu_items' ), 99 );
		
		// Add admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		
		// Add order admin meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_order_meta_boxes' ) );
		
		// Admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add product fields.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );
	}

	/**
	 * Add menu items.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_items() {
		// Quick access submenu under WooCommerce.
		add_submenu_page(
			'woocommerce',
			__( 'Customs Fees', 'customs-fees-for-woocommerce' ),
			__( 'Customs Fees', 'customs-fees-for-woocommerce' ),
			'manage_woocommerce',
			'wc-settings&tab=customs_fees',
			''
		);
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		// Check if we just activated.
		if ( get_transient( 'cfwc_activated' ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'Customs Fees for WooCommerce is activated! %s to configure your customs fee rules.', 'customs-fees-for-woocommerce' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=customs_fees' ) ) . '">' . esc_html__( 'Visit settings', 'customs-fees-for-woocommerce' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
			delete_transient( 'cfwc_activated' );
		}

		// Check if customs fees are disabled.
		$screen = get_current_screen();
		if ( $screen && 'shop_order' === $screen->id && ! get_option( 'cfwc_enabled', false ) ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'Customs fees are currently disabled. %s to enable and configure.', 'customs-fees-for-woocommerce' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=customs_fees' ) ) . '">' . esc_html__( 'Enable customs fees', 'customs-fees-for-woocommerce' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add order meta boxes.
	 *
	 * @since 1.0.0
	 */
	public function add_order_meta_boxes() {
		$screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'cfwc_order_customs_fees',
			__( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ),
			array( $this, 'render_order_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render order meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post|WC_Order $post_or_order Post or Order object.
	 */
	public function render_order_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		
		if ( ! $order ) {
			return;
		}

		$fees       = $order->get_fees();
		$total_fees = 0;
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$total_fees += $fee->get_total();
			}
		}

		if ( $total_fees > 0 ) {
			echo '<p><strong>' . esc_html__( 'Total Customs Fees:', 'customs-fees-for-woocommerce' ) . '</strong> ' . wp_kses_post( wc_price( $total_fees ) ) . '</p>';
			echo '<p class="description">' . esc_html__( 'These fees were calculated based on the destination country and cart value.', 'customs-fees-for-woocommerce' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'No customs fees applied to this order.', 'customs-fees-for-woocommerce' ) . '</p>';
		}
	}

	/**
	 * Add product fields for HS codes.
	 *
	 * @since 1.0.0
	 */
	public function add_product_fields() {
		echo '<div class="options_group">';
		
		woocommerce_wp_text_input( array(
			'id'          => '_cfwc_hs_code',
			'label'       => __( 'HS Code', 'customs-fees-for-woocommerce' ),
			'placeholder' => __( 'e.g., 6109.10', 'customs-fees-for-woocommerce' ),
			'desc_tip'    => true,
			'description' => __( 'Harmonized System code for customs classification.', 'customs-fees-for-woocommerce' ),
		) );
		
		woocommerce_wp_text_input( array(
			'id'          => '_cfwc_country_of_origin',
			'label'       => __( 'Country of Origin', 'customs-fees-for-woocommerce' ),
			'placeholder' => __( 'e.g., CN, US, GB', 'customs-fees-for-woocommerce' ),
			'desc_tip'    => true,
			'description' => __( 'Two-letter country code where the product was manufactured.', 'customs-fees-for-woocommerce' ),
		) );
		
		echo '</div>';
	}

	/**
	 * Save product fields.
	 *
	 * @since 1.0.0
	 * @param int $post_id Product ID.
	 */
	public function save_product_fields( $post_id ) {
		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Verify WooCommerce product save nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles the nonce for product saves
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$hs_code = isset( $_POST['_cfwc_hs_code'] ) ? sanitize_text_field( wp_unslash( $_POST['_cfwc_hs_code'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$country = isset( $_POST['_cfwc_country_of_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['_cfwc_country_of_origin'] ) ) : '';
		
		update_post_meta( $post_id, '_cfwc_hs_code', $hs_code );
		update_post_meta( $post_id, '_cfwc_country_of_origin', strtoupper( substr( $country, 0, 2 ) ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on relevant pages.
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Check if we're on the customs fees tab.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tab'] ) || 'customs_fees' !== $_GET['tab'] ) {
			return;
		}

		// Add inline styles for now.
		wp_add_inline_style( 'woocommerce_admin_styles', '
			.cfwc-rules-table { width: 100%; margin-top: 20px; }
			.cfwc-rules-table th { text-align: left; }
			.cfwc-rules-table .button-small { margin: 0 5px; }
			.cfwc-template-selector { margin: 20px 0; padding: 15px; background: #f7f7f7; border-radius: 4px; }
			.cfwc-template-selector select { min-width: 300px; }
		' );

		// Add inline script for interactivity.
		wp_add_inline_script( 'jquery', '
			jQuery(document).ready(function($) {
				// Template selector handler.
				$("#cfwc-apply-template").on("click", function(e) {
					e.preventDefault();
					var template = $("#cfwc-template-select").val();
					if (template) {
						if (confirm("' . esc_js( __( 'Apply this template? This will replace your current rules.', 'customs-fees-for-woocommerce' ) ) . '")) {
							// Template application would be handled here via AJAX.
							console.log("Applying template: " + template);
						}
					}
				});
			});
		' );
	}
}
