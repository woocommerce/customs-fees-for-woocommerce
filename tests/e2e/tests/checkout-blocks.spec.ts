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
	getCart,
	setCustomerAddress,
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

test.describe( 'Blocks checkout customs fee', () => {
	test.beforeEach( async ( { context, page } ) => {
		const rest = await createRestClient( context );
		try {
			store = await ensureStoreProvisioned( rest );
			await ensureCheckoutPageIs( rest, 'blocks' );
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

	test( 'charges the customs fee through the Store API and records it on the order', async ( {
		context,
		page,
	} ) => {
		await setCustomerAddress( page, GB_ADDRESS );

		// The Store API cart is what the Checkout block renders from; check the
		// fee there first so a UI failure and a calculation failure read
		// differently. Totals are in minor units.
		const cart = await getCart( page );
		expect( cart.totals.total_fees ).toBe( '1000' );
		expect( cart.totals.total_price ).toBe( '11000' );

		await page.goto( store.checkoutPath );
		await expectNoPhpErrors( page );

		// The block hydrates after load; wait on its totals, not on load state.
		const fees = page.locator( '.wc-block-components-totals-fees' );
		await expect(
			fees.locator( '.wc-block-components-totals-item__label' )
		).toHaveText( CUSTOMS_FEE_NAME, { timeout: 30_000 } );
		await expect(
			fees.locator( '.wc-block-components-totals-item__value' )
		).toHaveText( '$10.00' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item .wc-block-components-totals-item__value'
			)
		).toHaveText( '$110.00' );

		await page.getByRole( 'button', { name: /place order/i } ).click();
		await page.waitForURL( /order-received/, { timeout: 60_000 } );
		await expectNoPhpErrors( page );

		createdOrderId = orderIdFromUrl( page.url() );
		await expectCustomsFeeOnOrder( context, createdOrderId, {
			fee: '10.00',
			total: '110.00',
		} );
	} );
} );
