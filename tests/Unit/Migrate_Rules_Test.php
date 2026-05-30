<?php
/**
 * Unit tests for the per-rule field migration in CFWC_Settings::migrate_rules().
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Settings::migrate_rules
 */
class Migrate_Rules_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox migrate_rules() backfills the v1.2.0 fields on a legacy rule.
	 */
	public function test_backfills_missing_fields(): void {
		$legacy = array(
			array(
				'country' => 'CA',
				'type'    => 'percentage',
				'rate'    => 5,
				'label'   => 'Legacy',
			),
		);

		$migrated = \CFWC_Settings::migrate_rules( $legacy );

		$this->assertNotEmpty( $migrated[0]['rule_id'], 'A rule_id must be assigned.' );
		$this->assertSame( 'inherit', $migrated[0]['valuation_method'], 'Default valuation must be inherit.' );
		$this->assertSame( array(), $migrated[0]['base_includes'], 'base_includes must default to an empty array.' );
	}

	/**
	 * @testdox migrate_rules() is idempotent: a second pass does not rotate the rule_id.
	 */
	public function test_is_idempotent(): void {
		$legacy = array(
			array(
				'country' => 'CA',
				'type'    => 'percentage',
				'rate'    => 5,
				'label'   => 'Legacy',
			),
		);

		$first  = \CFWC_Settings::migrate_rules( $legacy );
		$second = \CFWC_Settings::migrate_rules( $first );

		$this->assertSame(
			$first[0]['rule_id'],
			$second[0]['rule_id'],
			'A stable rule_id must survive a second migration pass.'
		);
	}

	/**
	 * @testdox migrate_rules() preserves an existing rule_id and valid valuation_method.
	 */
	public function test_preserves_existing_values(): void {
		$rules = array(
			array(
				'rule_id'          => 'canada_gst_duty',
				'country'          => 'CA',
				'type'             => 'percentage',
				'rate'             => 8,
				'label'            => 'Canada Duty (Import)',
				'valuation_method' => 'fob',
				'base_includes'    => array(),
			),
		);

		$migrated = \CFWC_Settings::migrate_rules( $rules );

		$this->assertSame( 'canada_gst_duty', $migrated[0]['rule_id'], 'An existing rule_id must be preserved.' );
		$this->assertSame( 'fob', $migrated[0]['valuation_method'], 'A valid valuation_method must be preserved.' );
	}

	/**
	 * @testdox migrate_rules() normalizes an invalid valuation_method back to inherit.
	 */
	public function test_normalizes_invalid_valuation_method(): void {
		$rules = array(
			array(
				'rule_id'          => 'r1',
				'country'          => 'CA',
				'type'             => 'percentage',
				'rate'             => 8,
				'label'            => 'Bad valuation',
				'valuation_method' => 'bogus',
				'base_includes'    => array(),
			),
		);

		$migrated = \CFWC_Settings::migrate_rules( $rules );

		$this->assertSame(
			'inherit',
			$migrated[0]['valuation_method'],
			'An unrecognized valuation_method must fall back to inherit.'
		);
	}

	/**
	 * @testdox migrate_rules() returns an empty array for non-array input.
	 */
	public function test_returns_empty_array_for_non_array_input(): void {
		$this->assertSame(
			array(),
			\CFWC_Settings::migrate_rules( 'not-an-array' ),
			'Non-array input must yield an empty array, never a fatal.'
		);
	}
}
