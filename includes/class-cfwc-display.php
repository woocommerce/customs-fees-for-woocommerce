<?php
/**
 * Display handler for frontend fee presentation.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Display class.
 *
 * Handles the display of customs fees on cart, checkout, and order pages.
 *
 * @since 1.0.0
 */
class CFWC_Display {

	/**
	 * Initialize the display handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Override the default fee display to show our grouped format.
		add_filter( 'woocommerce_cart_totals_fee_html', array( $this, 'customize_fee_display' ), 10, 2 );

		// Order pages display - adds to order totals table.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 10, 3 );

		// Add HS Codes to order item names on order pages (non-email).
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item_display' ), 10, 3 );

		// Email display.
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_fees_in_email' ), 10, 4 );

		// Save fee breakdown to order when order is created.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fee_breakdown_to_order' ), 10, 2 );

		// Enqueue frontend assets for tooltips.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tooltip_assets' ) );
	}

	/**
	 * Customize fee display to show breakdown.
	 *
	 * @since 1.0.0
	 * @param string $cart_totals_fee_html The HTML for the fee amount.
	 * @param object $fee                  The fee object.
	 * @return string Modified HTML.
	 */
	public function customize_fee_display( $cart_totals_fee_html, $fee ) {
		// Only customize our customs fees.
		if ( __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) !== $fee->name ) {
			return $cart_totals_fee_html;
		}

