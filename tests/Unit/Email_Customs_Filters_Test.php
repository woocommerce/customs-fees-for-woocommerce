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
 * @covers \CFWC_Emails::is_rendering_email
 * @covers \CFWC_Emails::is_rendering_html_email
 */
class Email_Customs_Filters_Test extends \WC_Unit_Test_Case {

	/**
	 * Restore a front-end screen so admin context does not leak into later
	 * tests. set_current_screen() also updates $typenow, $taxnow and
	 * $hook_suffix, which nulling the global would leave behind.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * Build an order item for a simple product carrying customs meta.
	 *
	 * @return \WC_Order_Item_Product Order item.
	 */
	private function order_item_with_customs_meta(): \WC_Order_Item_Product {
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_cfwc_hs_code', '6109.10' );
		update_post_meta( $product->get_id(), '_cfwc_country_of_origin', 'US' );

		$order = \WC_Helper_Order::create_order( 1, $product );
		$items = $order->get_items();

		return array_shift( $items );
	}

	/**
	 * Run the woocommerce_order_item_name filter as templates do.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return string Filtered item name.
	 */
	private function filtered_item_name( \WC_Order_Item_Product $item ): string {
		return apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
	}

	/**
	 * Run the item name filter while woocommerce_email_order_details is
	 * executing, as the email order details templates do.
	 *
	 * @param \WC_Order_Item_Product $item       Order item.
	 * @param bool                   $plain_text Render as plain text email.
	 * @return string Filtered item name.
	 */
	private function item_name_during_email( \WC_Order_Item_Product $item, bool $plain_text = false ): string {
		$name    = '';
		$capture = function () use ( &$name, $item ): void {
			$name = $this->filtered_item_name( $item );
		};

		add_action( 'woocommerce_email_order_details', $capture, 20 );
		do_action( 'woocommerce_email_order_details', $item->get_order(), false, $plain_text, null );
		remove_action( 'woocommerce_email_order_details', $capture, 20 );

		return $name;
	}

	/** @testdox Both email filters set to false remove the HS code and origin from an admin-context HTML email. */
	public function test_email_filters_hide_customs_info_in_admin_context_email(): void {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		do_action( 'woocommerce_email_header', 'Heading', null );

		$name = $this->item_name_during_email( $item );

		$this->assertStringNotContainsString( 'HS Code', $name, 'cfwc_show_hs_code_in_email false must remove the HS code from the email item name.' );
		$this->assertStringNotContainsString( 'Origin', $name, 'cfwc_show_origin_in_email false must remove the origin from the email item name.' );
	}

	/** @testdox With default filter values, customs info appears exactly once in an admin-context HTML email. */
	public function test_customs_info_appears_once_in_admin_context_email(): void {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		do_action( 'woocommerce_email_header', 'Heading', null );

		$name = $this->item_name_during_email( $item );

		$this->assertSame( 1, substr_count( $name, 'Origin' ), 'CFWC_Display must not duplicate the origin already added by CFWC_Emails.' );
		$this->assertSame( 1, substr_count( $name, 'HS Code' ), 'CFWC_Display must not duplicate the HS code already added by CFWC_Emails.' );
	}

	/** @testdox A plain text email rendered in an admin request gets no injected HTML. */
	public function test_plain_text_email_in_admin_context_gets_no_html(): void {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );

		$name = $this->item_name_during_email( $item, true );

		$this->assertSame( $item->get_name(), $name, 'Neither class may inject HTML into a plain text email item name.' );
	}

	/** @testdox The email filters remove the HS code and origin for a variation with meta split across variation and parent. */
	public function test_email_filters_apply_to_variation_with_parent_origin(): void {
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

		$name = $this->item_name_during_email( $item );

		$this->assertStringNotContainsString( 'HS Code', $name, 'cfwc_show_hs_code_in_email false must remove the variation HS code from the email item name.' );
		$this->assertStringNotContainsString( 'Origin', $name, 'cfwc_show_origin_in_email false must remove the parent origin from the email item name.' );
	}

	/** @testdox The admin order screen keeps showing customs details regardless of the email filters. */
	public function test_admin_order_screen_display_is_unaffected_by_email_filters(): void {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		$name = $this->filtered_item_name( $item );

		$this->assertStringContainsString( 'HS Code: 6109.10', $name, 'The admin order screen must keep the HS code.' );
		$this->assertStringContainsString( 'Origin: United States', $name, 'The admin order screen must keep the origin.' );
	}

	/** @testdox Order items rendered after an email in the same request use the normal display path again. */
	public function test_display_path_recovers_after_an_email_in_the_same_request(): void {
		$item = $this->order_item_with_customs_meta();

		set_current_screen( 'edit-post' );
		add_filter( 'cfwc_show_hs_code_in_email', '__return_false' );
		add_filter( 'cfwc_show_origin_in_email', '__return_false' );

		do_action( 'woocommerce_email_header', 'Heading', null );
		$this->item_name_during_email( $item );

		$name = $this->filtered_item_name( $item );

		$this->assertStringContainsString( 'cfwc-order-customs', $name, 'After an email, a page render must get CFWC_Display markup, not be treated as an email.' );
		$this->assertSame( 1, substr_count( $name, 'HS Code' ), 'After an email, a page render must show the HS code exactly once.' );
		$this->assertSame( 1, substr_count( $name, 'Origin' ), 'After an email, a page render must show the origin exactly once.' );
	}
}
