<?php
/**
 * Unit tests for the china_to_us preset rates in CFWC_Templates.
 *
 * Covers CUSFEES-20: the China -> US preset rates were calibrated during the
 * IEEPA tariff period (February 2025 - February 2026). IEEPA tariffs were struck
 * down on 20 February 2026, so the preset must reflect the durable post-IEEPA
 * regime (MFN base + Section 301 + Section 232). The apparel rule in
 * particular was overstated at 69 percent and should be ~24 percent.
 *
 * The asserted rates are the intended *representative* per-category preset
 * values (most MFN/Section 301 components vary by HS line), not exact legal
 * duties. These tests guard the intended configuration, not statutory precision.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Templates::get_template
 */
class China_Preset_Rates_Test extends \WC_Unit_Test_Case {

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
	}

	/**
	 * Find a china_to_us rule by its hs_code_pattern.
	 *
	 * @param string $pattern The hs_code_pattern to match.
	 * @return array<string,mixed>|null The matching rule, or null.
	 */
	private function rule_by_pattern( string $pattern ): ?array {
		$template = $this->sut->get_template( 'china_to_us' );
		foreach ( $template['rules'] as $rule ) {
			if ( isset( $rule['hs_code_pattern'] ) && $pattern === $rule['hs_code_pattern'] ) {
				return $rule;
			}
		}
		return null;
	}

	/**
	 * The MFN fallback rule has no hs_code_pattern, so match it by match_type.
	 *
	 * @return array<string,mixed>|null The matching rule, or null.
	 */
	private function mfn_rule(): ?array {
		$template = $this->sut->get_template( 'china_to_us' );
		foreach ( $template['rules'] as $rule ) {
			if ( isset( $rule['match_type'] ) && 'all' === $rule['match_type'] ) {
				return $rule;
			}
		}
		return null;
	}

	/**
	 * @testdox The china_to_us preset uses the intended representative post-IEEPA durable rates.
	 *
	 * @dataProvider provide_expected_rates
	 *
	 * @param string $pattern The hs_code_pattern identifying the rule.
	 * @param float  $expected The expected post-IEEPA rate.
	 */
	public function test_preset_rates_are_post_ieepa( string $pattern, float $expected ): void {
		$rule = $this->rule_by_pattern( $pattern );
		$this->assertNotNull( $rule, "Rule for pattern {$pattern} should exist." );
		$this->assertEqualsWithDelta(
			$expected,
			(float) $rule['rate'],
			0.001,
			"Rule {$pattern} should use the post-IEEPA rate {$expected}."
		);
	}

	/**
	 * Intended representative post-IEEPA rates per HS pattern.
	 *
	 * Values are per-category representatives (MFN base + Section 301, plus
	 * Section 232 where applicable), not exact HTS duties. EVs are MFN 2.5% +
	 * Section 301 100% (= 102.5%); appliances are Annex I-B derivatives at
	 * Section 232 25% on full value (~51.5%); steel/aluminum are the primary
	 * metal high-end (finished Annex I-B derivatives run lower, ~53%).
	 *
	 * @return array<string,array{0:string,1:float}>
	 */
	public function provide_expected_rates(): array {
		return array(
			'apparel'      => array( '61*,62*', 24.0 ),
			'electronics'  => array( '85*', 25.0 ),
			'solar'        => array( '8541*,8542*', 50.0 ),
			'ev'           => array( '8703.80*', 102.5 ),
			'auto_parts'   => array( '8708*', 52.5 ),
			'steel'        => array( '72*,73*', 78.0 ),
			'aluminum'     => array( '76*', 78.0 ),
			'batteries'    => array( '8506*,8507*', 28.0 ),
			'syringes'     => array( '9018.31*,9018.32*', 100.0 ),
			'appl_machine' => array( '8419*', 28.0 ),
			'appl_metal'   => array( '8418*,8450*,8451*', 51.5 ),
			'footwear'     => array( '64*', 27.0 ),
			'leather'      => array( '4202*', 35.0 ),
			'toys'         => array( '95*', 7.5 ),
			'chemicals'    => array( '28*,29*,38*', 28.0 ),
		);
	}

	/**
	 * @testdox The china_to_us MFN fallback uses the post-IEEPA baseline rate.
	 */
	public function test_mfn_fallback_rate_is_post_ieepa(): void {
		$rule = $this->mfn_rule();
		$this->assertNotNull( $rule, 'The MFN fallback rule should exist.' );
		$this->assertEqualsWithDelta( 3.0, (float) $rule['rate'], 0.001, 'MFN baseline should be ~3 percent.' );
	}

	/**
	 * @testdox No china_to_us rate is still set at the IEEPA-era apparel value of 69 percent.
	 */
	public function test_apparel_rate_is_not_ieepa_value(): void {
		$rule = $this->rule_by_pattern( '61*,62*' );
		$this->assertNotNull( $rule );
		$this->assertNotEquals( 69, (float) $rule['rate'], 'The IEEPA-era 69 percent apparel rate must be removed.' );
	}

	/**
	 * @testdox The china_to_us preset still has exactly one rule per category after the appliance split.
	 *
	 * Guards against accidental duplication when the appliance rule was split into a
	 * steel-derivative rule (8418/8450/8451) and an industrial-machinery rule (8419).
	 */
	public function test_rule_count_is_stable(): void {
		$template = $this->sut->get_template( 'china_to_us' );
		$this->assertCount( 16, $template['rules'], 'The china_to_us preset should have exactly 16 rules.' );
	}

	/**
	 * @testdox The china_to_us preset name reflects the post-IEEPA (2026) recalibration.
	 */
	public function test_preset_name_reflects_2026(): void {
		$template = $this->sut->get_template( 'china_to_us' );
		$this->assertStringContainsString( '2026', $template['name'], 'The preset name should reflect the 2026 recalibration.' );
	}

	/**
	 * @testdox The struck-down IEEPA "fentanyl" surcharge is no longer referenced in the preset.
	 */
	public function test_no_fentanyl_reference(): void {
		$template = $this->sut->get_template( 'china_to_us' );
		$this->assertStringNotContainsStringIgnoringCase(
			'fentanyl',
			$template['description'],
			'The preset description should not cite the struck-down IEEPA fentanyl surcharge.'
		);
		foreach ( $template['rules'] as $rule ) {
			if ( isset( $rule['label'] ) ) {
				$this->assertStringNotContainsStringIgnoringCase(
					'fentanyl',
					$rule['label'],
					'No rule label should cite the struck-down IEEPA fentanyl surcharge.'
				);
			}
		}
	}

	/**
	 * @testdox The preset description discloses the representative basis and excludes the temporary Section 122 surcharge.
	 */
	public function test_description_discloses_section_122_exclusion(): void {
		$template = $this->sut->get_template( 'china_to_us' );
		$this->assertStringNotContainsStringIgnoringCase(
			'No additional US import rules needed',
			$template['description'],
			'The description must not claim no additional rules apply while the temporary Section 122 surcharge is in effect.'
		);
		$this->assertStringContainsString(
			'Section 122',
			$template['description'],
			'The description should disclose that the temporary Section 122 surcharge is excluded.'
		);
		$this->assertStringContainsStringIgnoringCase(
			'representative',
			$template['description'],
			'The description should state that the rates are representative, not exact duties.'
		);
	}
}
