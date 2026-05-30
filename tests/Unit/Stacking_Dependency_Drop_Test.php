<?php
/**
 * Unit tests for stacking-mode vs. base_includes dependency-drop detection.
 *
 * Covers the CUSFEES-12 review finding: when an override/exclusive rule removes
 * a rule that a surviving rule depends on (base_includes), CFWC_Rule_Matcher
 * records the dropped rule_id so the calculator can warn instead of silently
 * computing the dependent fee on an un-compounded base.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Rule_Matcher::get_last_stacking_dropped
 */
class Stacking_Dependency_Drop_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \CFWC_Rule_Matcher
	 */
	private $matcher;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->matcher = new \CFWC_Rule_Matcher();
	}

	/**
	 * Create a simple physical product.
	 *
	 * @return \WC_Product_Simple
	 */
	private function make_product(): \WC_Product_Simple {
		$product = new \WC_Product_Simple();
		$product->set_name( 'CFWC Stacking Test' );
		$product->set_regular_price( '100' );
		$product->set_price( '100' );
		$product->save();

		return $product;
	}

	/**
	 * Build a general CA rule.
	 *
	 * @param string $id       Rule ID.
	 * @param float  $rate     Percentage rate.
	 * @param string $label    Display label.
	 * @param string $stacking Stacking mode (add|override|exclusive).
	 * @param int    $priority Priority (higher sorts first).
	 * @param array  $deps     base_includes dependency ids.
	 * @return array Rule data.
	 */
	private function ca_rule( string $id, float $rate, string $label, string $stacking = 'add', int $priority = 0, array $deps = array() ): array {
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
			'stacking_mode'    => $stacking,
			'priority'         => $priority,
		);
	}

	/**
	 * @testdox An override rule that wipes a depended-on duty rule records it as stacking-dropped.
	 *
	 * Sorted by priority the set is [duty(10), override(5), gst(1)]: the override
	 * resets the survivor list (wiping the higher-priority duty rule) but allows
	 * the subsequent GST rule. GST still declares base_includes=[duty], so the
	 * dropped duty id must be recorded for the calculator to surface a warning.
	 */
	public function test_override_drop_of_dependency_is_recorded(): void {
		$rules = array(
			$this->ca_rule( 'duty', 8, 'CA Duty', 'add', 10 ),
			$this->ca_rule( 'ovr', 3, 'CA Override Fee', 'override', 5 ),
			$this->ca_rule( 'gst', 5, 'CA GST', 'add', 1, array( 'duty' ) ),
		);

		$survivors    = $this->matcher->find_matching_rules( $this->make_product(), 'US', 'CA', $rules );
		$dropped      = $this->matcher->get_last_stacking_dropped();
		$survivor_ids = wp_list_pluck( $survivors, 'rule_id' );

		$this->assertNotContains( 'duty', $survivor_ids, 'The override should wipe the higher-priority duty rule.' );
		$this->assertContains( 'gst', $survivor_ids, 'The GST rule added after the override should survive.' );
		$this->assertContains( 'duty', $dropped, 'The dropped duty dependency must be recorded so the calculator can warn.' );
	}

	/**
	 * @testdox An exclusive rule records every other matched rule as stacking-dropped.
	 */
	public function test_exclusive_drops_all_other_matches(): void {
		$rules = array(
			$this->ca_rule( 'excl', 9, 'CA Exclusive Fee', 'exclusive', 10 ),
			$this->ca_rule( 'duty', 8, 'CA Duty', 'add', 5 ),
			$this->ca_rule( 'gst', 5, 'CA GST', 'add', 1, array( 'duty' ) ),
		);

		$survivors = $this->matcher->find_matching_rules( $this->make_product(), 'US', 'CA', $rules );
		$dropped   = $this->matcher->get_last_stacking_dropped();

		$this->assertCount( 1, $survivors, 'Only the exclusive rule should survive.' );
		$this->assertContains( 'duty', $dropped );
		$this->assertContains( 'gst', $dropped );
	}

	/**
	 * @testdox A plain additive rule set drops nothing.
	 */
	public function test_additive_rules_drop_nothing(): void {
		$rules = array(
			$this->ca_rule( 'duty', 8, 'CA Duty', 'add', 10 ),
			$this->ca_rule( 'gst', 5, 'CA GST', 'add', 1, array( 'duty' ) ),
		);

		$this->matcher->find_matching_rules( $this->make_product(), 'US', 'CA', $rules );

		$this->assertSame(
			array(),
			$this->matcher->get_last_stacking_dropped(),
			'With no override/exclusive rule, nothing is stacking-filtered.'
		);
	}
}
