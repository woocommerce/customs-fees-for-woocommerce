<?php
/**
 * Unit tests for base_includes dependency cycle detection in CFWC_Calculator.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Calculator::has_cycle
 * @covers \CFWC_Calculator::detect_cycle_labels
 */
class Cycle_Detection_Test extends \WC_Unit_Test_Case {

	/**
	 * Build a minimal rule with a rule_id and a base_includes list.
	 *
	 * @param string $id   Rule ID.
	 * @param array  $deps Dependency rule IDs.
	 * @return array Rule data.
	 */
	private function rule( string $id, array $deps = array() ): array {
		return array(
			'rule_id'       => $id,
			'label'         => $id,
			'base_includes' => $deps,
		);
	}

	/**
	 * Cycle membership scenarios.
	 *
	 * @return array<string,array{0:array<int,array<string,mixed>>,1:string,2:bool}>
	 */
	public function cycle_membership_data(): array {
		$self    = array( $this->rule( 'A', array( 'A' ) ) );
		$mutual  = array( $this->rule( 'A', array( 'B' ) ), $this->rule( 'B', array( 'A' ) ) );
		$diamond = array(
			$this->rule( 'A', array( 'B', 'C' ) ),
			$this->rule( 'B' ),
			$this->rule( 'C', array( 'B' ) ),
		);
		$preset   = array( $this->rule( 'gst', array( 'duty' ) ), $this->rule( 'duty' ) );
		$upstream = array(
			$this->rule( 'X', array( 'A' ) ),
			$this->rule( 'A', array( 'B' ) ),
			$this->rule( 'B', array( 'A' ) ),
		);

		return array(
			'self-loop is a cycle'               => array( $self, 'A', true ),
			'mutual A<->B: A is a cycle'         => array( $mutual, 'A', true ),
			'mutual A<->B: B is a cycle'         => array( $mutual, 'B', true ),
			'diamond apex is not a cycle'        => array( $diamond, 'A', false ),
			'diamond shared dep is not a cycle'  => array( $diamond, 'C', false ),
			'canada gst->duty is not a cycle'    => array( $preset, 'gst', false ),
			'upstream of a cycle is not a cycle' => array( $upstream, 'X', false ),
			'unknown rule id is not a cycle'     => array( $preset, 'missing', false ),
		);
	}

	/**
	 * @testdox has_cycle() flags only rules that loop back to themselves.
	 * @dataProvider cycle_membership_data
	 *
	 * @param array  $rules    Rule set.
	 * @param string $start_id Rule under test.
	 * @param bool   $expected Whether the rule participates in a cycle.
	 */
	public function test_has_cycle_membership( array $rules, string $start_id, bool $expected ): void {
		$this->assertSame(
			$expected,
			\CFWC_Calculator::has_cycle( $rules, $start_id ),
			"has_cycle() returned the wrong result for '{$start_id}'."
		);
	}

	/**
	 * @testdox detect_cycle_labels() returns only the rules inside the cycle, deduplicated.
	 */
	public function test_detect_cycle_labels_returns_only_cyclic_rules(): void {
		$rules = array(
			$this->rule( 'X', array( 'A' ) ),
			$this->rule( 'A', array( 'B' ) ),
			$this->rule( 'B', array( 'A' ) ),
		);

		$labels = \CFWC_Calculator::detect_cycle_labels( $rules );
		sort( $labels );

		$this->assertSame(
			array( 'A', 'B' ),
			$labels,
			'Only the two mutually-dependent rules should be reported as cyclic.'
		);
	}

	/**
	 * @testdox detect_cycle_labels() returns an empty array for an acyclic rule set.
	 */
	public function test_detect_cycle_labels_empty_when_acyclic(): void {
		$rules = array(
			$this->rule( 'gst', array( 'duty' ) ),
			$this->rule( 'duty' ),
		);

		$this->assertSame(
			array(),
			\CFWC_Calculator::detect_cycle_labels( $rules ),
			'An acyclic rule set must report no cyclic labels.'
		);
	}
}
