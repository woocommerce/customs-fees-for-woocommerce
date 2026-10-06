import { expect, type Page } from '@playwright/test';

// The plugin's settings live as a section of WooCommerce > Settings > Tax.
// The slug comes from CFWC_Settings::add_customs_section().
export const CUSTOMS_SETTINGS_PATH =
	'wp-admin/admin.php?page=wc-settings&tab=tax&section=customs';

// CFWC_Loader::add_customs_fees() adds ONE combined fee under this name,
// whatever the matching rules are called; rule labels only appear in its
// breakdown. It is a translatable string - the suite assumes the English
// locale wp-env and QIT run by default.
export const CUSTOMS_FEE_NAME = 'Customs & Import Fees';

export interface CustomsRule {
	label: string;
	from_country: string;
	to_country: string;
	type: 'percentage' | 'flat';
	rate: number;
	amount: number;
	match_type: 'all';
	stacking_mode: 'add';
}

/** A rule as CFWC_Settings::save_rules() stores it. */
export interface StoredRule extends CustomsRule {
	rule_id: string;
}

/** 10% on goods made in China shipped to the United Kingdom. */
export const IMPORT_DUTY_RULE: CustomsRule = {
	label: 'E2E Import Duty',
	from_country: 'CN',
	to_country: 'GB',
	type: 'percentage',
	rate: 10,
	amount: 0,
	match_type: 'all',
	stacking_mode: 'add',
};

const ruleKey = ( rule: CustomsRule ) =>
	`${ rule.label } | ${ rule.from_country } -> ${ rule.to_country }`;

/**
 * Read the stored rule set from the settings screen.
 *
 * The screen prints the saved `cfwc_rules` option into the hidden
 * #cfwc_rules field, so this reads exactly what the calculator will read.
 */
export const getRules = async ( page: Page ): Promise< StoredRule[] > => {
	await page.goto( CUSTOMS_SETTINGS_PATH );
	const raw = await page.locator( '#cfwc_rules' ).inputValue();
	return JSON.parse( raw || '[]' ) as StoredRule[];
};

/**
 * Replace the whole rule set through the plugin's own save path.
 *
 * Submits the real settings form (its nonce, referer and every global field at
 * its current value) with `cfwc_rules` swapped for the given rules, so
 * CFWC_Settings::save_rules() sanitizes, migrates and stores them and clears
 * the cfwc_rules_cache transient - the stored shape is the plugin's, not this
 * suite's. Every field is carried over because save_customs_settings() also
 * runs save_global_settings(), which writes an absent cfwc_use_original_price
 * checkbox as 'no'.
 *
 * Verified by reading the rules back, so a rejected nonce or a no-op save
 * fails here instead of later as a missing fee.
 */
export const setRules = async (
	page: Page,
	rules: CustomsRule[]
): Promise< void > => {
	await page.goto( CUSTOMS_SETTINGS_PATH );

	const fields = await page.locator( '#mainform' ).evaluate( ( element ) => {
		const form = element as HTMLFormElement;
		const out: Record< string, string > = {};
		new FormData( form ).forEach( ( value, key ) => {
			if ( typeof value === 'string' ) {
				out[ key ] = value;
			}
		} );
		return out;
	} );

	if ( ! fields._wpnonce ) {
		throw new Error(
			`The Customs Fees settings form at ${ CUSTOMS_SETTINGS_PATH } has no _wpnonce field; is WooCommerce's Tax tab enabled?`
		);
	}

	fields.cfwc_rules = JSON.stringify( rules );
	// The submit button is not part of FormData, but WooCommerce only saves
	// when `save` is posted.
	fields.save = 'Save changes';

	const response = await page.request.post( CUSTOMS_SETTINGS_PATH, {
		form: fields,
	} );
	expect(
		response.ok(),
		`Saving customs rules failed (HTTP ${ response.status() })`
	).toBeTruthy();

	const stored = await getRules( page );
	expect(
		stored.map( ruleKey ),
		'the stored customs rules must be exactly the ones just saved'
	).toEqual( rules.map( ruleKey ) );
};