		// Get the breakdown from session.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( empty( $breakdown ) ) {
			return $cart_totals_fee_html;
		}

		// Build the breakdown display - clean list like tax display.
		$html = '<div id="cfwc_fees_breakdown" class="cfwc-fees-breakdown">';

		foreach ( $breakdown as $index => $fee_item ) {
			$html .= '<div class="cfwc-fee-item">';
			$html .= '<span class="cfwc-fee-label">' . esc_html( $fee_item['label'] ) . '</span>';
			$html .= '<span class="cfwc-fee-amount"><strong>' . wc_price( $fee_item['amount'] ) . '</strong></span>';
			$html .= '</div>';
		}

		$html .= '</div>';

		// No tooltip here since it's already shown on the left label.

		return $html;
	}

	/**
	 * Save fee breakdown to order meta.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order  The order object.
	 * @param array    $data   Posted data (unused but required by hook).
	 */
	public function save_fee_breakdown_to_order( $order, $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Required by hook signature.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( ! empty( $breakdown ) ) {
			$order->update_meta_data( '_cfwc_fees_breakdown', $breakdown );
		}
	}

	/**
	 * Add fees to order totals display.
	 *
	 * @since 1.0.0
	 * @param array    $totals Order totals.
	 * @param WC_Order $order  Order object.
	 * @param bool     $tax_display Whether to display tax.
	 * @return array Modified totals.
	 */
	public function add_fees_to_order_totals( $totals, $order, $tax_display = false ) {
		$fees = $order->get_fees();

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				// Get the breakdown if available.
				$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

				$value_html = '';
				if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
					// Show breakdown with nice list formatting.
					$value_html = '<ul class="cfwc-fees-breakdown woocommerce-order-overview">';
					foreach ( $breakdown as $fee_item ) {
						$value_html .= '<li class="cfwc-fee-item">';
						$value_html .= '<span class="cfwc-fee-label">' . esc_html( $fee_item['label'] ) . ':</span> ';
						$value_html .= '<strong>' . wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) . '</strong>';
						$value_html .= '</li>';
					}
					$value_html .= '</ul>';
				} else {
					// Fallback to simple total.
					$value_html = wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) );
				}

				$totals['customs_fees'] = array(
					'label' => $fee->get_name() . ':',
					'value' => $value_html,
				);
			}
		}

		return $totals;
	}

	/**
	 * Display fees in order emails.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	public function display_fees_in_email( $order, $sent_to_admin, $plain_text, $email = null ) {
		$fees = $order->get_fees();

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

				if ( $plain_text ) {
					echo "\n" . esc_html( $fee->get_name() ) . ":\n";
					if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
						foreach ( $breakdown as $fee_item ) {
							echo '  • ' . esc_html( $fee_item['label'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
						}
					} else {
						echo '  Total: ' . esc_html( wp_strip_all_tags( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
					}
					echo "\n";
				} else {
					// HTML email format with better styling.
					echo '<table cellspacing="0" cellpadding="0" style="width: 100%; margin-top: 20px; border-top: 1px solid #e5e5e5;">';
					echo '<tr><td style="padding: 15px 0 10px;"><h3 style="margin: 0; font-size: 16px; color: #333;">' . esc_html( $fee->get_name() ) . '</h3></td></tr>';
					if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
						echo '<tr><td style="padding: 0 0 15px;">';
						echo '<table cellspacing="0" cellpadding="0" style="width: 100%;">';
						foreach ( $breakdown as $fee_item ) {
							echo '<tr>';
							echo '<td style="padding: 5px 0; color: #666; font-size: 14px;">• ' . esc_html( $fee_item['label'] ) . '</td>';
							echo '<td style="padding: 5px 0; text-align: right; font-weight: bold; color: #333; font-size: 14px;">' . wp_kses_post( wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) ) . '</td>';
							echo '</tr>';
						}
						echo '</table>';
						echo '</td></tr>';
					} else {
						echo '<tr><td style="padding: 0 0 15px; font-size: 14px; color: #333;">Total: <strong>' . wp_kses_post( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ) . '</strong></td></tr>';
					}
					echo '</table>';
				}
			}
		}
	}



	/**
	 * Add HS Code and Origin to order item display on order pages.
	 *
	 * @since 1.0.0
	 * @param string        $item_name Item name HTML.
	 * @param WC_Order_Item $item      Order item object.
	 * @param bool          $is_visible Whether item is visible.
	 * @return string Modified item name.
	 */
	public function add_hs_code_to_order_item_display( $item_name, $item, $is_visible = true ) {
		// Only modify for product line items.
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item_name;
		}

		// Skip if we're in an email context (emails handled separately).
		if ( did_action( 'woocommerce_email_header' ) ) {
			return $item_name;
		}

		// Only on frontend order pages (thank you, my account), NOT admin.
		if ( ! is_order_received_page() && ! is_account_page() ) {
			return $item_name;
		}

		// Skip in admin area - handled separately as order item meta.
		if ( is_admin() ) {
			return $item_name;
		}

		$product = $item->get_product();
		if ( ! $product ) {
			return $item_name;
		}

		$product_id = $product->get_id();
		$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$origin     = get_post_meta( $product_id, '_cfwc_country_of_origin', true );

		if ( $hs_code || $origin ) {
			$customs_info = '<br><small class="cfwc-order-customs">';

			if ( $hs_code ) {
				$customs_info .= sprintf(
					'%s: %s',
					__( 'HS Code', 'customs-fees-for-woocommerce' ),
					esc_html( $hs_code )
				);
			}

			if ( $origin ) {
				// Display country code in uppercase.
				$origin_display = strtoupper( substr( $origin, 0, 2 ) );

				if ( $hs_code ) {
					$customs_info .= ' | ';
				}

				$customs_info .= sprintf(
					'%s: %s',
					__( 'Origin', 'customs-fees-for-woocommerce' ),
					esc_html( $origin_display )
				);
			}

			$customs_info .= '</small>';

			$item_name .= $customs_info;
		}

		return $item_name;
	}

	/**
	 * Enqueue tooltip assets on cart and checkout pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_tooltip_assets() {
		// Only on cart and checkout pages.
		if ( ! is_cart() && ! is_checkout() ) {
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

		// Get tooltip text from session first (it's set during cart calculation).
		$tooltip_text = '';
		if ( WC()->session ) {
			$tooltip_text = WC()->session->get( 'cfwc_tooltip_text', '' );
		}

		// Fallback to default translatable text if not in session.
		if ( empty( $tooltip_text ) && class_exists( 'CFWC_Settings' ) ) {
			$tooltip_text = CFWC_Settings::get_default_help_text();
		}

		// Localize script with tooltip text.
		wp_localize_script(
			'cfwc-frontend',
			'cfwc_params',
			array(
				'tooltip_text' => $tooltip_text,
			)
		);
	}
}
