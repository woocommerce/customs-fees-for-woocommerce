<?php
/**
 * Unit tests for the china_to_us preset rates in CFWC_Templates.
 *
 * Covers CUSFEES-20: the China -> US preset rates were calibrated during the
 * IEEPA tariff period (April 2025 - February 2026). IEEPA tariffs were struck
 * down in February 2026, so the preset must reflect the durable post-IEEPA
 * regime (MFN base + Section 301 + Section 232). The apparel rule in
 * particular was overstated at 69 percent and should be ~24 percent.
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
	 * @testdox The china_to_us preset reflects the post-IEEPA durable tariff rates.
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
	 * Expected post-IEEPA rates per HS pattern.
	 *
	 * @return array<string,array{0:string,1:float}>
	 */
	public function provide_expected_rates(): array {
		return array(
			'apparel'      => array( '61*,62*', 24.0 ),
			'electronics'  => array( '85*', 25.0 ),
			'solar'        => array( '8541*,8542*', 50.0 ),
			'ev'           => array( '8703.80*', 100.0 ),
			'auto_parts'   => array( '8708*', 52.5 ),
			'steel'        => array( '72*,73*', 78.0 ),
			'aluminum'     => array( '76*', 78.0 ),
			'batteries'    => array( '8506*,8507*', 28.0 ),
			'syringes'     => array( '9018.31*,9018.32*', 100.0 ),
			'appl_machine' => array( '8419*', 28.0 ),
			'appl_metal'   => array( '8418*,8450*,8451*', 76.0 ),
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
}
