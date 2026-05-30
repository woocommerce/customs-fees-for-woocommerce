<?php
/**
 * Unit tests for save-time detection of stacking-broken base_includes dependencies.
 *
 * Covers case (a) of the stacking/compounding interaction: a general rule
 * survives stacking but a rule it depends on is removed by a higher-priority
 * override/exclusive rule for the same destination, so its fee compounds on an
 * incomplete base. CFWC_Rule_Matcher::detect_broken_dependencies() derives this
 * from the saved rules so the settings page can warn without a cart calculation.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Rule_Matcher::detect_broken_dependencies
 */
class Broken_Dependency_Detection_Test extends \WC_Unit_Test_Case {

	/**
	 * Build a general CA rule.
	 *
	 * @param string $id         Rule ID.
	 * @param float  $rate       Percentage rate.
	 * @param string $label      Display label.
	 * @param string $stacking   Stacking mode (add|override|exclusive).
	 * @param int    $priority   Priority (higher sorts first).
	 * @param array  $deps       base_includes dependency ids.
	 * @param string $match_type Match type (all|category|hs_code|combined).
	 * @param string $from       Origin restriction (empty = any origin).
	 * @return array Rule data.
	 */
	private function rule( string $id, float $rate, string $label, string $stacking = 'add', int $priority = 0, array $deps = array(), string $match_type = 'all', string $from = '' ): array {
		return array(
			'rule_id'          => $id,
			'country'          => 'CA',
			'to_country'       => 'CA',
			'from_country'     => $from,
			'origin_country'   => '',
			'match_type'       => $match_type,
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
	 * @testdox A surviving rule whose dependency is wiped by an override is flagged.
	 *
	 * Sorted [duty(10), override(5), gst(1)]: the override wipes duty but the
	 * lower-priority GST survives, and GST still depends on the now-gone duty.
	 */
	public function test_surviving_rule_with_dropped_dependency_is_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10 ),
			$this->rule( 'ovr', 3, 'Canada (Custom)', 'override', 5 ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ) ),
		);

		$this->assertSame(
			array( 'Canada GST (Import)' ),
			\CFWC_Rule_Matcher::detect_broken_dependencies( $rules )
		);
	}

	/**
	 * @testdox Plain additive rules are never flagged.
	 */
	public function test_plain_additive_rules_not_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10 ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ) ),
		);

		$this->assertSame( array(), \CFWC_Rule_Matcher::detect_broken_dependencies( $rules ) );
	}

	/**
	 * @testdox When the override wipes the whole chain (dependent included) nothing is flagged.
	 *
	 * Override at the lowest priority sorts last and replaces every rule before
	 * it, so GST itself does not survive. Replacing rules is the documented job
	 * of override/exclusive, so case (a) deliberately does not report this.
	 */
	public function test_whole_chain_replacement_not_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10 ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 5, array( 'duty' ) ),
			$this->rule( 'ovr', 3, 'Canada (Custom)', 'override', 1 ),
		);

		$this->assertSame( array(), \CFWC_Rule_Matcher::detect_broken_dependencies( $rules ) );
	}

	/**
	 * @testdox An exclusive rule that leaves no dependent surviving is not flagged.
	 */
	public function test_exclusive_leaving_no_dependent_not_flagged(): void {
		$rules = array(
			$this->rule( 'excl', 9, 'Canada (Custom)', 'exclusive', 10 ),
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 5 ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ) ),
		);

		// Only the exclusive rule survives; GST is gone, so there is no surviving
		// dependent to warn about.
		$this->assertSame( array(), \CFWC_Rule_Matcher::detect_broken_dependencies( $rules ) );
	}

	/**
	 * @testdox Category/HS-code rules are excluded from the static check.
	 *
	 * Their co-occurrence is product-dependent, so they are left to the
	 * calculator's runtime warning rather than flagged at save time.
	 */
	public function test_category_rules_excluded(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10, array(), 'category' ),
			$this->rule( 'ovr', 3, 'Canada (Custom)', 'override', 5, array(), 'category' ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ), 'category' ),
		);

		$this->assertSame( array(), \CFWC_Rule_Matcher::detect_broken_dependencies( $rules ) );
	}

	/**
	 * @testdox Rules restricted to the same origin are analysed together and flagged.
	 */
	public function test_same_origin_restricted_rules_are_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10, array(), 'all', 'US' ),
			$this->rule( 'ovr', 3, 'Canada (Custom)', 'override', 5, array(), 'all', 'US' ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ), 'all', 'US' ),
		);

		$this->assertSame(
			array( 'Canada GST (Import)' ),
			\CFWC_Rule_Matcher::detect_broken_dependencies( $rules )
		);
	}

	/**
	 * @testdox An origin-restricted override breaking an any-origin dependent is flagged.
	 *
	 * Mirrors the real saved state: duty + override carry an origin (CA) while
	 * the GST rule applies to any origin. For a CA-origin product all three
	 * co-occur, so the override wipes duty and the surviving GST is left
	 * compounding on an incomplete base.
	 */
	public function test_mixed_any_and_specific_origin_is_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'Canada Duty (Import)', 'add', 10, array(), 'all', 'CA' ),
			$this->rule( 'ovr', 3, 'Canada (Custom)', 'override', 5, array(), 'all', 'CA' ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ), 'all', '' ),
		);

		$this->assertSame(
			array( 'Canada GST (Import)' ),
			\CFWC_Rule_Matcher::detect_broken_dependencies( $rules )
		);
	}

	/**
	 * @testdox Rules restricted to different origins are never analysed together (no false positive).
	 *
	 * The duty/override apply only to US-origin products; the GST rule only to
	 * CA-origin products. No single product matches both groups, so the GST
	 * dependency on the (different-origin) duty is not a stacking break.
	 */
	public function test_different_origin_rules_not_flagged(): void {
		$rules = array(
			$this->rule( 'duty', 8, 'US Duty', 'add', 10, array(), 'all', 'US' ),
			$this->rule( 'ovr', 3, 'US Override', 'override', 5, array(), 'all', 'US' ),
			$this->rule( 'gst', 5, 'Canada GST (Import)', 'add', 1, array( 'duty' ), 'all', 'CA' ),
		);

		$this->assertSame( array(), \CFWC_Rule_Matcher::detect_broken_dependencies( $rules ) );
	}

	/**
	 * @testdox The dismissal signature is order-independent and empty for no conflict.
	 */
	public function test_signature_is_order_independent_and_empty_for_none(): void {
		$this->assertSame( '', \CFWC_Rule_Matcher::broken_dependency_signature( array() ) );

		$this->assertSame(
			\CFWC_Rule_Matcher::broken_dependency_signature( array( 'Canada GST (Import)', 'EU VAT (Import)' ) ),
			\CFWC_Rule_Matcher::broken_dependency_signature( array( 'EU VAT (Import)', 'Canada GST (Import)' ) ),
			'Signature must not depend on label order.'
		);
	}

	/**
	 * @testdox A changed set of affected rules produces a different signature so the notice re-surfaces.
	 */
	public function test_signature_changes_when_affected_rules_change(): void {
		$this->assertNotSame(
			\CFWC_Rule_Matcher::broken_dependency_signature( array( 'Canada GST (Import)' ) ),
			\CFWC_Rule_Matcher::broken_dependency_signature( array( 'Canada GST (Import)', 'EU VAT (Import)' ) )
		);
	}
}
