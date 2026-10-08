import { chromium, type Browser, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import { ADMIN_STORAGE_STATE_PATH } from '../utils/paths';
import { createRestClient, type RestClient } from '../utils/rest';
import { ensureCheckoutPageIs } from '../utils/checkout-page';
import { setRules } from '../utils/customs-rules';
import { assertDisposableStore } from '../utils/provisioning';

// Fail-soft by design: leaving the store dirty is the status quo this
// improves on, so an error here must neither redden a green run nor mask the
// real failure of a red one. Every step logs and carries on.
const attempt = async (
	label: string,
	step: () => Promise< unknown >
): Promise< void > => {
	try {
		await step();
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn( `[global-teardown] ${ label } failed:`, error );
	}
};

export default async function globalTeardown(
	config: FullConfig
): Promise< void > {
	const baseURL = config.projects[ 0 ]?.use.baseURL;
	if ( ! baseURL || ! fs.existsSync( ADMIN_STORAGE_STATE_PATH ) ) {
		return;
	}

	let browser: Browser;
	try {
		browser = await chromium.launch();
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn( '[global-teardown] could not launch a browser:', error );
		return;
	}

	try {
		const context = await browser.newContext( {
			baseURL,
			storageState: ADMIN_STORAGE_STATE_PATH,
		} );
		const page = await context.newPage();

		let rest: RestClient;
		try {
			rest = await createRestClient( context );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( '[global-teardown] could not open a REST client:', error );
			return;
		}

		try {
			// A store the specs refused must not be reset either.
			try {
				await assertDisposableStore( rest );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.warn( '[global-teardown] skipped:', error );
				return;
			}

			await attempt( 'emptying the customs rules', () =>
				setRules( page, [] )
			);
			// The stock Checkout block is WooCommerce's fresh-install default.
			await attempt( 'resetting the checkout page to the Checkout block', () =>
				ensureCheckoutPageIs( rest, 'blocks' )
			);
		} finally {
			await rest.dispose();
		}
	} finally {
		await browser.close();
	}
}
