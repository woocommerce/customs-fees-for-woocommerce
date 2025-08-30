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
		
		// Add tooltip to the main fee label.
		add_filter( 'woocommerce_cart_totals_fee_label', array( $this, 'add_tooltip_to_fee_label' ), 10, 2 );
		
		// Order pages display - adds to order totals table.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 10, 3 );
		
		// Add HS Codes to order item names on order pages (non-email).
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item_display' ), 10, 3 );
		
		// Email display.
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_fees_in_email' ), 10, 4 );
		
		// Save fee breakdown to order when order is created.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fee_breakdown_to_order' ), 10, 2 );
		
		// Enqueue frontend scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add tooltip to the main fee label.
	 *
	 * @since 1.0.0
	 * @param string $label The fee label.
	 * @param object $fee   The fee object.
	 * @return string Modified label with tooltip.
	 */
	public function add_tooltip_to_fee_label( $label, $fee ) {
		// Only modify our customs fees label.
		if ( $fee->name !== __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) {
			return $label;
		}
		
		// Add tooltip if enabled.
		$show_tooltip = get_option( 'cfwc_show_tooltip', true );
		if ( $show_tooltip ) {
			$tooltip_text = WC()->session->get( 'cfwc_tooltip_text', '' );
			if ( $tooltip_text ) {
				$label .= '<span class="cfwc-tooltip" title="' . esc_attr( $tooltip_text ) . '">';
				$label .= '<span class="dashicons dashicons-info-outline"></span>';
				$label .= '</span>';
			}
		}
		
		return $label;
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
		if ( $fee->name !== __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) {
			return $cart_totals_fee_html;
		}

		// Get the breakdown from session.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( empty( $breakdown ) ) {
			return $cart_totals_fee_html;
		}

		// Build the breakdown display with inline styles to ensure they apply.
		// Use ul/li structure with inline styles to override any theme styles.
		$html = '<ul id="cfwc_fees_breakdown" class="cfwc-fees-list" style="list-style: none !important; margin: 0 !important; padding: 0 !important;">';
		
		$total_items = count( $breakdown );
		$current = 0;
		foreach ( $breakdown as $index => $fee_item ) {
			$fee_id = 'cfwc_fee_' . $index;
			$current++;
			// Remove bottom margin for last item.
			$margin = ( $current === $total_items ) ? '0' : '0 0 0.5em 0';
			$html .= '<li class="cfwc-fee-item" style="list-style: none !important; margin: ' . $margin . ' !important; padding: 0 !important;">';
			// Use radio input with unique names so all can be checked.
			// Don't use disabled to maintain native theme styling.
			// Inline style pointer-events: none will prevent interaction.
			$html .= '<input type="radio" name="cfwc_fee_' . esc_attr( $index ) . '" id="' . esc_attr( $fee_id ) . '" ';
			$html .= 'value="' . esc_attr( $index ) . '" class="cfwc-fee-radio" checked="checked" style="pointer-events: none !important; margin: 0 0.5em 0 0 !important;" />';
			$html .= '<label for="' . esc_attr( $fee_id ) . '">';
			$html .= esc_html( $fee_item['label'] );
			// Format the price exactly like WooCommerce shipping methods.
			$html .= wc_price( $fee_item['amount'] );
			$html .= '</label>';
			$html .= '</li>';
		}
		
		$html .= '</ul>';
		
		// No tooltip here since it's already shown on the left label.
		
		return $html;
	}

	/**
	 * Save fee breakdown to order meta.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order  The order object.
	 * @param array    $data   Posted data.
	 */
	public function save_fee_breakdown_to_order( $order, $data ) {
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
					$value_html = '<ul class="cfwc-fees-breakdown woocommerce-order-overview" style="margin: 0; padding: 0; list-style: none;">';
					foreach ( $breakdown as $fee_item ) {
						$value_html .= '<li class="cfwc-fee-item" style="margin: 0 0 0.25em 0;">';
						$value_html .= '<span style="color: #515151;">' . esc_html( $fee_item['label'] ) . ':</span> ';
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
					echo esc_html( $fee->get_name() ) . ":\n";
					if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
						foreach ( $breakdown as $fee_item ) {
							echo '  - ' . esc_html( $fee_item['label'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
						}
					} else {
						echo '  ' . esc_html( wp_strip_all_tags( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
					}
				} else {
					echo '<h3>' . esc_html( $fee->get_name() ) . '</h3>';
					if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
						echo '<ul style="margin: 0; padding-left: 20px;">';
						foreach ( $breakdown as $fee_item ) {
							echo '<li>' . esc_html( $fee_item['label'] ) . ': ' . wp_kses_post( wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) ) . '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>' . wp_kses_post( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ) . '</p>';
					}
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
		$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
		
		if ( $hs_code || $origin ) {
			$customs_info = '<br><small style="color: #666; font-size: 0.9em;">';
			
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
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( is_cart() || is_checkout() || is_account_page() ) {
			// Only add minimal styles for elements not using inline styles.
			wp_add_inline_style( 'woocommerce-general', '
				/* Tooltip on the main label */
				.cfwc-tooltip {
					cursor: help;
					margin-left: 5px;
					display: inline-block;
					vertical-align: middle;
				}
				
				.cfwc-tooltip .dashicons {
					font-size: 16px;
					width: 16px;
					height: 16px;
					color: #999;
				}
				
				/* Disclaimer text (if used) */
				.cfwc-disclaimer {
					font-size: 0.9em;
					color: #666;
					font-style: italic;
				}
				
				/* Order fees section (for order pages) */
				.cfwc-order-fees {
					margin-top: 20px;
					padding: 15px;
					background: #f7f7f7;
					border-radius: 4px;
				}
				
				/* High contrast mode support for tooltip */
				@media (prefers-contrast: high) {
					.cfwc-tooltip .dashicons {
						color: inherit;
					}
				}
				
				/* RTL language support for tooltip */
				.rtl .cfwc-tooltip {
					margin-left: 0;
					margin-right: 5px;
				}
			' );
			
			// Add small JavaScript to ensure radio buttons stay checked.
			wp_add_inline_script( 'woocommerce', '
				jQuery( function( $ ) {
					// Ensure customs fee radio buttons always stay checked.
					$( document ).on( "change", ".cfwc-fee-radio", function() {
						$( this ).prop( "checked", true );
					});
				});
			' );
		}
	}
}
