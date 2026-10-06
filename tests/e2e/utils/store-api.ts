import { expect, type APIResponse, type Page } from '@playwright/test';

// Cart and customer state is set through the Store API with the page's own
// request context, which shares the browser session's cookies - so the
// checkout the page then renders sees exactly this cart and this address.
// ?rest_route= works under plain (wp-env) and pretty (QIT) permalinks alike.

export interface CustomerAddress {
	first_name: string;
	last_name: string;
	address_1: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
}

/** The dutiable destination for IMPORT_DUTY_RULE. */
export const GB_ADDRESS: CustomerAddress = {
	first_name: 'E2E',
	last_name: 'Importer',
	address_1: '1 Parliament Street',
	city: 'London',
	state: '',
	postcode: 'SW1A 2AA',
	country: 'GB',
};

/** The store's own country: no customs rule applies. */
export const US_ADDRESS: CustomerAddress = {
	first_name: 'E2E',
	last_name: 'Domestic',
	address_1: '60 29th Street',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94110',
	country: 'US',
};

export interface StoreCart {
	items: Array< { id: number; name: string; quantity: number } >;
	totals: { total_fees: string; total_price: string };
}

const storeRoute = ( route: string ) => `?rest_route=/wc/store/v1${ route }`;

const ensureOk = async ( response: APIResponse, label: string ) => {
	if ( ! response.ok() ) {
		throw new Error(
			`Store API ${ label } failed (HTTP ${ response.status() }): ${ await response.text() }`
		);
	}
};

export const getCart = async ( page: Page ): Promise< StoreCart > => {
	const response = await page.request.get( storeRoute( '/cart' ) );
	await ensureOk( response, 'GET /cart' );
	return ( await response.json() ) as StoreCart;
};

/**
 * Perform a Store API write.
 *
 * Writes need the session's Nonce header, which every cart response carries.
 * DELETE is sent as POST + X-HTTP-Method-Override: QIT's web server answers a
 * real DELETE to the site root with 405 before PHP runs (found in
 * woocommerce-shipment-tracking #299), and WP_REST_Server honours the override.
 */
const storeWrite = async (
	page: Page,
	method: 'POST' | 'DELETE',
	route: string,
	data?: unknown
): Promise< void > => {
	const cartResponse = await page.request.get( storeRoute( '/cart' ) );
	await ensureOk( cartResponse, 'GET /cart' );
	const nonce = cartResponse.headers()[ 'nonce' ];
	if ( ! nonce ) {
		throw new Error(
			'Store API GET /cart returned no Nonce header; cart writes would be rejected.'
		);
	}

	const headers: Record< string, string > = { Nonce: nonce };
	if ( 'DELETE' === method ) {
		headers[ 'X-HTTP-Method-Override' ] = 'DELETE';
	}

	const response = await page.request.post( storeRoute( route ), {
		headers,
		data,
	} );
	await ensureOk( response, `${ method } ${ route }` );
};

export const emptyCart = ( page: Page ): Promise< void > =>
	storeWrite( page, 'DELETE', '/cart/items' );

/**
 * Add one unit of a product and assert the cart is exactly that.
 *
 * A wrong cart would otherwise surface steps later as a wrong total.
 */
export const addProductToCart = async (
	page: Page,
	productId: number,
	productName: string
): Promise< void > => {
	await storeWrite( page, 'POST', '/cart/add-item', {
		id: productId,
		quantity: 1,
	} );

	const cart = await getCart( page );
	expect(
		cart.items.map( ( item ) => `${ item.name } x${ item.quantity }` ),
		'the cart must hold exactly one unit of the test product'
	).toEqual( [ `${ productName } x1` ] );
};

/**
 * Set the session customer's billing and shipping address.
 *
 * Both checkouts read the session customer, so this is what decides the
 * destination country the customs calculator sees
 * (CFWC_Calculator::get_customer_country(): shipping, then billing).
 */
export const setCustomerAddress = ( page: Page, address: CustomerAddress ) =>
	storeWrite( page, 'POST', '/cart/update-customer', {
		billing_address: {
			...address,
			email: 'cfwc-e2e@example.com',
			phone: '5555550100',
		},
		shipping_address: { ...address, phone: '5555550100' },
	} );
