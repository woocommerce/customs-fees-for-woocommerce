import type { RestClient } from './rest';

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
