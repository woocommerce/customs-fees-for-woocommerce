import { expect, type BrowserContext } from '@playwright/test';
import { createRestClient } from './rest';
import { CUSTOMS_FEE_NAME } from './customs-rules';

interface WcOrder {
	id: number;
	total: string;
	fee_lines: Array< { name: string; total: string } >;
}

/**
 * Read the order ID from the order-received URL.
 *
 * WooCommerce emits the endpoint as a path segment under pretty permalinks
 * (QIT) and as a query arg under plain ones (wp-env).
 */
export const orderIdFromUrl = ( url: string ): number => {
	const parsed = new URL( url );
	const orderId = Number(
		/order-received\/(\d+)/.exec( parsed.pathname )?.[ 1 ] ??
			parsed.searchParams.get( 'order-received' ) ??
			parsed.searchParams.get( 'order' ) ??
			0
	);
	expect( orderId, `no order ID in ${ url }` ).toBeGreaterThan( 0 );
	return orderId;
};

// Order item names can come back HTML-encoded; compare on the decoded text.
const decodeAmp = ( text: string ) => text.replace( /&amp;/g, '&' );

/**
 * Assert, through the public /wc/v3/orders surface, that the order carries
 * exactly one combined customs fee line of the expected total.
 */
export const expectCustomsFeeOnOrder = async (
	context: BrowserContext,
	orderId: number,
	expected: { fee: string; total: string }
): Promise< void > => {
	const rest = await createRestClient( context );
	try {
		const order = await rest.fetch< WcOrder >( {
			path: `/wc/v3/orders/${ orderId }`,
		} );
		const customsFees = order.fee_lines.filter(
			( fee ) => decodeAmp( fee.name ) === CUSTOMS_FEE_NAME
		);
		expect(
			customsFees.map( ( fee ) => fee.total ),
			`order ${ orderId } must carry exactly one "${ CUSTOMS_FEE_NAME }" fee line`
		).toEqual( [ expected.fee ] );
		expect( order.total ).toBe( expected.total );
	} finally {
		await rest.dispose();
	}
};

/** Permanently delete an order the suite created. */
export const deleteOrder = async (
	context: BrowserContext,
	orderId: number
): Promise< void > => {
	const rest = await createRestClient( context );
	try {
		await rest.fetch( {
			path: `/wc/v3/orders/${ orderId }?force=true`,
			method: 'DELETE',
		} );
	} finally {
		await rest.dispose();
	}
};
