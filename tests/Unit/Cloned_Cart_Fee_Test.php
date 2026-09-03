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
	 * Set up store, rule, and cart fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

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
		WC()->session->set( 'cfwc_fees_breakdown', array() );
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
		$total = 0.0;
		foreach ( (array) WC()->session->get( 'cfwc_fees_breakdown', array() ) as $entry ) {
			$total += (float) ( $entry['amount'] ?? 0 );
		}
		return $total;
	}

	/**
	 * Create a physical simple product with a Chinese origin.
	 *
	 * @param float $price Product price.
	 * @return int Product ID.
	 */
	private function make_product( float $price ): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'CFWC Cloned Cart Test' );
		$product->set_regular_price( (string) $price );
		$product->set_price( (string) $price );
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
