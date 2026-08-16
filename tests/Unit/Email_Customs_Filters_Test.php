<?php
/**
 * Unit tests for the cfwc_show_hs_code_in_email and cfwc_show_origin_in_email
 * filters on the woocommerce_order_item_name rendering paths.
 *
 * Covers CUSFEES-26: emails generated in an admin request context (order
 * status change, resend email, admin-ajax, Action Scheduler async runner)
 * were decorated by CFWC_Display::add_hs_code_to_order_item_display(), which
 * ignores both email filters and duplicates the CFWC_Emails output.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Display::add_hs_code_to_order_item_display
 * @covers \CFWC_Emails::add_hs_code_to_order_item
 */
class Email_Customs_Filters_Test extends \WC_Unit_Test_Case {

	/**
	 * Reset email-context action counters, screen, and filters between tests.
	 */
	public function tearDown(): void {
		global $wp_actions, $current_screen;
		unset( $wp_actions['woocommerce_email_header'] );
		unset( $wp_actions['woocommerce_email_order_details'] );
		$current_screen = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		remove_all_filters( 'cfwc_show_hs_code_in_email' );
		remove_all_filters( 'cfwc_show_origin_in_email' );
		parent::tearDown();
	}

	/**
	 * Build an order item for a simple product carrying customs meta.
	 *
	 * @return \WC_Order_Item_Product Order item.
	 */
	private function order_item_with_customs_meta() {
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_cfwc_hs_code', '6109.10' );
		update_post_meta( $product->get_id(), '_cfwc_country_of_origin', 'US' );

		$order = \WC_Helper_Order::create_order( 1, $product );
		$items = $order->get_items();

		return array_shift( $items );
	}

	/**
	 * Run the woocommerce_order_item_name filter as email templates do.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return string Filtered item name.
	 */
	private function filtered_item_name( $item ) {
		return apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
	}

	/**
	 * HTML email rendered in an admin request: both filters set to false must
	 * remove the HS code and the origin from the item name.
	 */
	public function test_email_filters_hide_customs_info_in_admin_context_email() {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		do_action( 'woocommerce_email_header', 'Heading', null );

		$name = $this->filtered_item_name( $item );

		$this->assertStringNotContainsString( 'Origin', $name );
		$this->assertStringNotContainsString( 'HS Code', $name );
	}

	/**
	 * HTML email rendered in an admin request with default filter values:
	 * customs info must appear exactly once (no CFWC_Display duplication).
	 */
	public function test_customs_info_appears_once_in_admin_context_email() {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		do_action( 'woocommerce_email_header', 'Heading', null );

		$name = $this->filtered_item_name( $item );

		$this->assertSame( 1, substr_count( $name, 'Origin' ) );
		$this->assertSame( 1, substr_count( $name, 'HS Code' ) );
	}

	/**
	 * Plain-text emails never fire woocommerce_email_header but do fire
	 * woocommerce_email_order_details; the display path must not inject HTML
	 * into them even in an admin request context.
	 */
	public function test_plain_text_email_in_admin_context_gets_no_html() {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		do_action( 'woocommerce_email_order_details', null, false, true, null );

		$name = $this->filtered_item_name( $item );

		$this->assertSame( $item->get_name(), $name );
	}

	/**
	 * Variation with the HS code stored on the variation and the origin on the
	 * parent (the CUSFEES-26 reporter's setup): filters must still remove the
	 * origin in an admin-context email.
	 */
	public function test_email_origin_filter_applies_to_variation_with_parent_origin() {
		$parent = \WC_Helper_Product::create_variation_product();
		update_post_meta( $parent->get_id(), '_cfwc_country_of_origin', 'US' );
		$children     = $parent->get_children();
		$variation_id = array_shift( $children );
		update_post_meta( $variation_id, '_cfwc_hs_code', '6109.10' );

		$order = \WC_Helper_Order::create_order( 1, wc_get_product( $variation_id ) );
		$items = $order->get_items();
		$item  = array_shift( $items );

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		do_action( 'woocommerce_email_header', 'Heading', null );

		$name = $this->filtered_item_name( $item );

		$this->assertStringNotContainsString( 'Origin', $name );
	}

	/**
	 * Outside email rendering, the admin order screen must keep showing the
	 * customs details regardless of the email filters.
	 */
	public function test_admin_order_screen_display_is_unaffected_by_email_filters() {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		$name = $this->filtered_item_name( $item );

		$this->assertStringContainsString( 'HS Code: 6109.10', $name );
		$this->assertStringContainsString( 'Origin: United States', $name );
	}
}
