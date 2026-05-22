<?php
/**
 * Unit tests for compound-preset behavior in CFWC_Templates::apply_template().
 *
 * Covers CUSFEES-12 finding #1: appending a two-rule compound preset (duty +
 * tax-with-base_includes) to a store that already has a general rule for the
 * same destination silently drops the new rules, so the dependency that makes
 * the fix work is never added.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Templates::apply_template
 */
class Preset_Append_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \CFWC_Templates
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new \CFWC_Templates();
		delete_option( 'cfwc_rules' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'cfwc_rules' );
		parent::tearDown();
	}

	/**
	 * Find a saved rule by its label.
	 *
	 * @param array  $rules Saved rules.
	 * @param string $label Label to match.
	 * @return array|null The matching rule, or null.
	 */
	private function find_by_label( array $rules, string $label ): ?array {
		foreach ( $rules as $rule ) {
			if ( isset( $rule['label'] ) && $label === $rule['label'] ) {
				return $rule;
			}
		}
		return null;
	}

	/**
	 * A pre-existing manual general rule for Canada (different rule_id than the preset).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function existing_canada_duty_rule(): array {
		return array(
			array(
				'rule_id'          => 'manual_uuid_123',
				'country'          => 'CA',
				'to_country'       => 'CA',
				'from_country'     => '',
				'match_type'       => 'all',
				'type'             => 'percentage',
				'rate'             => 8,
				'amount'           => 0,
				'label'            => 'Canada Duty (Import)',
				'taxable'          => false,
				'tax_class'        => '',
				'valuation_method' => 'inherit',
				'base_includes'    => array(),
				'stacking_mode'    => 'add',
				'priority'         => 0,
			),
		);
	}

	/**
	 * @testdox Appending the Canada preset to an empty store links GST to duty.
	 */
	public function test_append_to_empty_store_links_dependency(): void {
		$this->sut->apply_template( 'canada_gst', true );

		$rules = get_option( 'cfwc_rules', array() );
		$gst   = $this->find_by_label( $rules, 'Canada GST (Import)' );
		$duty  = $this->find_by_label( $rules, 'Canada Duty (Import)' );

		$this->assertNotNull( $duty, 'The duty rule should be added.' );
		$this->assertNotNull( $gst, 'The GST rule should be added.' );
		$this->assertContains(
			$duty['rule_id'],
			$gst['base_includes'],
			'GST must depend on the duty rule so its base is CIF + Duty.'
		);
	}

	/**
	 * @testdox CHARACTERIZATION (current bug): appending over an existing general CA rule drops the new rules.
	 *
	 * Documents the behavior observed during the CUSFEES-12 review. The
	 * "conflicting general rules" dedup heuristic in apply_template() drops any
	 * new general rule for a destination that already has one, regardless of
	 * label, so both compound-preset rules are silently discarded. Update this
	 * test (and remove the skip on the desired-behavior test below) once the
	 * dedup logic is fixed.
	 */
	public function test_append_over_existing_general_rule_drops_new_rules_current_behavior(): void {
		update_option( 'cfwc_rules', $this->existing_canada_duty_rule() );

		$this->sut->apply_template( 'canada_gst', true );

		$rules = get_option( 'cfwc_rules', array() );

		$this->assertCount(
			1,
			$rules,
			'Current behavior: appending the compound Canada preset over an existing general CA rule adds nothing.'
		);
		$this->assertNull(
			$this->find_by_label( $rules, 'Canada GST (Import)' ),
			'Current behavior: the GST rule is silently dropped, so the compliance fix is not adopted.'
		);
	}

	/**
	 * @testdox DESIRED (Finding #1 fix): appending the preset preserves the GST->duty dependency.
	 */
	public function test_append_over_existing_general_rule_preserves_dependency_desired(): void {
		$this->markTestSkipped(
			'CUSFEES-12 finding #1: apply_template drops compound-preset rules when a general rule '
			. 'for the same destination already exists. Unskip once the dedup heuristic is fixed.'
		);

		update_option( 'cfwc_rules', $this->existing_canada_duty_rule() );

		$this->sut->apply_template( 'canada_gst', true );

		$rules = get_option( 'cfwc_rules', array() );
		$gst   = $this->find_by_label( $rules, 'Canada GST (Import)' );

		$this->assertNotNull( $gst, 'The GST rule should be added even when a duty rule already exists.' );
		$this->assertNotEmpty( $gst['base_includes'], 'The GST rule must keep a resolvable duty dependency.' );

		$dep_id     = $gst['base_includes'][0];
		$dep_exists = false;
		foreach ( $rules as $rule ) {
			if ( isset( $rule['rule_id'] ) && $dep_id === $rule['rule_id'] ) {
				$dep_exists = true;
				break;
			}
		}
		$this->assertTrue( $dep_exists, 'The GST dependency must point at a rule that actually exists (no dangling id).' );
	}
}
