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
	 * @testdox Appending the compound preset over an existing general CA rule adds the GST rule and remaps its dependency.
	 *
	 * Regression coverage for CUSFEES-12 finding #1. Previously, the
	 * "conflicting general rules" dedup heuristic in apply_template() dropped
	 * any new general rule for a destination that already had one, regardless
	 * of label, silently discarding the GST rule and leaving the merchant
	 * non-compliant. The fix: detect duplicates by label and remap the GST
	 * rule's base_includes onto the existing duty rule's rule_id.
	 */
	public function test_append_over_existing_general_rule_preserves_dependency(): void {
		$existing = $this->existing_canada_duty_rule();
		update_option( 'cfwc_rules', $existing );

		$result = $this->sut->apply_template( 'canada_gst', true );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['added'], 'GST rule should be added.' );
		$this->assertSame( 1, $result['duplicates'], 'Duty rule should be detected as the duplicate.' );

		$rules = get_option( 'cfwc_rules', array() );
		$gst   = $this->find_by_label( $rules, 'Canada GST (Import)' );

		$this->assertCount( 2, $rules, 'Existing duty + new GST should be present.' );
		$this->assertNotNull( $gst, 'The GST rule should be added even when a duty rule already exists.' );
		$this->assertNotEmpty( $gst['base_includes'], 'The GST rule must keep a resolvable duty dependency.' );
		$this->assertSame(
			$existing[0]['rule_id'],
			$gst['base_includes'][0],
			'GST base_includes must be remapped to the existing duty rule_id, not the preset placeholder.'
		);
	}

	/**
	 * @testdox Same-label, different-country rule is not treated as a duplicate.
	 */
	public function test_append_does_not_dedup_across_countries(): void {
		update_option(
			'cfwc_rules',
			array(
				array(
					'rule_id'          => 'manual_us_duty',
					'country'          => 'US',
					'to_country'       => 'US',
					'from_country'     => '',
					'match_type'       => 'all',
					'type'             => 'percentage',
					'rate'             => 5,
					'amount'           => 0,
					'label'            => 'Canada Duty (Import)',
					'taxable'          => false,
					'tax_class'        => '',
					'valuation_method' => 'inherit',
					'base_includes'    => array(),
				),
			)
		);

		$result = $this->sut->apply_template( 'canada_gst', true );

		$this->assertSame( 2, $result['added'], 'Both CA rules should be added; US rule with same label is irrelevant.' );
		$this->assertSame( 0, $result['duplicates'] );
	}

	/**
	 * @testdox Same-country, different-label general rule is not treated as a duplicate.
	 *
	 * Guards against re-introducing the over-broad "conflicting general
	 * rules" heuristic. Two general rules to the same destination with
	 * distinct labels are the whole point of compound presets.
	 */
	public function test_append_does_not_dedup_general_rules_by_country_alone(): void {
		update_option(
			'cfwc_rules',
			array(
				array(
					'rule_id'          => 'manual_ca_other',
					'country'          => 'CA',
					'to_country'       => 'CA',
					'from_country'     => '',
					'match_type'       => 'all',
					'type'             => 'percentage',
					'rate'             => 2,
					'amount'           => 0,
					'label'            => 'Some Other CA Fee',
					'taxable'          => false,
					'tax_class'        => '',
					'valuation_method' => 'inherit',
					'base_includes'    => array(),
				),
			)
		);

		$result = $this->sut->apply_template( 'canada_gst', true );

		$this->assertSame( 2, $result['added'], 'Both preset rules should be added; existing rule has a different label.' );
		$this->assertSame( 0, $result['duplicates'] );
	}
}
