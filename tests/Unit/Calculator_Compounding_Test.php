<?php
/**
 * Integration tests for compound (base_includes) fee calculation at the cart.
 *
 * Verifies the CUSFEES-12 fix end-to-end: a dependent tax rule computes its
 * percentage on a base that includes the upstream duty fee.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Calculator::calculate_fees
 */
class Calculator_Compounding_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up store + cart fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'cfwc_valuation_method', 'fob' );

		if ( null === WC()->cart ) {
			wc_load_cart();
		}
		WC()->cart->empty_cart();

		// The fee hook runs against a single shared CFWC_Calculator instance that
		// memoizes rules in a private $rules_cache. The static clear_cache()
		// cannot reset that memo (CUSFEES-12 finding #8), so without this reset
		// a prior test's rules would leak into this one. Clear it via reflection.
		$this->reset_calculator_memo();
	}

	/**
	 * Tear down fixtures.
	 */
	public function tearDown(): void {
		WC()->cart->empty_cart();
		delete_option( 'cfwc_rules' );
		delete_transient( 'cfwc_rules_cache' );
		\CFWC_Calculator::clear_cache();
		parent::tearDown();
	}

	/**
	 * Null the shared calculator's in-memory rule cache so each test reads its
	 * own rules. Works around the static clear_cache() not resetting the memo.
	 */
	private function reset_calculator_memo(): void {
		delete_transient( 'cfwc_rules_cache' );

		if ( ! class_exists( 'CFWC_Loader' ) ) {
			return;
		}

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

	/**
	 * Create a simple product at the given price.
	 *
	 * @param float $price Product price.
	 * @return int Product ID.
	 */
	private function make_product( float $price ): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'CFWC Compounding Test' );
		$product->set_regular_price( (string) $price );
		$product->set_price( (string) $price );

		return $product->save();
	}

	/**
	 * Build a CA rule with the given id, rate, and dependencies.
	 *
	 * @param string $id   Rule ID.
	 * @param float  $rate Percentage rate.
	 * @param string $label Display label.
	 * @param array  $deps base_includes dependency ids.
	 * @return array Rule data.
	 */
	private function ca_rule( string $id, float $rate, string $label, array $deps = array() ): array {
		return array(
			'rule_id'          => $id,
			'country'          => 'CA',
			'to_country'       => 'CA',
			'from_country'     => '',
			'match_type'       => 'all',
			'type'             => 'percentage',
			'rate'             => $rate,
			'amount'           => 0,
			'label'            => $label,
			'taxable'          => false,
			'tax_class'        => '',
			'valuation_method' => 'fob',
			'base_includes'    => $deps,
			'stacking_mode'    => 'add',
			'priority'         => 0,
		);
	}

	/**
	 * Add a $100 product, ship to Canada, recalculate, and total the customs fees.
	 *
	 * @return float Sum of all cart fees.
	 */
	private function calculate_total_fees(): float {
		$product_id = $this->make_product( 100 );

		WC()->customer->set_shipping_country( 'CA' );
		WC()->customer->set_billing_country( 'CA' );
		WC()->cart->add_to_cart( $product_id, 1 );
		WC()->cart->calculate_totals();

		$total = 0.0;
		foreach ( WC()->cart->get_fees() as $fee ) {
			$total += (float) $fee->amount;
		}
		return $total;
	}

	/**
	 * @testdox A dependent tax rule compounds on the upstream duty fee.
	 *
	 * duty: 8% FOB on $100 = $8.00; gst: 5% FOB on ($100 + $8) = $5.40; total $13.40.
	 */
	public function test_dependent_rule_compounds_on_duty(): void {
		update_option(
			'cfwc_rules',
			array(
				$this->ca_rule( 'duty', 8, 'CA Duty' ),
				$this->ca_rule( 'gst', 5, 'CA GST', array( 'duty' ) ),
			)
		);
		$this->reset_calculator_memo();

		$this->assertEqualsWithDelta(
			13.40,
			$this->calculate_total_fees(),
			0.001,
			'GST must compound on duty: 8%*100 + 5%*(100+8) = 13.40.'
		);
	}

	/**
	 * @testdox Without a dependency, the same two rules are additive on their own bases.
	 *
	 * duty: 8% FOB on $100 = $8.00; gst: 5% FOB on $100 = $5.00; total = $13.00.
	 */
	public function test_independent_rules_do_not_compound(): void {
		update_option(
			'cfwc_rules',
			array(
				$this->ca_rule( 'duty', 8, 'CA Duty' ),
				$this->ca_rule( 'gst', 5, 'CA GST' ),
			)
		);
		$this->reset_calculator_memo();

		$this->assertEqualsWithDelta(
			13.00,
			$this->calculate_total_fees(),
			0.001,
			'Independent rules must not compound: 8%*100 + 5%*100 = 13.00.'
		);
	}
}
