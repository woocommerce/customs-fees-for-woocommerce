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
		// Add admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		
		// Add order admin meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_order_meta_boxes' ) );
		
		// Admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add product fields to inventory tab for better UX.
		add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_product_fields' ), 15 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );
		
		// Add customs info as order item meta in admin.
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'format_meta_key' ), 10, 3 );
		add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'format_meta_value' ), 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'display_customs_meta_in_order' ), 10, 3 );
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
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' ) ) . '">' . esc_html__( 'Visit settings', 'customs-fees-for-woocommerce' ) . '</a>'
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
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' ) ) . '">' . esc_html__( 'Enable customs fees', 'customs-fees-for-woocommerce' ) . '</a>'
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

		// Display customs fees summary.
		$fees       = $order->get_fees();
		$total_fees = 0;
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ||
			     strpos( strtolower( $fee->get_name() ), 'customs' ) !== false ||
			     strpos( strtolower( $fee->get_name() ), 'import' ) !== false ||
			     strpos( strtolower( $fee->get_name() ), 'duty' ) !== false ) {
				$total_fees += $fee->get_total();
			}
		}

		if ( $total_fees > 0 ) {
			echo '<p><strong>' . esc_html__( 'Total Customs Fees:', 'customs-fees-for-woocommerce' ) . '</strong> ' . wp_kses_post( wc_price( $total_fees ) ) . '</p>';
			echo '<p class="description">' . esc_html__( 'These fees were calculated based on the destination country and cart value.', 'customs-fees-for-woocommerce' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'No customs fees applied to this order.', 'customs-fees-for-woocommerce' ) . '</p>';
		}
		
		// Display HS codes and origin for order items.
		$items = $order->get_items();
		if ( ! empty( $items ) ) {
			echo '<hr style="margin: 15px 0;">';
			echo '<p><strong>' . esc_html__( 'Product Customs Information:', 'customs-fees-for-woocommerce' ) . '</strong></p>';
			echo '<ul style="margin: 10px 0 0 20px;">';
			
			foreach ( $items as $item ) {
				if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
					continue;
				}
				
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}
				
				$product_id = $product->get_id();
				$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
				$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
				
				if ( $hs_code || $origin ) {
					echo '<li>';
					echo '<strong>' . esc_html( $item->get_name() ) . '</strong><br>';
					
					if ( $hs_code ) {
						echo esc_html__( 'HS Code:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( $hs_code );
						if ( $origin ) {
							echo ' | ';
						}
					}
					
					if ( $origin ) {
						echo esc_html__( 'Origin:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( strtoupper( $origin ) );
					}
					
					echo '</li>';
				}
			}
			
			echo '</ul>';
		}
	}

	/**
	 * Add product fields for HS codes.
	 *
	 * @since 1.0.0
	 */
	public function add_product_fields() {
		echo '<div class="options_group show_if_simple show_if_variable">';
		
		// Add a separator heading for clarity.
		echo '<p class="form-field" style="margin: 0; padding: 8px 12px; background: #f8f8f8; border-bottom: 1px solid #ddd;">';
		echo '<strong>' . esc_html__( 'Customs & Import Information', 'customs-fees-for-woocommerce' ) . '</strong>';
		echo '</p>';
		
		woocommerce_wp_text_input( array(
			'id'          => '_cfwc_hs_code',
			'label'       => __( 'HS/Tariff Code', 'customs-fees-for-woocommerce' ),
			'placeholder' => __( 'e.g., 6109.10 or 6109.10.0012', 'customs-fees-for-woocommerce' ),
			'desc_tip'    => true,
			'description' => __( 'Harmonized System code for customs classification. This helps calculate accurate import duties.', 'customs-fees-for-woocommerce' ),
			'type'        => 'text',
			'class'       => 'short',
		) );
		
		woocommerce_wp_text_input( array(
			'id'          => '_cfwc_country_of_origin',
			'label'       => __( 'Country of Origin', 'customs-fees-for-woocommerce' ),
			'placeholder' => __( 'e.g., CN, US, GB', 'customs-fees-for-woocommerce' ),
			'desc_tip'    => true,
			'description' => __( 'Two-letter ISO country code where the product was manufactured (e.g., CN for China, US for United States).', 'customs-fees-for-woocommerce' ),
			'type'        => 'text',
			'class'       => 'short',
			'custom_attributes' => array(
				'maxlength' => '2',
				'style'     => 'text-transform: uppercase;',
			),
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

	/**
	 * Display customs info as order item meta in admin.
	 *
	 * @since 1.0.0
	 * @param int           $item_id Order item ID.
	 * @param WC_Order_Item $item    Order item object.
	 * @param WC_Product    $product Product object.
	 */
	public function display_customs_meta_in_order( $item_id, $item, $product ) {
		// Only in admin and for product line items.
		if ( ! is_admin() || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return;
		}
		
		// Get product if not provided.
		if ( ! $product ) {
			$product = $item->get_product();
		}
		
		if ( ! $product ) {
			return;
		}
		
		$product_id = $product->get_id();
		$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
		
		// Display as meta data similar to SKU.
		if ( $hs_code || $origin ) {
			echo '<div class="cfwc-order-item-meta" style="margin-top: 0.5em;">';
			echo '<table cellspacing="0" class="display_meta">';
			
			if ( $hs_code ) {
				echo '<tr>';
				echo '<th>' . esc_html__( 'HS Code:', 'customs-fees-for-woocommerce' ) . '</th>';
				echo '<td><p>' . esc_html( $hs_code ) . '</p></td>';
				echo '</tr>';
			}
			
			if ( $origin ) {
				echo '<tr>';
				echo '<th>' . esc_html__( 'Origin:', 'customs-fees-for-woocommerce' ) . '</th>';
				echo '<td><p>' . esc_html( strtoupper( $origin ) ) . '</p></td>';
				echo '</tr>';
			}
			
			echo '</table>';
			echo '</div>';
		}
	}

	/**
	 * Format meta key display.
	 *
	 * @since 1.0.0
	 * @param string        $display_key Display key.
	 * @param WC_Meta_Data  $meta        Meta data object.
	 * @param WC_Order_Item $item        Order item object.
	 * @return string
	 */
	public function format_meta_key( $display_key, $meta, $item ) {
		if ( 'cfwc_hs_code' === $meta->key ) {
			return __( 'HS Code', 'customs-fees-for-woocommerce' );
		}
		
		if ( 'cfwc_origin' === $meta->key ) {
			return __( 'Origin', 'customs-fees-for-woocommerce' );
		}
		
		return $display_key;
	}

	/**
	 * Format meta value display.
	 *
	 * @since 1.0.0
	 * @param string        $display_value Display value.
	 * @param WC_Meta_Data  $meta          Meta data object.
	 * @param WC_Order_Item $item          Order item object.
	 * @return string
	 */
	public function format_meta_value( $display_value, $meta, $item ) {
		if ( 'cfwc_origin' === $meta->key ) {
			return strtoupper( $display_value );
		}
		
		return $display_value;
	}
}
