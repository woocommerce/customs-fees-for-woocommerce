import type { RestClient } from './rest';
import { getCheckoutPageId } from './checkout-page';

// Everything is looked up before it is created, and rewritten when it has
// drifted, so a second run - or a run after an aborted one - converges on the
// same store. Names and SKUs are prefixed so a real store's own zones and
// products are never touched.
export const PRODUCT_SKU = 'cfwc-e2e-product';
export const PRODUCT_NAME = 'CFWC E2E Imported Product';
const PRODUCT_PRICE = '100.00';
const PRODUCT_ORIGIN = 'CN';
const ZONE_NAME = 'CFWC E2E';

export interface ProvisionedStore {
	productId: number;
	/** The store's own checkout page; see ensureCheckoutPageIs(). */
	checkoutPath: string;
}

interface WcProduct {
	id: number;
	meta_data: Array< { key: string; value: unknown } >;
}
interface WcZone {
	id: number;
	name: string;
}
interface WcZoneMethod {
	id: number;
	method_id: string;
}

// The settings REST route is keyed by group; a setting addressed under the
// wrong group is rejected rather than looked up elsewhere.
export const setSetting = async (
	rest: RestClient,
	group: string,
	id: string,
	value: string
): Promise< void > => {
	await rest.fetch( {
		path: `/wc/v3/settings/${ group }/${ id }`,
		method: 'PUT',
		data: { value },
	} );
};

/**
 * Turn WooCommerce taxes on.
 *
 * WC_Settings_Tax::add_settings_page() drops the whole Tax tab while taxes are
 * off, and the Customs Fees settings are a section of that tab - so with taxes
 * off the screen this suite drives does not exist.
 */
export const ensureTaxesEnabled = ( rest: RestClient ): Promise< void > =>
	setSetting( rest, 'general', 'woocommerce_calc_taxes', 'yes' );

/**
 * Taxes must be on (see ensureTaxesEnabled), so exact totals depend on the
 * store having no tax rates. Fail loudly rather than delete a store's rates.
 */
const ensureNoTaxRates = async ( rest: RestClient ): Promise< void > => {
	const rates = await rest.fetch< unknown[] >( {
		path: '/wc/v3/taxes?per_page=1',
	} );
	if ( rates.length > 0 ) {
		throw new Error(
			'This store has tax rates configured. The customs-fee assertions expect tax-free totals, ' +
				"and the suite will not delete a store's rates. Run it against a disposable store (wp-env or QIT)."
		);
	}
};

const PRODUCT_DATA = {
	name: PRODUCT_NAME,
	sku: PRODUCT_SKU,
	type: 'simple',
	status: 'publish',
	regular_price: PRODUCT_PRICE,
	virtual: false,
	manage_stock: false,
	weight: '1',
	// One unit per cart, whatever an earlier run left behind.
	sold_individually: true,
	// The origin is what the CN -> GB rule matches on (CFWC_Calculator reads
	// this post meta).
	meta_data: [ { key: '_cfwc_country_of_origin', value: PRODUCT_ORIGIN } ],
};

const ensureProduct = async ( rest: RestClient ): Promise< WcProduct > => {
	const existing = await rest.fetch< WcProduct[] >( {
		path: `/wc/v3/products?sku=${ encodeURIComponent( PRODUCT_SKU ) }`,
	} );

	const product =
		existing.length > 0
			? await rest.fetch< WcProduct >( {
					path: `/wc/v3/products/${ existing[ 0 ].id }`,
					method: 'PUT',
					data: PRODUCT_DATA,
			  } )
			: await rest.fetch< WcProduct >( {
					path: '/wc/v3/products',
					method: 'POST',
					data: PRODUCT_DATA,
			  } );

	// If the origin meta is ever not written, fail here by name instead of
	// later as a missing fee.
	const origin = product.meta_data.find(
		( meta ) => meta.key === '_cfwc_country_of_origin'
	)?.value;
	if ( origin !== PRODUCT_ORIGIN ) {
		throw new Error(
			`Product ${ product.id } has _cfwc_country_of_origin=${ String(
				origin
			) }, expected ${ PRODUCT_ORIGIN }.`
		);
	}

	return product;
};

const ensureFreeShippingZone = async ( rest: RestClient ): Promise< void > => {
	const zones = await rest.fetch< WcZone[] >( {
		path: '/wc/v3/shipping/zones',
	} );
	const zone =
		zones.find( ( z ) => z.name === ZONE_NAME ) ??
		( await rest.fetch< WcZone >( {
			path: '/wc/v3/shipping/zones',
			method: 'POST',
			data: { name: ZONE_NAME },
		} ) );

	// GB is the dutiable destination, US the domestic one.
	await rest.fetch( {
		path: `/wc/v3/shipping/zones/${ zone.id }/locations`,
		method: 'PUT',
		data: [
			{ code: 'GB', type: 'country' },
			{ code: 'US', type: 'country' },
		],
	} );

	const methods = await rest.fetch< WcZoneMethod[] >( {
		path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
	} );
	const freeShipping =
		methods.find( ( m ) => m.method_id === 'free_shipping' ) ??
		( await rest.fetch< WcZoneMethod >( {
			path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
			method: 'POST',
			data: { method_id: 'free_shipping' },
		} ) );
	await rest.fetch( {
		path: `/wc/v3/shipping/zones/${ zone.id }/methods/${ freeShipping.id }`,
		method: 'PUT',
		data: { enabled: true },
	} );
};

export const ensureStoreProvisioned = async (
	rest: RestClient
): Promise< ProvisionedStore > => {
	// 1. A US store, so US is the domestic (no customs fee) destination; USD
	//    with the default "$100.00" formatting the assertions use.
	await setSetting( rest, 'general', 'woocommerce_default_country', 'US:CA' );
	await setSetting( rest, 'general', 'woocommerce_currency', 'USD' );
	await setSetting( rest, 'general', 'woocommerce_currency_pos', 'left' );
	await setSetting( rest, 'general', 'woocommerce_price_num_decimals', '2' );

	// 2. Taxes on (the settings screen needs it) but with no rates.
	await ensureTaxesEnabled( rest );
	await ensureNoTaxRates( rest );

	// 3. Free shipping to both destinations, so shipping adds nothing.
	await ensureFreeShippingZone( rest );

	// 4. Out of "coming soon" mode: a fresh WooCommerce install starts there,
	//    and its admin banner sits over #place_order. Site visibility has no
	//    /wc/v3/settings route; the admin screen writes it through this one.
	await rest.fetch( {
		path: '/wc-admin/options',
		method: 'POST',
		data: { woocommerce_coming_soon: 'no' },
	} );

	// 5. Cash on delivery: the only core gateway with no credentials and no
	//    redirect. Every gateway ships disabled.
	await rest.fetch( {
		path: '/wc/v3/payment_gateways/cod',
		method: 'PUT',
		data: { enabled: true },
	} );

	const product = await ensureProduct( rest );
	const checkoutPageId = await getCheckoutPageId( rest );

	return {
		productId: product.id,
		checkoutPath: `?page_id=${ checkoutPageId }`,
	};
};
