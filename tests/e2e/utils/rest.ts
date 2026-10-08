import type { BrowserContext, Page } from '@playwright/test';

export interface ApiFetchOptions {
	path: string;
	method?: string;
	data?: unknown;
}

export interface RestClient {
	/** Perform a REST request as the authenticated admin. */
	fetch< T >( options: ApiFetchOptions ): Promise< T >;
	/** Close the backing page. Always call this in afterAll. */
	dispose(): Promise< void >;
}

interface ApiFetchWindow extends Window {
	wp?: {
		apiFetch?: ( options: ApiFetchOptions ) => Promise< unknown >;
	};
}

// Any wc-admin screen boots wp.apiFetch with the REST nonce already attached,
// which is why provisioning runs through the browser rather than through a bare
// Playwright APIRequestContext: no nonce to mint, no cookie juggling, and the
// request is authenticated as exactly the admin the suite logged in as.
const REST_CONTEXT_URL = 'wp-admin/admin.php?page=wc-settings';

export const createRestClient = async (
	context: BrowserContext
): Promise< RestClient > => {
	// A DEDICATED page, not the spec's page. Provisioning frequently runs in the
	// middle of a checkout flow; navigating the page under test to wp-admin to
	// borrow its apiFetch would destroy the state the spec is mid-way through
	// building.
	const page: Page = await context.newPage();

	await page.goto( REST_CONTEXT_URL );
	await page.waitForFunction( () =>
		Boolean( ( window as ApiFetchWindow ).wp?.apiFetch )
	);

	return {
		async fetch< T >( options: ApiFetchOptions ): Promise< T > {
			// apiFetch rejects with a plain object ({ code, message, data }),
			// not an Error, and Playwright surfaces that as "[object Object]".
			// Re-throw a real Error carrying the REST code and path so a failed
			// provisioning step names itself.
			const result = await page.evaluate( async ( opts ) => {
				const apiFetch = ( window as ApiFetchWindow ).wp?.apiFetch;
				if ( ! apiFetch ) {
					throw new Error(
						'wp.apiFetch is unavailable; the REST context page navigated away.'
					);
				}

				try {
					return { ok: true as const, value: await apiFetch( opts ) };
				} catch ( error ) {
					const err = error as { code?: string; message?: string };
					return {
						ok: false as const,
						code: err?.code ?? 'unknown_error',
						message: err?.message ?? String( error ),
					};
				}
			}, options );

			if ( ! result.ok ) {
				throw new Error(
					`REST ${ options.method ?? 'GET' } ${ options.path } failed: ${ result.code } — ${ result.message }`
				);
			}

			return result.value as T;
		},

		async dispose() {
			await page.close();
		},
	};
};
