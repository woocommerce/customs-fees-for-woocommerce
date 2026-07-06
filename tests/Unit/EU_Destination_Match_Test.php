<?php
/**
 * Unit tests for "EU" as a destination pseudo-code in rule matching.
 *
 * Covers CUSFEES-22: the "EU VAT & Duty" preset stores its rules with the
 * destination set to the "EU" pseudo-code, which the rule matcher expands to
 * all EU member states. The admin rule editor did not offer "EU" as a
 * selectable destination, so editing (or re-saving) a preset rule silently
 * dropped the "EU" value back to "Any" -- making the fee apply to every
 * country. The dropdown fix relies on "EU" being a valid, matchable
 * destination value; these tests guard that backend contract so the value the
 * dropdown now preserves keeps behaving as intended.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Rule_Matcher::find_matching_rules
 */
class EU_Destination_Match_Test extends \WC_Unit_Test_Case {

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
		$product->set_name( 'CFWC EU Destination Test' );
		$product->set_regular_price( '100' );
		$product->set_price( '100' );
		$product->save();

		return $product;
	}

	/**
	 * Build an EU-destination rule.
	 *
	 * @param bool $legacy When true, only the legacy `country` field carries
	 *                     the destination (no `to_country`), mirroring how the
	 *                     "EU VAT & Duty" preset stores its rules.
	 * @return array Rule data.
	 */
	private function eu_rule( bool $legacy = false ): array {
		$rule = array(
			'rule_id'          => 'eu_import',
			'from_country'     => '',
			'match_type'       => 'all',
			'type'             => 'percentage',
			'rate'             => 20,
			'amount'           => 0,
			'label'            => 'EU Import',
			'taxable'          => false,
			'tax_class'        => '',
			'valuation_method' => 'cif',
			'base_includes'    => array(),
			'stacking_mode'    => 'add',
			'priority'         => 0,
		);

		if ( $legacy ) {
			// Legacy shape: destination lives in `country` only.
			$rule['country'] = 'EU';
		} else {
			$rule['country']    = 'EU';
			$rule['to_country'] = 'EU';
		}

		return $rule;
	}

	/**
	 * @testdox An EU-destination rule matches an EU member state (Germany).
	 */
	public function test_eu_rule_matches_eu_destination(): void {
		$survivors = $this->matcher->find_matching_rules( $this->make_product(), 'US', 'DE', array( $this->eu_rule() ) );

		$this->assertContains(
			'eu_import',
			wp_list_pluck( $survivors, 'rule_id' ),
			'A rule targeting the EU should match a German (EU) destination.'
		);
	}

	/**
	 * @testdox An EU-destination rule does NOT match a non-EU destination (US).
	 *
	 * This is the core of the bug: when the "EU" value is silently dropped to
	 * "Any", the fee applies everywhere. As long as the destination is "EU",
	 * a non-EU order must not match.
	 */
	public function test_eu_rule_does_not_match_non_eu_destination(): void {
		$survivors = $this->matcher->find_matching_rules( $this->make_product(), 'CN', 'US', array( $this->eu_rule() ) );

		$this->assertNotContains(
			'eu_import',
			wp_list_pluck( $survivors, 'rule_id' ),
			'A rule targeting the EU must not match a US (non-EU) destination.'
		);
	}

	/**
	 * @testdox A legacy EU rule (destination in `country` only) still matches EU and not non-EU.
	 *
	 * The "EU VAT & Duty" preset stores its rules with the destination in the
	 * legacy `country` field. The matcher must treat that identically to
	 * `to_country`, otherwise a freshly applied preset would behave
	 * differently from an edited one.
	 */
	public function test_legacy_eu_rule_matches_eu_only(): void {
		$product = $this->make_product();

		$matches_eu = wp_list_pluck(
			$this->matcher->find_matching_rules( $product, 'US', 'FR', array( $this->eu_rule( true ) ) ),
			'rule_id'
		);
		$matches_non_eu = wp_list_pluck(
			$this->matcher->find_matching_rules( $product, 'US', 'GB', array( $this->eu_rule( true ) ) ),
			'rule_id'
		);

		$this->assertContains( 'eu_import', $matches_eu, 'A legacy EU rule should match a French (EU) destination.' );
		$this->assertNotContains( 'eu_import', $matches_non_eu, 'A legacy EU rule must not match a UK (non-EU) destination.' );
	}

	/**
	 * @testdox The "EU VAT & Duty" preset targets the EU destination pseudo-code.
	 *
	 * Guards the config the admin dropdown must be able to represent: if the
	 * preset ever stored a real ISO code (or "Any") here, the dropdown-fix
	 * rationale would no longer hold.
	 */
	public function test_eu_vat_preset_targets_eu_destination(): void {
		$templates = new \CFWC_Templates();
		$preset    = $templates->get_template( 'eu_vat' );

		$this->assertNotNull( $preset, 'The eu_vat preset should exist.' );
		$this->assertNotEmpty( $preset['rules'], 'The eu_vat preset should define rules.' );

		foreach ( $preset['rules'] as $rule ) {
			$destination = $rule['to_country'] ?? $rule['country'] ?? '';
			$this->assertSame(
				'EU',
				$destination,
				'Every eu_vat preset rule should target the EU destination pseudo-code.'
			);
		}
	}
}
