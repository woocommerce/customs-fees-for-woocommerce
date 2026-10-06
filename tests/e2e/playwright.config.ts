import { defineConfig, type ReporterDescription } from '@playwright/test';
import {
	ADMIN_STORAGE_STATE_PATH,
	OUTPUT_ROOT_PATH,
	REPORT_PATH,
	TESTS_RESULTS_PATH,
} from './utils/paths';
import path from 'node:path';

const { BASE_URL, CI } = process.env;
// QIT injects the environment's URL as QIT_BASE_URL / QIT_SITE_URL, not
// BASE_URL. An explicit BASE_URL still wins, so local runs and manual
// overrides are unchanged; the QIT vars are the CI fallback ahead of the local
// wp-env default. `||` rather than `??`: the npm scripts pass
// `$(node bin/resolve-base-url.js)`, and a throwing resolver yields '' - which
// `??` would accept, turning baseURL into '/' and every goto() into an opaque
// URL error instead of the resolver's own message.
const resolvedBaseUrl =
	BASE_URL ||
	process.env.QIT_BASE_URL ||
	process.env.QIT_SITE_URL ||
	'http://localhost:8888';

// QIT sets QIT=1 and collects results.ctrf-json / results.blob-dir from
// qit-test.json, so those reporters are required there - and only there: the
// CTRF reporter is a devDependency of this package, so a local run started
// before `test:e2e:install` must not die loading it.
const isQitRun = process.env.QIT === '1';

// CI does not propagate into the QIT container; treat QIT runs as CI so they
// get CI timeouts and retries on slower hardware.
const isCi = Boolean( CI ) || isQitRun;

const reporters: ReporterDescription[] = [
	[ 'list' ],
	[
		'html',
		{
			outputFolder: REPORT_PATH,
			open: 'never',
		},
	],
];

if ( isQitRun ) {
	reporters.push(
		[
			'playwright-ctrf-json-reporter',
			{
				outputDir: OUTPUT_ROOT_PATH,
				outputFile: 'ctrf.json',
			},
		],
		[ 'blob', { outputDir: path.resolve( OUTPUT_ROOT_PATH, 'blob' ) } ]
	);
}

const setupProjects = [
	{
		name: 'global authentication',
		testDir: path.resolve( __dirname, './fixtures' ),
		testMatch: 'auth.setup.ts',
		// A cold wp-env can fail the first login once; give setup its own retry.
		retries: isCi ? 2 : 1,
	},
];

export default defineConfig( {
	testDir: './tests',
	outputDir: TESTS_RESULTS_PATH,
	// Empties the customs rules and resets the checkout page after the run.
	globalTeardown: './fixtures/global-teardown.ts',
	// Specs share the store's own checkout page and the customs rule set, so
	// they must never run concurrently.
	fullyParallel: false,
	forbidOnly: isCi,
	retries: isCi ? 1 : 0,
	workers: 1,
	timeout: 120 * 1000,
	expect: {
		timeout: isCi ? 30 * 1000 : 10 * 1000,
	},
	reporter: reporters,
	use: {
		baseURL: `${ resolvedBaseUrl }/`.replace( /\/+$/, '/' ),
		trace: isCi ? 'on-first-retry' : 'retain-on-failure',
		video: 'retain-on-failure',
		screenshot: 'only-on-failure',
		actionTimeout: isCi ? 30 * 1000 : 10 * 1000,
		navigationTimeout: isCi ? 30 * 1000 : 10 * 1000,
	},
	projects: [
		...setupProjects,
		{
			name: 'e2e',
			dependencies: [ 'global authentication' ],
			use: {
				storageState: ADMIN_STORAGE_STATE_PATH,
			},
		},
	],
} );
