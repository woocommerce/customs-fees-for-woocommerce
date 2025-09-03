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
		// Activation notices are now handled by CFWC_Onboarding class.
		// This prevents duplicate notices on activation.
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

		// Display customs fees breakdown.
		$fees       = $order->get_fees();
		$total_fees = 0;
		$has_fees   = false;

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ||
				strpos( strtolower( $fee->get_name() ), 'customs' ) !== false ||
				strpos( strtolower( $fee->get_name() ), 'import' ) !== false ||
				strpos( strtolower( $fee->get_name() ), 'duty' ) !== false ) {
				$total_fees += $fee->get_total();
				$has_fees    = true;
			}
		}

		if ( $has_fees ) {
			// Get the breakdown if available.
			$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

			if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
				// Show detailed breakdown.
				echo '<strong>' . esc_html__( 'Fees Breakdown:', 'customs-fees-for-woocommerce' ) . '</strong>';
				echo '<ul style="margin: 10px 0; padding-left: 20px;">';
				foreach ( $breakdown as $fee_item ) {
					echo '<li style="margin: 5px 0;">';
					echo esc_html( $fee_item['label'] ) . ': ';
					echo '<strong>' . wp_kses_post( wc_price( $fee_item['amount'] ) ) . '</strong>';
					echo '</li>';
				}
				echo '</ul>';
				echo '<hr style="margin: 10px 0;">';
				echo '<p><strong>' . esc_html__( 'Total:', 'customs-fees-for-woocommerce' ) . '</strong> ' . wp_kses_post( wc_price( $total_fees ) ) . '</p>';
			} else {
				// Fallback to simple total.
				echo '<p><strong>' . esc_html__( 'Total Customs Fees:', 'customs-fees-for-woocommerce' ) . '</strong> ' . wp_kses_post( wc_price( $total_fees ) ) . '</p>';
			}
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
				$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );
				$origin     = get_post_meta( $product_id, '_cfwc_country_of_origin', true );

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

		// Add a heading without grey background to match WooCommerce style.
		echo '<h4 style="margin: 10px 12px 5px; font-size: 14px; font-weight: 600;">' . esc_html__( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) . '</h4>';

		woocommerce_wp_text_input(
			array(
				'id'          => '_cfwc_hs_code',
				'label'       => __( 'HS/Tariff Code', 'customs-fees-for-woocommerce' ),
				'placeholder' => __( 'e.g., 6109.10 or 6109.10.0012', 'customs-fees-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Harmonized System code for customs classification. This helps calculate accurate import duties.', 'customs-fees-for-woocommerce' ),
				'type'        => 'text',
			)
		);

		// Add help text with link after HS Code field
		echo '<p class="cfwc-hs-code-help" style="margin-left: 154px; margin-top: -10px; margin-bottom: 10px; font-size: 12px; color: #666;">';
		echo esc_html__( 'You can ', 'customs-fees-for-woocommerce' );
		echo '<a href="https://hts.usitc.gov/" target="_blank" rel="noopener noreferrer">';
		echo esc_html__( 'find HS codes here', 'customs-fees-for-woocommerce' );
		echo '</a>';
		echo '</p>';

		// Country of Origin dropdown with WooCommerce countries.
		$countries    = WC()->countries->get_countries();
		$origin_value = get_post_meta( get_the_ID(), '_cfwc_country_of_origin', true );

		// Get default origin if no value is set for this product.
		if ( empty( $origin_value ) ) {
			$default_origin = get_option( 'cfwc_default_origin', 'store' );

			if ( 'store' === $default_origin ) {
				// Use store base country.
				$base_country       = get_option( 'woocommerce_default_country' );
				$base_country_parts = explode( ':', $base_country );
				$origin_value       = $base_country_parts[0];
			} elseif ( 'custom' === $default_origin ) {
				// Use custom default country.
				$origin_value = get_option( 'cfwc_custom_default_origin', '' );
			}
			// If 'none', leave empty.
		}

		woocommerce_wp_select(
			array(
				'id'                => '_cfwc_country_of_origin',
				'label'             => __( 'Country of Origin', 'customs-fees-for-woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( 'Select the country where this product was manufactured. This determines which customs rules apply.', 'customs-fees-for-woocommerce' ),
				'options'           => array( '' => __( 'Select a country', 'customs-fees-for-woocommerce' ) ) + $countries,
				'value'             => $origin_value,
				'class'             => 'wc-enhanced-select',
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select a country', 'customs-fees-for-woocommerce' ),
				),
			)
		);

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
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- edit_product is a standard WooCommerce capability.
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

		// Validate country code (should be 2 letters).
		if ( ! empty( $country ) && ! array_key_exists( $country, WC()->countries->get_countries() ) ) {
			$country = '';
		}

		update_post_meta( $post_id, '_cfwc_hs_code', $hs_code );
		update_post_meta( $post_id, '_cfwc_country_of_origin', $country );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type;

		// Load on product list page for quick/bulk edit support.
		if ( 'edit.php' === $hook && 'product' === $post_type ) {
			wp_enqueue_style(
				'cfwc-admin',
				( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/css/admin.css',
				array(),
				( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' )
			);

			wp_enqueue_script(
				'cfwc-admin',
				( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/js/admin.js',
				array( 'jquery' ),
				( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' ),
				true
			);
		}

		// Load on product pages for Select2.
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			if ( 'product' === $post_type ) {
				// Ensure WooCommerce Select2 (SelectWoo) is loaded.
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_style( 'woocommerce_admin_styles' );

				// Also load our admin CSS for proper select2 styling.
				wp_enqueue_style(
					'cfwc-admin',
					( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/css/admin.css',
					array( 'woocommerce_admin_styles' ),
					( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' )
				);

				// Initialize Select2 on our country field - trigger WooCommerce's native enhancement.
				wp_add_inline_script(
					'wc-enhanced-select',
					'
					jQuery( document ).ready( function( $ ) {
						// Use WooCommerce\'s native enhanced select initialization
						$( document.body ).trigger( "wc-enhanced-select-init" );
						
						// Ensure our specific field is enhanced if not already
						var $countryField = $( "#_cfwc_country_of_origin" );
						if ( $countryField.length && !$countryField.hasClass( "enhanced" ) ) {
							if ( $.fn.selectWoo ) {
								$countryField.selectWoo({
									minimumResultsForSearch: 10,
									allowClear: true,
									placeholder: $countryField.data( "placeholder" ) || "' . esc_js( __( 'Select a country', 'customs-fees-for-woocommerce' ) ) . '",
									width: "250px"
								}).addClass( "enhanced" );
							}
						}
					});
				'
				);
			}
		}

		// Only load on WooCommerce settings pages.
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Check if we're on the Tax tab with customs section.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		if ( 'tax' !== $tab || 'customs' !== $section ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'cfwc-admin',
			( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/css/admin.css',
			array( 'woocommerce_admin_styles' ),
			( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' )
		);

		// Enqueue UI improvements CSS.
		wp_enqueue_style(
			'cfwc-admin-improvements',
			( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/css/admin-improvements.css',
			array( 'cfwc-admin' ),
			( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' )
		);

		// Enqueue admin JavaScript with dependencies.
		// Include wp-data for snackbar notifications
		wp_enqueue_script(
			'cfwc-admin',
			( defined( 'CFWC_PLUGIN_URL' ) ? CFWC_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) ) . 'assets/js/admin.js',
			array( 'jquery', 'wc-enhanced-select', 'selectWoo', 'wp-data', 'wp-notices' ),
			( defined( 'CFWC_VERSION' ) ? CFWC_VERSION : '1.0.0' ),
			true
		);

		// Get templates for JavaScript.
		$templates_handler = new CFWC_Templates();
		$templates         = $templates_handler->get_templates();

		// Localize script with data.
		// Get product categories for the category selector.
		$product_categories = array();
		$categories         = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$product_categories[ $category->term_id ] = $category->name;
			}
		}

		wp_localize_script(
			'cfwc-admin',
			'cfwc_admin',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'cfwc_admin_nonce' ),
				'countries'       => WC()->countries->get_countries(),
				'categories'      => $product_categories,
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'templates'       => $templates,
				'strings'         => array(
					'select_country'             => __( 'Select a country', 'customs-fees-for-woocommerce' ),
					'select_preset_first'        => __( 'Please select a preset first.', 'customs-fees-for-woocommerce' ),
					'import_preset'              => __( 'Import Preset Rules', 'customs-fees-for-woocommerce' ),
					'add_to_existing'            => __( 'Add to Existing Rules', 'customs-fees-for-woocommerce' ),
					'adding_preset'              => __( 'Adding preset rules...', 'customs-fees-for-woocommerce' ),
					'replacing_rules'            => __( 'Replacing all rules with preset...', 'customs-fees-for-woocommerce' ),
					'confirm_replace'            => __( 'Click again to confirm', 'customs-fees-for-woocommerce' ),
					'replace_all'                => __( 'Replace All Rules', 'customs-fees-for-woocommerce' ),
					'no_rules_delete'            => __( 'No rules to delete.', 'customs-fees-for-woocommerce' ),
					'all_deleted'                => __( 'All rules deleted. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ),
					'delete_all'                 => __( 'Delete All Rules', 'customs-fees-for-woocommerce' ),
					'delete_warning'             => __( 'Warning: This will delete all existing rules. Click again to confirm.', 'customs-fees-for-woocommerce' ),
					'confirm_delete'             => __( 'Click to Confirm Delete', 'customs-fees-for-woocommerce' ),
					'preset_applied'             => __( 'Preset applied successfully!', 'customs-fees-for-woocommerce' ),
					'save_reminder'              => __( 'Remember to click "Save changes" to persist these rules.', 'customs-fees-for-woocommerce' ),
					'preset_failed'              => __( 'Failed to apply preset.', 'customs-fees-for-woocommerce' ),
					'preset_error'               => __( 'An error occurred while applying the preset.', 'customs-fees-for-woocommerce' ),
					'check_console'              => __( 'Check browser console for details.', 'customs-fees-for-woocommerce' ),
					'dismiss_notice'             => __( 'Dismiss this notice', 'customs-fees-for-woocommerce' ),
					'select_country_placeholder' => __( 'Select country...', 'customs-fees-for-woocommerce' ),
					'all_origins'                => __( 'All Origins', 'customs-fees-for-woocommerce' ),
					'eu_countries'               => __( 'EU Countries', 'customs-fees-for-woocommerce' ),
					'specific_country'           => __( 'Specific Country', 'customs-fees-for-woocommerce' ),
					'fee_label'                  => __( 'Fee label', 'customs-fees-for-woocommerce' ),
					'choose_country'             => __( 'Choose a country...', 'customs-fees-for-woocommerce' ),
					'choose_origin'              => __( 'Choose origin...', 'customs-fees-for-woocommerce' ),
					'percentage'                 => __( 'Percentage', 'customs-fees-for-woocommerce' ),
					'flat'                       => __( 'Flat', 'customs-fees-for-woocommerce' ),
					'save'                       => __( 'Save', 'customs-fees-for-woocommerce' ),
					'cancel'                     => __( 'Cancel', 'customs-fees-for-woocommerce' ),
					'edit'                       => __( 'Edit', 'customs-fees-for-woocommerce' ),
					'delete'                     => __( 'Delete', 'customs-fees-for-woocommerce' ),
					'rule_saved'                 => __( 'Rule saved. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ),
					'rule_deleted'               => __( 'Rule deleted successfully. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ),
					'delete_confirm'             => __( 'Click the delete button again to confirm deletion.', 'customs-fees-for-woocommerce' ),
					'no_rules'                   => __( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ),
					'not_set'                    => __( 'Not set', 'customs-fees-for-woocommerce' ),
				),
			)
		);
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
		$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$origin     = get_post_meta( $product_id, '_cfwc_country_of_origin', true );

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
		// Use get_data() method for proper access to meta data properties.
		$meta_data = $meta->get_data();
		$meta_key  = isset( $meta_data['key'] ) ? $meta_data['key'] : '';

		if ( 'cfwc_hs_code' === $meta_key ) {
			return __( 'HS Code', 'customs-fees-for-woocommerce' );
		}

		if ( 'cfwc_origin' === $meta_key ) {
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
		// Use get_data() method for proper access to meta data properties.
		$meta_data = $meta->get_data();
		$meta_key  = isset( $meta_data['key'] ) ? $meta_data['key'] : '';

		if ( 'cfwc_origin' === $meta_key ) {
			return strtoupper( $display_value );
		}

		return $display_value;
	}
}
