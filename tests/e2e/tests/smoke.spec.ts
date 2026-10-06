import { test, expect } from '@playwright/test';
import { expectNoPhpErrors } from '../utils/php-errors';
import { createRestClient } from '../utils/rest';
import { ensureTaxesEnabled } from '../utils/provisioning';
import { CUSTOMS_SETTINGS_PATH } from '../utils/customs-rules';

// Located by the stable data-plugin attribute, so the check survives locale
// changes. QIT unpacks the slug-wrapped zip and wp-env mounts the checkout
// directory, and both yield this folder name.
const PLUGIN_FILE =
	'customs-fees-for-woocommerce/customs-fees-for-woocommerce.php';

test.describe( 'Smoke', () => {
	test( 'wp-admin loads with no fatal PHP errors', async ( { page } ) => {
		await page.goto( 'wp-admin/' );
		await expectNoPhpErrors( page );
		await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
	} );

	test( 'the plugin is active', async ( { page } ) => {
		await page.goto( 'wp-admin/plugins.php' );
		await expectNoPhpErrors( page );

		const row = page.locator( `tr[data-plugin="${ PLUGIN_FILE }"]` );
		await expect( row ).toHaveCount( 1 );
		await expect( row ).toHaveClass( /\bactive\b/ );
	} );

	test( 'the Customs Fees settings section renders', async ( {
		context,
		page,
	} ) => {
		const rest = await createRestClient( context );
		try {
			await ensureTaxesEnabled( rest );
		} finally {
			await rest.dispose();
		}

		await page.goto( CUSTOMS_SETTINGS_PATH );
		await expectNoPhpErrors( page );
		await expect( page.locator( '.cfwc-add-rule' ) ).toBeVisible();
		await expect( page.locator( '#cfwc_rules' ) ).toHaveCount( 1 );
	} );
} );
