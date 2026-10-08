import { expect, type Page } from '@playwright/test';

// The classic instance settings screen for a shipping-method instance. Adding
// instance_id (not zone_id) loads the settings form; zone_id routes to the React
// zone editor instead. Used by shipping-method settings/rates specs.
export const instanceSettingsUrl = ( instanceId: number ) =>
	`wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id=${ instanceId }`;

// Locale-proof handles for the shared WooCommerce settings chrome. The visible
// text ("Save changes", "Your settings have been saved") is localized, so match
// on the structural markup WC core emits instead: the save button carries
// `.woocommerce-save-button` / `name="save"`. Use these in every settings spec
// rather than getByRole/getByText on the English strings.
export const saveButton = ( page: Page ) =>
	page
		.locator( 'button.woocommerce-save-button, button[name="save"]' )
		.first();

// WC_Admin_Settings::show_messages() emits `<div id="message" class="updated
// inline">` on success and `<div id="message" class="error inline">` on
// failure, so `#message.updated` is both locale-proof and specific to a
// SUCCESSFUL save. Do not widen this to `.notice-success`: wp-admin screens
// routinely carry unrelated success notices (and they render above the
// settings form), so the assertion would pass while the save actually errored.
export const expectSettingsSaved = async ( page: Page ) => {
	await expect( page.locator( '#message.updated' ).first() ).toBeVisible();
};
