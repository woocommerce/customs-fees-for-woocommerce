import type { RestClient } from './rest';

const CLASSIC_CHECKOUT_CONTENT = '[woocommerce_checkout]';

// The stock WooCommerce Blocks checkout, captured from an untouched /checkout
// page (via woocommerce-shipping-multiple-addresses #494) rather than written
// from memory: the Checkout block silently renders nothing when its inner
// blocks do not match what the installer produces.
const BLOCKS_CHECKOUT_CONTENT = `<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block -->
<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->
<div class="wp-block-woocommerce-checkout-express-payment-block"></div>
<!-- /wp:woocommerce/checkout-express-payment-block -->

<!-- wp:woocommerce/checkout-contact-information-block -->
<div class="wp-block-woocommerce-checkout-contact-information-block"></div>
<!-- /wp:woocommerce/checkout-contact-information-block -->

<!-- wp:woocommerce/checkout-shipping-method-block -->
<div class="wp-block-woocommerce-checkout-shipping-method-block"></div>
<!-- /wp:woocommerce/checkout-shipping-method-block -->

<!-- wp:woocommerce/checkout-pickup-options-block -->
<div class="wp-block-woocommerce-checkout-pickup-options-block"></div>
<!-- /wp:woocommerce/checkout-pickup-options-block -->

<!-- wp:woocommerce/checkout-shipping-address-block -->
<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>
<!-- /wp:woocommerce/checkout-shipping-address-block -->

<!-- wp:woocommerce/checkout-billing-address-block -->
<div class="wp-block-woocommerce-checkout-billing-address-block"></div>
<!-- /wp:woocommerce/checkout-billing-address-block -->

<!-- wp:woocommerce/checkout-shipping-methods-block -->
<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
<!-- /wp:woocommerce/checkout-shipping-methods-block -->

<!-- wp:woocommerce/checkout-payment-block -->
<div class="wp-block-woocommerce-checkout-payment-block"></div>
<!-- /wp:woocommerce/checkout-payment-block -->

<!-- wp:woocommerce/checkout-additional-information-block -->
<div class="wp-block-woocommerce-checkout-additional-information-block"></div>
<!-- /wp:woocommerce/checkout-additional-information-block -->

<!-- wp:woocommerce/checkout-order-note-block -->
<div class="wp-block-woocommerce-checkout-order-note-block"></div>
<!-- /wp:woocommerce/checkout-order-note-block -->

<!-- wp:woocommerce/checkout-terms-block -->
<div class="wp-block-woocommerce-checkout-terms-block"></div>
<!-- /wp:woocommerce/checkout-terms-block -->

<!-- wp:woocommerce/checkout-actions-block -->
<div class="wp-block-woocommerce-checkout-actions-block"></div>
<!-- /wp:woocommerce/checkout-actions-block --></div>
<!-- /wp:woocommerce/checkout-fields-block -->

<!-- wp:woocommerce/checkout-totals-block -->
<div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block -->
<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->

<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->

<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->

<!-- wp:woocommerce/checkout-order-summary-fee-block -->
<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-fee-block -->

<!-- wp:woocommerce/checkout-order-summary-discount-block -->
<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-discount-block -->

<!-- wp:woocommerce/checkout-order-summary-shipping-block -->
<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->

<!-- wp:woocommerce/checkout-order-summary-taxes-block -->
<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/checkout-order-summary-block --></div>
<!-- /wp:woocommerce/checkout-totals-block --></div>
<!-- /wp:woocommerce/checkout -->`;

export const getCheckoutPageId = async ( rest: RestClient ): Promise< number > => {
	const setting = await rest.fetch< { value: string | number } >( {
		path: '/wc/v3/settings/advanced/woocommerce_checkout_page_id',
	} );
	const pageId = Number( setting.value );
	if ( ! Number.isInteger( pageId ) || pageId <= 0 ) {
		throw new Error(
			`WooCommerce reports an invalid checkout page ID (${ String( setting.value ) }).`
		);
	}
	return pageId;
};

// WordPress normalises line endings on save; compare on content, not bytes.
const normalizeContent = ( content: string ) =>
	content.replace( /\r\n/g, '\n' ).trim();

/**
 * Make the store's own checkout page render the requested checkout.
 *
 * The store's real checkout page, not one of our own: is_checkout() and the
 * order-received flow key off woocommerce_checkout_page_id. Idempotent and
 * self-healing - it rewrites the page only when it is not already the
 * requested variant - so no spec has to restore anything.
 */
export const ensureCheckoutPageIs = async (
	rest: RestClient,
	variant: 'classic' | 'blocks'
): Promise< void > => {
	const pageId = await getCheckoutPageId( rest );
	const page = await rest.fetch< { content: { raw: string } } >( {
		path: `/wp/v2/pages/${ pageId }?context=edit`,
	} );

	const wanted =
		'classic' === variant
			? CLASSIC_CHECKOUT_CONTENT
			: BLOCKS_CHECKOUT_CONTENT;

	if ( normalizeContent( page.content.raw ) === normalizeContent( wanted ) ) {
		return;
	}

	await rest.fetch( {
		path: `/wp/v2/pages/${ pageId }`,
		method: 'POST',
		data: { content: wanted },
	} );
};
