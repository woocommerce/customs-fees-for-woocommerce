// The plugin's settings live as a section of WooCommerce > Settings > Tax.
// The slug comes from CFWC_Settings::add_customs_section().
export const CUSTOMS_SETTINGS_PATH =
	'wp-admin/admin.php?page=wc-settings&tab=tax&section=customs';

// CFWC_Loader::add_customs_fees() adds ONE combined fee under this name,
// whatever the matching rules are called; rule labels only appear in its
// breakdown. It is a translatable string - the suite assumes the English
// locale wp-env and QIT run by default.
export const CUSTOMS_FEE_NAME = 'Customs & Import Fees';
