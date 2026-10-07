import { test, expect } from '@playwright/test';
import { expectNoPhpErrors } from '../utils/php-errors';
import { createRestClient } from '../utils/rest';
import { ensureTaxesEnabled } from '../utils/provisioning';
import { expectSettingsSaved, saveButton } from '../utils/wc-settings';
import {
	CUSTOMS_SETTINGS_PATH,
	getRules,
	setRules,
} from '../utils/customs-rules';

const RULE_LABEL = 'E2E Settings Rule';

// Set once ensureTaxesEnabled() has passed its disposable-store check.
// Playwright runs afterEach even when beforeEach throws, so without this a
// refused store would still have its rules emptied.
let storeClaimed = false;

test.describe( 'Customs rules editor', () => {
	test.beforeEach( async ( { context, page } ) => {
		const rest = await createRestClient( context );
		try {
			await ensureTaxesEnabled( rest );
		} finally {
			await rest.dispose();
		}
		storeClaimed = true;
		await setRules( page, [] );
	} );

	test.afterEach( async ( { page } ) => {
		if ( storeClaimed ) {
			await setRules( page, [] );
			storeClaimed = false;
		}
	} );

	test( 'a rule added in the editor persists after Save changes', async ( {
		page,
	} ) => {
		await page.goto( CUSTOMS_SETTINGS_PATH );
		await expectNoPhpErrors( page );

		// The editor row is built by assets/js/admin.js; if the admin script
		// did not load (e.g. a 404 on admin.min.js) this click does nothing
		// and the next assertion fails.
		await page.locator( '.cfwc-add-rule' ).click();
		const editor = page.locator( 'tr.cfwc-rule-editing' );
		await expect( editor ).toBeVisible();

		// Fields are addressed by data-field, the attribute the editor's save
		// handler itself reads. The country selects are select2-enhanced: the
		// native <select> is visually hidden, and the handler reads it with
		// jQuery .val(), so set it directly.
		await editor.locator( '[data-field="label"]' ).fill( RULE_LABEL );
		await editor
			.locator( 'select[data-field="from_country"]' )
			.selectOption( 'CN', { force: true } );
		await editor
			.locator( 'select[data-field="to_country"]' )
			.selectOption( 'DE', { force: true } );
		await editor
			.locator( 'select[data-field="type"]' )
			.selectOption( 'percentage' );
		await editor.locator( '[data-field="rate"]' ).fill( '5' );
		await editor.locator( '.cfwc-save-rule' ).click();

		// Saving the row only updates the hidden #cfwc_rules field; nothing is
		// persisted until the WooCommerce settings form is submitted.
		await expect( editor ).toHaveCount( 0 );
		await expect( saveButton( page ) ).toBeEnabled();
		await saveButton( page ).click();
		await expectSettingsSaved( page );

		const stored = await getRules( page );
		expect( stored ).toHaveLength( 1 );
		expect( stored[ 0 ] ).toMatchObject( {
			label: RULE_LABEL,
			from_country: 'CN',
			to_country: 'DE',
			type: 'percentage',
			rate: 5,
		} );
		expect( stored[ 0 ].rule_id ).toMatch( /^rule_/ );

		// getRules() reloaded the screen; the server-rendered table shows it.
		await expect( page.locator( '#cfwc-rules-tbody' ) ).toContainText(
			RULE_LABEL
		);
	} );
} );
