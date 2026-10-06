import { test, expect } from '@playwright/test';
import { createRestClient } from '../utils/rest';
import {
	ensureStoreProvisioned,
	PRODUCT_NAME,
	type ProvisionedStore,
} from '../utils/provisioning';
import { ensureCheckoutPageIs } from '../utils/checkout-page';
import {
	addProductToCart,
	emptyCart,
	GB_ADDRESS,
	setCustomerAddress,
	US_ADDRESS,
} from '../utils/store-api';
import {
	CUSTOMS_FEE_NAME,
	IMPORT_DUTY_RULE,
	setRules,
} from '../utils/customs-rules';
import {
	deleteOrder,
	expectCustomsFeeOnOrder,
	orderIdFromUrl,
} from '../utils/orders';
import { expectNoPhpErrors } from '../utils/php-errors';

let store: ProvisionedStore;
let createdOrderId: number | undefined;

test.describe( 'Classic checkout customs fee', () => {
	test.beforeEach( async ( { context, page } ) => {
		const rest = await createRestClient( context );
		try {
			store = await ensureStoreProvisioned( rest );
			// Each checkout spec claims the variant it needs on every run, so
			// neither has to restore anything.
			await ensureCheckoutPageIs( rest, 'classic' );
		} finally {
			await rest.dispose();
		}

		await setRules( page, [ IMPORT_DUTY_RULE ] );
		await emptyCart( page );
		await addProductToCart( page, store.productId, PRODUCT_NAME );
	} );

	test.afterEach( async ( { context } ) => {
		if ( createdOrderId ) {
			await deleteOrder( context, createdOrderId );
			createdOrderId = undefined;
		}
	} );

	test( 'charges the customs fee on an international order and records it on the order', async ( {
		context,
		page,
	} ) => {
		await setCustomerAddress( page, GB_ADDRESS );
		await page.goto( store.checkoutPath );
		await expectNoPhpErrors( page );

		// Anchor on the total first: it is retried until the review table
		// settles, so the fee assertions below read a rendered table.
		await expect(
			page.locator( 'tr.order-total .woocommerce-Price-amount' ).first()
		).toHaveText( '$110.00' );

		const feeRow = page
			.locator( 'tr.fee' )
			.filter( { hasText: CUSTOMS_FEE_NAME } );
		await expect( feeRow ).toHaveCount( 1 );
		// CFWC_Display::customize_fee_display() renders the per-rule breakdown
		// inside the combined fee row; CFWC_Calculator labels a percentage
		// rule "<label> (<rate>%)".
		await expect( feeRow.locator( '.cfwc-fee-label' ) ).toHaveText(
			`${ IMPORT_DUTY_RULE.label } (${ IMPORT_DUTY_RULE.rate }%)`
		);
		await expect( feeRow.locator( '.cfwc-fee-amount' ) ).toHaveText(
			'$10.00'
		);

		await page.locator( '#place_order' ).click();
		await page.waitForURL( /order-received/ );
		await expectNoPhpErrors( page );

		createdOrderId = orderIdFromUrl( page.url() );
		await expectCustomsFeeOnOrder( context, createdOrderId, {
			fee: '10.00',
			total: '110.00',
		} );
	} );

	test( 'charges no customs fee when shipping to the store country', async ( {
		page,
	} ) => {
		await setCustomerAddress( page, US_ADDRESS );
		await page.goto( store.checkoutPath );
		await expectNoPhpErrors( page );

		// Positive anchor before the zero-count check, so an unrendered table
		// cannot pass this test.
		await expect(
			page.locator( 'tr.order-total .woocommerce-Price-amount' ).first()
		).toHaveText( '$100.00' );
		await expect(
			page.locator( 'tr.fee' ).filter( { hasText: CUSTOMS_FEE_NAME } )
		).toHaveCount( 0 );
	} );
} );
