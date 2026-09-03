<?php
/**
 * Tests that the fee hook adds the fee to the cart it receives (Subscriptions
 * calculates recurring totals on a clone) without overwriting the main cart's
 * session breakdown.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Loader::add_customs_fees
 */
class Cloned_Cart_Fee_Test extends \WC_Unit_Test_Case {

	/**
	 * Shipping zone id, so the cart-level needs_shipping() check is meaningful.
	 *
	 * @var int
	 */
	private $zone_id = 0;

	/**
	 * Set up store, rule, shipping, and cart fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'CFWC Test Zone' );
		$zone->add_location( 'CA', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );
		$this->zone_id = $zone->get_id();

		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'cfwc_valuation_method', 'fob' );
		update_option( 'cfwc_rules', array( $this->ca_flat_rule() ) );
		delete_transient( 'cfwc_rules_cache' );

		if ( null === WC()->cart ) {
			wc_load_cart();
		}
		WC()->cart->empty_cart();
		WC()->customer->set_shipping_country( 'CA' );

		$this->reset_calculator_memo();
	}

	/**
	 * Tear down fixtures.
	 */
	public function tearDown(): void {
		WC()->cart->empty_cart();
		WC()->customer->set_shipping_country( '' );
		WC()->session->set( 'cfwc_fees_breakdown', array() );
		WC()->session->set( 'cfwc_tooltip_text', null );
		remove_all_filters( 'woocommerce_cart_needs_shipping' );
		remove_all_filters( 'cfwc_calculated_fees' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		if ( $this->zone_id ) {
			\WC_Shipping_Zones::delete_zone( $this->zone_id );
		}
		delete_option( 'cfwc_rules' );
		delete_transient( 'cfwc_rules_cache' );
		\CFWC_Calculator::clear_cache();
		parent::tearDown();
	}

	/**
	 * @testdox Adds the customs fee to a cloned cart, not only to the main cart.
	 */
	public function test_fee_is_added_to_the_cart_passed_to_the_hook(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( WC()->cart ), 'Main cart should carry the customs fee.' );

		$recurring_cart = clone WC()->cart;
		$recurring_cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( $recurring_cart ), 'Cloned cart should carry its own customs fee.' );
	}

	/**
	 * @testdox Keeps the main cart's session breakdown when a cloned cart with fewer items is calculated.
	 */
	public function test_cloned_cart_does_not_overwrite_main_cart_breakdown(): void {
		$key_a = WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->add_to_cart( $this->make_product( 30 ), 1 );
		WC()->cart->calculate_totals();

		$this->assertSame( 10.0, $this->breakdown_total(), 'Main cart breakdown should cover both products.' );

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_a => WC()->cart->get_cart_item( $key_a ) ) );
		$recurring_cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( $recurring_cart ), 'Cloned cart fee should reflect only its own item.' );
		$this->assertSame( 10.0, $this->breakdown_total(), 'Session breakdown should still describe the main cart.' );
	}

	/**
	 * @testdox Leaves the main cart's session breakdown alone when a cloned cart produces no fees.
	 */
	public function test_cloned_cart_without_fees_keeps_main_cart_breakdown(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		$key_virtual = WC()->cart->add_to_cart( $this->make_product( 30, true ), 1 );
		WC()->cart->calculate_totals();

		$this->assertSame( 5.0, $this->breakdown_total() );

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_virtual => WC()->cart->get_cart_item( $key_virtual ) ) );
		$recurring_cart->calculate_totals();

		$this->assertSame( 0.0, $this->customs_fee_amount( $recurring_cart ), 'A virtual-only clone gets no fee.' );
		$this->assertSame( 5.0, $this->breakdown_total(), 'The clone must not clear the main cart breakdown.' );
	}

	/**
	 * @testdox Clears the session breakdown when the main cart itself no longer has fees.
	 */
	public function test_main_cart_without_fees_clears_session_breakdown(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertSame( 5.0, $this->breakdown_total() );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->make_product( 30, true ), 1 );
		WC()->cart->calculate_totals();

		$this->assertSame( 0.0, $this->breakdown_total() );
	}

	/**
	 * @testdox Writes the tooltip text to the session only from the main cart.
	 */
	public function test_tooltip_is_written_only_by_the_main_cart(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertNotEmpty( WC()->session->get( 'cfwc_tooltip_text' ) );

		WC()->session->set( 'cfwc_tooltip_text', 'sentinel' );
		$recurring_cart = clone WC()->cart;
		$recurring_cart->calculate_totals();

		$this->assertSame( 'sentinel', WC()->session->get( 'cfwc_tooltip_text' ) );
	}

	/**
	 * @testdox Computes a percentage fee from the cloned cart's own line totals.
	 */
	public function test_percentage_fee_uses_the_cloned_cart_totals(): void {
		$rule           = $this->ca_flat_rule();
		$rule['type']   = 'percentage';
		$rule['rate']   = 10;
		$rule['amount'] = 0;
		update_option( 'cfwc_rules', array( $rule ) );
		delete_transient( 'cfwc_rules_cache' );
		$this->reset_calculator_memo();

		$key_a = WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->add_to_cart( $this->make_product( 30 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertSame( 8.0, $this->customs_fee_amount( WC()->cart ) );

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_a => WC()->cart->get_cart_item( $key_a ) ) );
		$recurring_cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( $recurring_cart ) );
	}

	/**
	 * @testdox Applies tax to a taxable fee on the cloned cart.
	 */
	public function test_taxable_fee_is_taxed_on_the_cloned_cart(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'CA',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
		$rule            = $this->ca_flat_rule();
		$rule['taxable'] = true;
		update_option( 'cfwc_rules', array( $rule ) );
		delete_transient( 'cfwc_rules_cache' );
		$this->reset_calculator_memo();

		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();

		$recurring_cart = clone WC()->cart;
		$recurring_cart->calculate_totals();

		$fee = $this->customs_fee( $recurring_cart );
		$this->assertTrue( $fee->taxable );
		$this->assertSame( 1.0, round( (float) $fee->tax, 2 ) );
		$this->assertSame( 66.0, round( (float) $recurring_cart->get_total( 'edit' ), 2 ) );
	}

	/**
	 * @testdox Adds the fee to a cart passed to the hook even when the global cart is unavailable.
	 */
	public function test_fee_is_added_when_the_global_cart_is_unset(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		$recurring_cart = clone WC()->cart;
		$recurring_cart->fees_api()->set_fees();

		$main_cart  = WC()->cart;
		WC()->cart = null;
		try {
			do_action( 'woocommerce_cart_calculate_fees', $recurring_cart );
		} finally {
			WC()->cart = $main_cart;
		}

		$this->assertSame( 5.0, $this->customs_fee_amount( $recurring_cart ) );
	}

	/**
	 * @testdox Skips a cloned cart that needs no shipping, such as a Subscriptions one-time-shipping recurring cart.
	 */
	public function test_cloned_cart_that_needs_no_shipping_gets_no_fee(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertSame( 5.0, $this->customs_fee_amount( WC()->cart ) );

		$recurring_cart = clone WC()->cart;
		add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
		$recurring_cart->calculate_totals();

		$this->assertSame( 0.0, $this->customs_fee_amount( $recurring_cart ), 'Nothing ships on this cart, so no customs fee.' );
		$this->assertSame( 5.0, $this->breakdown_total(), 'Main cart breakdown is untouched.' );
	}

	/**
	 * @testdox Keeps charging a cloned cart when the store has no shipping methods, matching the main cart.
	 */
	public function test_cloned_cart_is_still_charged_when_the_store_has_no_shipping_methods(): void {
		\WC_Shipping_Zones::delete_zone( $this->zone_id );
		$this->zone_id = 0;

		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertFalse( WC()->cart->needs_shipping(), 'Precondition: core reports no shipping without methods.' );
		$this->assertSame( 5.0, $this->customs_fee_amount( WC()->cart ) );

		$recurring_cart = clone WC()->cart;
		$recurring_cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( $recurring_cart ) );
	}

	/**
	 * @testdox Attaches the breakdown of the cart it was computed for to the fee object.
	 */
	public function test_fee_carries_the_breakdown_of_its_own_cart(): void {
		$key_a = WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->add_to_cart( $this->make_product( 30 ), 1 );
		WC()->cart->calculate_totals();

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_a => WC()->cart->get_cart_item( $key_a ) ) );
		$recurring_cart->calculate_totals();

		$this->assertSame( 10.0, $this->sum_amounts( $this->customs_fee( WC()->cart )->cfwc_breakdown ?? array() ) );
		$this->assertSame( 5.0, $this->sum_amounts( $this->customs_fee( $recurring_cart )->cfwc_breakdown ?? array() ) );
	}

	/**
	 * @testdox Saves the breakdown of the cart the order was built from, not the main cart's, on the fee item and order.
	 */
	public function test_order_built_from_cloned_cart_stores_that_cart_breakdown(): void {
		$key_a = WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->add_to_cart( $this->make_product( 30 ), 1 );
		WC()->cart->calculate_totals();

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_a => WC()->cart->get_cart_item( $key_a ) ) );
		$recurring_cart->calculate_totals();

		// Subscriptions builds the subscription's fee lines from the recurring cart this way.
		$subscription = wc_create_order();
		WC()->checkout()->create_order_fee_lines( $subscription, $recurring_cart );
		$subscription->save();

		$fee_items = $subscription->get_fees();
		$this->assertCount( 1, $fee_items );
		$fee_item = reset( $fee_items );
		$this->assertSame( 5.0, (float) $fee_item->get_total() );
		$this->assertSame( 5.0, $this->sum_amounts( (array) $fee_item->get_meta( '_cfwc_breakdown' ) ) );
		$this->assertSame( 5.0, $this->sum_amounts( (array) $subscription->get_meta( '_cfwc_fees_breakdown' ) ) );
		$this->assertSame( 10.0, $this->breakdown_total(), 'Session still describes the main cart.' );
	}

	/**
	 * @testdox Renders the fee row of a cloned cart from that cart's own breakdown.
	 */
	public function test_fee_row_html_uses_the_fee_own_breakdown(): void {
		$key_a = WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->add_to_cart( $this->make_product( 30 ), 1 );
		WC()->cart->calculate_totals();

		$recurring_cart = clone WC()->cart;
		$recurring_cart->set_cart_contents( array( $key_a => WC()->cart->get_cart_item( $key_a ) ) );
		$recurring_cart->calculate_totals();

		$main_html      = apply_filters( 'woocommerce_cart_totals_fee_html', 'default', $this->customs_fee( WC()->cart ) );
		$recurring_html = apply_filters( 'woocommerce_cart_totals_fee_html', 'default', $this->customs_fee( $recurring_cart ) );

		$this->assertStringContainsString( '10.00', $main_html );
		$this->assertStringContainsString( '5.00', $recurring_html );
		$this->assertStringNotContainsString( '10.00', $recurring_html );
	}

	/**
	 * @testdox Falls back to the session breakdown for a foreign fee object only when the amounts agree.
	 */
	public function test_session_fallback_applies_only_when_it_matches_the_fee_amount(): void {
		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();
		$this->assertSame( 5.0, $this->breakdown_total() );

		// A fee re-added from an order (Subscriptions renewal cart) has no breakdown of its own.
		$matching = (object) array( 'name' => 'Customs & Import Fees', 'amount' => 5.0, 'total' => 5.0 );
		$foreign  = (object) array( 'name' => 'Customs & Import Fees', 'amount' => 7.0, 'total' => 7.0 );

		$this->assertStringContainsString( '5.00', apply_filters( 'woocommerce_cart_totals_fee_html', 'default', $matching ) );
		$this->assertSame( 'default', apply_filters( 'woocommerce_cart_totals_fee_html', 'default', $foreign ) );
	}

	/**
	 * @testdox Ignores malformed entries returned by the cfwc_calculated_fees filter.
	 */
	public function test_malformed_filter_entries_do_not_break_the_fee(): void {
		add_filter(
			'cfwc_calculated_fees',
			function ( $fees ) {
				$fees[] = 'not-an-array';
				$fees[] = array( 'label' => 'Bad amount', 'amount' => 'abc' );
				$fees[] = array( 'label' => 'No amount' );
				return $fees;
			}
		);

		WC()->cart->add_to_cart( $this->make_product( 50 ), 1 );
		WC()->cart->calculate_totals();

		$this->assertSame( 5.0, $this->customs_fee_amount( WC()->cart ) );
	}

	/**
	 * The customs fee object on a cart.
	 *
	 * @param \WC_Cart $cart Cart to inspect.
	 * @return \stdClass
	 */
	private function customs_fee( \WC_Cart $cart ): \stdClass {
		foreach ( $cart->get_fees() as $fee ) {
			if ( 'Customs & Import Fees' === $fee->name ) {
				return $fee;
			}
		}
		$this->fail( 'No customs fee on the cart.' );
	}

	/**
	 * Sum of the amount fields in a breakdown array.
	 *
	 * @param array $breakdown Breakdown entries.
	 * @return float
	 */
	private function sum_amounts( array $breakdown ): float {
		$total = 0.0;
		foreach ( $breakdown as $entry ) {
			$total += (float) ( $entry['amount'] ?? 0 );
		}
		return $total;
	}

	/**
	 * Sum of the customs fee amounts on a cart.
	 *
	 * @param \WC_Cart $cart Cart to inspect.
	 * @return float
	 */
	private function customs_fee_amount( \WC_Cart $cart ): float {
		$total = 0.0;
		foreach ( $cart->get_fees() as $fee ) {
			if ( 'Customs & Import Fees' === $fee->name ) {
				$total += (float) $fee->amount;
			}
		}
		return $total;
	}

	/**
	 * Sum of the fee amounts in the session breakdown.
	 *
	 * @return float
	 */
	private function breakdown_total(): float {
		return $this->sum_amounts( (array) WC()->session->get( 'cfwc_fees_breakdown', array() ) );
	}

	/**
	 * Create a simple product with a Chinese origin.
	 *
	 * @param float $price   Product price.
	 * @param bool  $virtual Whether the product is virtual (no customs fee applies).
	 * @return int Product ID.
	 */
	private function make_product( float $price, bool $virtual = false ): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'CFWC Cloned Cart Test' );
		$product->set_regular_price( (string) $price );
		$product->set_price( (string) $price );
		$product->set_virtual( $virtual );
		$product->update_meta_data( '_cfwc_country_of_origin', 'CN' );

		return $product->save();
	}

	/**
	 * Flat USD 5 rule for shipments to Canada.
	 *
	 * @return array Rule data.
	 */
	private function ca_flat_rule(): array {
		return array(
			'rule_id'          => 'ca-flat',
			'country'          => 'CA',
			'to_country'       => 'CA',
			'from_country'     => '',
			'match_type'       => 'all',
			'type'             => 'flat',
			'rate'             => 0,
			'amount'           => 5,
			'label'            => 'Import Fee',
			'taxable'          => false,
			'tax_class'        => '',
			'valuation_method' => 'fob',
			'base_includes'    => array(),
			'stacking_mode'    => 'add',
			'priority'         => 0,
		);
	}

	/**
	 * Reset the calculator's rule memo, which the static clear_cache() cannot.
	 */
	private function reset_calculator_memo(): void {
		$loader     = \CFWC_Loader::instance();
		$loader_ref = new \ReflectionClass( $loader );
		if ( ! $loader_ref->hasProperty( 'calculator' ) ) {
			return;
		}

		$calc_prop = $loader_ref->getProperty( 'calculator' );
		$calc_prop->setAccessible( true );
		$calculator = $calc_prop->getValue( $loader );
		if ( ! $calculator ) {
			return;
		}

		$calc_ref = new \ReflectionClass( $calculator );
		if ( $calc_ref->hasProperty( 'rules_cache' ) ) {
			$cache_prop = $calc_ref->getProperty( 'rules_cache' );
			$cache_prop->setAccessible( true );
			$cache_prop->setValue( $calculator, null );
		}
	}
}
