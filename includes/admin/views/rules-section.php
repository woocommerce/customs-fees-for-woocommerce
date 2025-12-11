<?php
/**
 * Fee rules section view.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Current rules should already be available as $rules.
// Templates should already be available as $templates.
?>

<div class="cfwc-rules-section">
	
	<?php
	// Collect all notices in one area.
	$notices = array();

	// Check if WooCommerce tax is enabled.
	$tax_enabled = wc_tax_enabled();
	if ( ! $tax_enabled ) {
		$tax_notice  = '<strong>' . esc_html__( '⚠️ Tax is disabled in WooCommerce!', 'customs-fees-for-woocommerce' ) . '</strong> ';
		$tax_notice .= sprintf(
			/* translators: %s: Link to WooCommerce tax settings */
			esc_html__( 'Customs fees are added as taxable fees. Please %s to use this plugin.', 'customs-fees-for-woocommerce' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">' . esc_html__( 'enable taxes in WooCommerce settings', 'customs-fees-for-woocommerce' ) . '</a>'
		);
		$notices[] = array(
			'type'    => 'warning',
			'content' => $tax_notice,
		);
	}

	// Check setup status.
	if ( class_exists( 'CFWC_Onboarding' ) ) {
		$onboarding = new CFWC_Onboarding();
		$stats      = $onboarding->get_product_stats();

		if ( $stats['missing'] > 0 ) {
			$products_url  = admin_url( 'edit.php?post_type=product' );
			$setup_notice  = '<strong>' . esc_html__( 'Setup Status:', 'customs-fees-for-woocommerce' ) . '</strong> ';
			$setup_notice .= sprintf(
				/* translators: %1$d: number of products missing origin, %2$d: total products */
				esc_html__( '%1$d of %2$d products need Country of Origin data.', 'customs-fees-for-woocommerce' ),
				absint( $stats['missing'] ),
				absint( $stats['total'] )
			);
			$setup_notice .= ' <a href="' . esc_url( $products_url ) . '">' . esc_html__( 'View Products', 'customs-fees-for-woocommerce' ) . '</a>';

			$notices[] = array(
				'type'    => 'warning',
				'content' => $setup_notice,
			);
		}
	}
	?>
	
	<!-- Global Settings Section (WooCommerce Style) -->
	<?php
	$base_country       = get_option( 'woocommerce_default_country' );
	$base_country_parts = explode( ':', $base_country );
	$store_country      = $base_country_parts[0];
	$store_country_name = WC()->countries->countries[ $store_country ] ?? $store_country;
	$default_origin     = sanitize_text_field( get_option( 'cfwc_default_origin', 'store' ) );
	$custom_origin      = sanitize_text_field( get_option( 'cfwc_custom_default_origin', '' ) );

	// Determine the actual selected value for the dropdown.
	$selected_origin = '';
	if ( 'store' === $default_origin ) {
		$selected_origin = 'store';
	} elseif ( 'none' === $default_origin ) {
		$selected_origin = 'none';
	} elseif ( 'custom' === $default_origin && ! empty( $custom_origin ) ) {
		$selected_origin = $custom_origin;
	}
	?>
	
	<?php
	// Get the use original price setting.
	$use_original_price = get_option( 'cfwc_use_original_price', 'no' );
	?>
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="cfwc_default_origin_select">
						<?php esc_html_e( 'Default Product Origin', 'customs-fees-for-woocommerce' ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Sets the default country of origin for new products. This determines which customs rules apply when products are shipped internationally. Existing products keep their saved origin.', 'customs-fees-for-woocommerce' ); ?>"></span>
					</label>
				</th>
				<td class="forminp">
					<select id="cfwc_default_origin_select" name="cfwc_default_origin_select" class="wc-enhanced-select" style="width: 350px; max-width: 50%;">
						<option value="store" <?php selected( $selected_origin, 'store' ); ?>>
							<?php
							printf(
								/* translators: %s: Store country name */
								esc_html__( 'Same as store location (%s)', 'customs-fees-for-woocommerce' ),
								esc_html( $store_country_name )
							);
							?>
						</option>
						<option value="none" <?php selected( $selected_origin, 'none' ); ?>>
							<?php esc_html_e( 'No default (set per product)', 'customs-fees-for-woocommerce' ); ?>
						</option>
						<optgroup label="<?php esc_attr_e( 'Different Country', 'customs-fees-for-woocommerce' ); ?>">
							<?php
							foreach ( WC()->countries->get_countries() as $code => $name ) {
								if ( $code !== $store_country ) {
									?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $selected_origin, $code ); ?>>
										<?php echo esc_html( $name ); ?>
									</option>
									<?php
								}
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="cfwc_use_original_price">
						<?php esc_html_e( 'Calculate on Original Price', 'customs-fees-for-woocommerce' ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'When enabled, customs fees will be calculated based on the product\'s regular price, ignoring any discounts or coupons applied. Useful for promotions where you want customers to still pay applicable tariffs based on product value.', 'customs-fees-for-woocommerce' ); ?>"></span>
					</label>
				</th>
				<td class="forminp">
					<fieldset>
						<label for="cfwc_use_original_price">
							<input type="checkbox" name="cfwc_use_original_price" id="cfwc_use_original_price" value="yes" <?php checked( $use_original_price, 'yes' ); ?> />
							<?php esc_html_e( 'Calculate customs fees on original price (before discounts)', 'customs-fees-for-woocommerce' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Enable this if you run promotions with discounts but still want to charge customs fees based on the full product value.', 'customs-fees-for-woocommerce' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>
	
	<!-- Hidden inputs for backward compatibility -->
	<input type="hidden" name="cfwc_default_origin" id="cfwc_default_origin" value="<?php echo esc_attr( $default_origin ); ?>" />
	<input type="hidden" name="cfwc_custom_default_origin" id="cfwc_custom_default_origin" value="<?php echo esc_attr( $custom_origin ); ?>" />
	
	<!-- WooCommerce Style Modal for Help -->
	<div id="cfwc-help-modal" class="cfwc-modal" style="display: none;">
		<div class="cfwc-modal-backdrop"></div>
		<div class="cfwc-modal-content">
			<div class="cfwc-modal-header">
				<h2><?php esc_html_e( 'Customs & import fees - Getting started guide', 'customs-fees-for-woocommerce' ); ?></h2>
				<button type="button" class="cfwc-modal-close" aria-label="<?php esc_attr_e( 'Close', 'customs-fees-for-woocommerce' ); ?>">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>
			
			<div class="cfwc-modal-body">
				<!-- How It Works -->
				<div class="cfwc-help-section">
					<h3>
						<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
						<?php esc_html_e( 'How it works', 'customs-fees-for-woocommerce' ); ?>
					</h3>
					<ol>
						<li><?php esc_html_e( 'Add Country of Origin to products (Products → Edit → Inventory tab).', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Configure rules below or use Quick Start presets.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Rules apply automatically at checkout based on shipping destination.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Customers see fee breakdown on cart and checkout pages.', 'customs-fees-for-woocommerce' ); ?></li>
					</ol>
				</div>
				
				<!-- How Rules Apply -->
				<div class="cfwc-help-section">
					<h3>
						<span class="dashicons dashicons-admin-generic" style="color: #2271b1;"></span>
						<?php esc_html_e( 'How rules apply', 'customs-fees-for-woocommerce' ); ?>
					</h3>
					<ul>
						<li><strong><?php esc_html_e( '"Any → [Country]"', 'customs-fees-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'ALL imports to that country get the fee.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><strong><?php esc_html_e( '"[Country A] → [Country B]"', 'customs-fees-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'Only products from A to B.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Multiple rules for same destination will stack unless set to Override/Exclusive.', 'customs-fees-for-woocommerce' ); ?></li>
					</ul>
				</div>
				
				<!-- Pro Tips -->
				<div class="cfwc-help-section">
					<h3>
						<span class="dashicons dashicons-lightbulb" style="color: #f0ad4e;"></span>
						<?php esc_html_e( 'Pro tips', 'customs-fees-for-woocommerce' ); ?>
					</h3>
					<ul>
						<li><?php esc_html_e( 'Use priority (0-100) to control rule order - higher numbers apply first.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Category-specific rules override general ones.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Use CSV import/export for bulk updates.', 'customs-fees-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Test rules with different shipping destinations before going live.', 'customs-fees-for-woocommerce' ); ?></li>
					</ul>
				</div>
				
				<!-- Rule Stacking Modes -->
				<div class="cfwc-help-section">
					<h3>
						<span class="dashicons dashicons-admin-settings" style="color: #2271b1;"></span>
						<?php esc_html_e( 'Rule stacking modes', 'customs-fees-for-woocommerce' ); ?>
					</h3>
					<div class="cfwc-stacking-modes">
						<div class="cfwc-stacking-mode">
							<span class="cfwc-badge cfwc-badge-stack"><?php esc_html_e( 'Stack', 'customs-fees-for-woocommerce' ); ?></span>
							<span><?php esc_html_e( 'Adds with other matching rules.', 'customs-fees-for-woocommerce' ); ?></span>
						</div>
						<div class="cfwc-stacking-mode">
							<span class="cfwc-badge cfwc-badge-override"><?php esc_html_e( 'Override', 'customs-fees-for-woocommerce' ); ?></span>
							<span><?php esc_html_e( 'Replaces lower priority rules.', 'customs-fees-for-woocommerce' ); ?></span>
						</div>
						<div class="cfwc-stacking-mode">
							<span class="cfwc-badge cfwc-badge-exclusive"><?php esc_html_e( 'Exclusive', 'customs-fees-for-woocommerce' ); ?></span>
							<span><?php esc_html_e( 'Only this rule applies, ignores all others.', 'customs-fees-for-woocommerce' ); ?></span>
						</div>
					</div>
				</div>
			</div>
			
			<div class="cfwc-modal-footer">
				<button type="button" class="button button-primary cfwc-modal-close">
					<?php esc_html_e( 'Got it', 'customs-fees-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	</div>
	
	<style>
	/* WooCommerce-style Modal */
	.cfwc-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 160000;
	}
	
	.cfwc-modal-backdrop {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.6);
		z-index: 159990;
	}
	
	.cfwc-modal-content {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		width: 90%;
		max-width: 600px;
		max-height: 90vh;
		background: #fff;
		border-radius: 4px;
		box-shadow: 0 3px 30px rgba(0, 0, 0, 0.2);
		z-index: 160000;
		display: flex;
		flex-direction: column;
	}
	
	.cfwc-modal-header {
		padding: 20px;
		border-bottom: 1px solid #e0e0e0;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.cfwc-modal-header h2 {
		margin: 0;
		font-size: 18px;
		font-weight: 600;
		color: #333;
	}
	
	.cfwc-modal-close {
		background: none;
		border: none;
		cursor: pointer;
		padding: 0;
		color: #999;
		font-size: 20px;
	}
	
	.cfwc-modal-close:hover {
		color: #333;
	}
	
	.cfwc-modal-body {
		padding: 20px;
		overflow-y: auto;
		flex: 1;
	}
	
	.cfwc-modal-footer {
		padding: 20px;
		border-top: 1px solid #e0e0e0;
		text-align: right;
	}
	
	.cfwc-help-section {
		margin-bottom: 30px;
	}
	
	.cfwc-help-section:last-child {
		margin-bottom: 0;
	}
	
	.cfwc-help-section h3 {
		margin: 0 0 15px 0;
		font-size: 14px;
		font-weight: 600;
		color: #333;
	}
	
	.cfwc-help-section h3 .dashicons {
		font-size: 18px;
		vertical-align: middle;
		margin-right: 5px;
	}
	
	.cfwc-help-section ol,
	.cfwc-help-section ul {
		margin: 0;
		padding-left: 25px;
		font-size: 14px;
		line-height: 1.8;
		color: #555;
	}
	
	.cfwc-help-section li {
		margin-bottom: 8px;
	}
	
	.cfwc-stacking-modes {
		margin-top: 10px;
	}
	
	.cfwc-stacking-mode {
		margin-bottom: 10px;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	
	.cfwc-badge {
		display: inline-block;
		padding: 4px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
		color: #fff;
		min-width: 70px;
		text-align: center;
	}
	
	.cfwc-badge-stack {
		background: #46b450;
	}
	
	.cfwc-badge-override {
		background: #f0ad4e;
	}
	
	.cfwc-badge-exclusive {
		background: #dc3232;
	}
	
	/* Mobile styles */
	@media screen and (max-width: 768px) {
		.cfwc-modal-content {
			width: 100%;
			height: 100%;
			max-width: none;
			max-height: none;
			border-radius: 0;
			transform: none;
			position: fixed;
			top: 0;
			left: 0;
		}
		
		#cfwc_default_origin_select {
			width: 100% !important;
			margin-left: 0 !important;
			margin-top: 10px;
		}
	}
	</style>
	
	<script>
	jQuery(document).ready(function($) {
		// Initialize Select2 on the default origin dropdown
		setTimeout(function() {
			if (typeof $.fn.selectWoo !== 'undefined') {
				$('#cfwc_default_origin_select').selectWoo({
					width: '350px'
				});
			} else if (typeof $.fn.select2 !== 'undefined') {
				$('#cfwc_default_origin_select').select2({
					width: '350px'
				});
			}
		}, 100);
		
		// Handle dropdown changes for default origin
		$('#cfwc_default_origin_select').on('change', function() {
			var value = $(this).val();
			
			if (value === 'store') {
				$('#cfwc_default_origin').val('store');
				$('#cfwc_custom_default_origin').val('');
			} else if (value === 'none') {
				$('#cfwc_default_origin').val('none');
				$('#cfwc_custom_default_origin').val('');
			} else {
				// It's a country code
				$('#cfwc_default_origin').val('custom');
				$('#cfwc_custom_default_origin').val(value);
			}
		});
		
		// Handle help modal
		$('#cfwc-help-link').on('click', function(e) {
			e.preventDefault();
			$('#cfwc-help-modal').fadeIn(200);
			$('body').css('overflow', 'hidden'); // Prevent background scrolling
		});
		
		// Close modal
		$('.cfwc-modal-close, .cfwc-modal-backdrop').on('click', function() {
			$('#cfwc-help-modal').fadeOut(200);
			$('body').css('overflow', ''); // Restore scrolling
		});
		
		// Close modal on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#cfwc-help-modal').is(':visible')) {
				$('#cfwc-help-modal').fadeOut(200);
				$('body').css('overflow', '');
			}
		});
	});
	</script>
	
	<?php
	// Check if there are potentially stacking rules (only count "add" mode rules).
	$has_stacking_risk  = false;
	$stacking_countries = array();
	if ( ! empty( $rules ) ) {
		$countries_add_rules = array();
		foreach ( $rules as $rule ) {
			$to_country    = $rule['to_country'] ?? $rule['country'] ?? '';
			$stacking_mode = $rule['stacking_mode'] ?? 'add';

			// Only count rules that can stack (add mode).
			if ( ! empty( $to_country ) && 'add' === $stacking_mode ) {
				if ( ! isset( $countries_add_rules[ $to_country ] ) ) {
					$countries_add_rules[ $to_country ] = 0;
				}
				++$countries_add_rules[ $to_country ];
				if ( $countries_add_rules[ $to_country ] > 1 ) {
					$has_stacking_risk                 = true;
					$stacking_countries[ $to_country ] = true;
				}
			}
		}

		// Also check if there are "add" mode rules mixed with exclusive/override for same country.
		$countries_all_rules = array();
		foreach ( $rules as $rule ) {
			$to_country = $rule['to_country'] ?? $rule['country'] ?? '';
			if ( ! empty( $to_country ) ) {
				if ( ! isset( $countries_all_rules[ $to_country ] ) ) {
					$countries_all_rules[ $to_country ] = array();
				}
				$countries_all_rules[ $to_country ][] = $rule['stacking_mode'] ?? 'add';
			}
		}

		// Check for mixed stacking modes.
		foreach ( $countries_all_rules as $country => $modes ) {
			if ( count( $modes ) > 1 && in_array( 'add', $modes, true ) ) {
				// Has multiple rules AND at least one is "add" mode.
				$has_stacking_risk              = true;
				$stacking_countries[ $country ] = true;
			}
		}
	}

	// We'll handle stacking risk together with mixed rules below for a more comprehensive notice.
	?>
	
	<!-- Quick Preset Loader -->
	<div class="cfwc-preset-loader">
		<h3><?php esc_html_e( 'Quick Start with Presets', 'customs-fees-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Load presets to quickly configure common import scenarios:', 'customs-fees-for-woocommerce' ); ?></p>
		
		<select id="cfwc-preset-select" class="wc-enhanced-select" style="width: 280px; max-width: 100%;">
			<option value=""><?php esc_html_e( '-- Select a preset --', 'customs-fees-for-woocommerce' ); ?></option>
			<?php if ( ! empty( $templates ) ) : ?>
				<?php foreach ( $templates as $template_id => $template ) : ?>
					<option value="<?php echo esc_attr( $template_id ); ?>" data-description="<?php echo esc_attr( $template['description'] ); ?>">
						<?php echo esc_html( $template['name'] ); ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		
		<button type="button" class="button button-primary cfwc-add-preset">
			<?php
			// Show different text based on whether rules exist.
			if ( empty( $rules ) ) {
				esc_html_e( 'Import Preset Rules', 'customs-fees-for-woocommerce' );
			} else {
				esc_html_e( 'Add to Existing Rules', 'customs-fees-for-woocommerce' );
			}
			?>
		</button>
		<button type="button" class="button cfwc-replace-preset">
			<?php esc_html_e( 'Replace All Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		
		<div id="cfwc-preset-description">
			<em></em>
		</div>
	</div>
	
	<div style="margin: 40px 0 30px 0; border-top: 1px solid #c3c4c7;"></div>
	
	<!-- Mixed Rules Warning -->
	<?php
	// Check for potential conflicts between general and specific rules with "add" mode.
	$has_general_add_rules  = false;
	$has_specific_add_rules = false;
	$mixed_destinations     = array();

	foreach ( $rules as $rule ) {
		$from          = $rule['from_country'] ?? $rule['origin_country'] ?? '';
		$to            = $rule['to_country'] ?? $rule['country'] ?? '';
		$stacking_mode = $rule['stacking_mode'] ?? 'add';

		// Only care about rules that can stack (add mode).
		if ( 'add' === $stacking_mode ) {
			if ( empty( $from ) && ! empty( $to ) ) {
				// General rule (any origin to specific destination).
				$has_general_add_rules     = true;
				$mixed_destinations[ $to ] = true;
			} elseif ( ! empty( $from ) && ! empty( $to ) ) {
				// Specific rule (specific origin to specific destination).
				$has_specific_add_rules    = true;
				$mixed_destinations[ $to ] = true;
			}
		}
	}

	// Check for any rule stacking scenarios (combined notice for both mixed types and multiple rules).
	if ( $has_stacking_risk || ( $has_general_add_rules && $has_specific_add_rules ) ) {
		$stacking_notice = '<strong>' . esc_html__( '⚠️ Rule Stacking Detected', 'customs-fees-for-woocommerce' ) . '</strong><br>';

		// Check if we have mixed rule types.
		if ( $has_general_add_rules && $has_specific_add_rules ) {
			$stacking_notice .= esc_html__( 'You have both general (Any → Country) and specific (Country → Country) rules with "Add" stacking mode. These will combine unless you set them to "Exclusive" or "Override" mode.', 'customs-fees-for-woocommerce' ) . ' ';
			$stacking_notice .= esc_html__( 'Example: A general "Any → US 10%" rule will add to "China → US 25%" if both are in "Add" mode.', 'customs-fees-for-woocommerce' );
		} elseif ( $has_stacking_risk ) {
			// Just multiple rules for same destination.
			$country_names = array();
			foreach ( array_keys( $stacking_countries ) as $code ) {
				$country_names[] = WC()->countries->countries[ $code ] ?? $code;
			}
			$stacking_notice .= sprintf(
				/* translators: %s: list of countries */
				esc_html__( 'You have multiple rules with "Add" stacking mode for %s. These fees will be combined. Consider using "Exclusive" or "Override" mode if you want only the highest priority rule to apply.', 'customs-fees-for-woocommerce' ),
				esc_html( implode( ', ', $country_names ) )
			);
		}

		$notices[] = array(
			'type'    => 'warning',
			'content' => $stacking_notice,
		);
	}

	// Display all collected notices in one area.
	if ( ! empty( $notices ) ) :
		?>
		<div class="cfwc-notices-container" style="margin-bottom: 20px;">
			<?php foreach ( $notices as $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>" style="margin-bottom: 10px;">
					<p><?php echo wp_kses_post( $notice['content'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	endif;
	?>
	
	<!-- Rules Table -->
	<div class="cfwc-rules-header">
		<div style="flex: 1;">
			<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
				<h3 style="margin: 0;"><?php esc_html_e( 'All Rules', 'customs-fees-for-woocommerce' ); ?></h3>
				<button type="button" class="button cfwc-add-rule">
					<?php esc_html_e( 'Add New Rule', 'customs-fees-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button cfwc-delete-all">
					<?php esc_html_e( 'Delete All Rules', 'customs-fees-for-woocommerce' ); ?>
				</button>
			</div>
			<p style="margin: 0;"><?php esc_html_e( 'Configure customs fee rules based on destination countries, product origins, categories, and HS codes.', 'customs-fees-for-woocommerce' ); ?></p>
		</div>
		<div style="align-self: flex-start;">
			<a href="#" id="cfwc-help-link" class="cfwc-help-link-under">
				<span class="cfwc-help-text"><?php esc_html_e( 'Need help getting started?', 'customs-fees-for-woocommerce' ); ?></span>
			</a>
		</div>
	</div>
	
	<div class="cfwc-rules-table-wrapper">
		<table class="widefat fixed striped cfwc-rules-table">
			<thead>
				<tr>
					<th style="width: 18%;"><?php esc_html_e( 'Label', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 18%;"><?php esc_html_e( 'Countries', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 18%;"><?php esc_html_e( 'Products', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Type', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 7%;"><?php esc_html_e( 'Rate', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Stacking', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Actions', 'customs-fees-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody id="cfwc-rules-tbody">
				<?php if ( ! empty( $rules ) ) : ?>
					<?php foreach ( $rules as $index => $rule ) : ?>
						<tr>
							<td>
								<?php
								echo esc_html( $rule['label'] ?? '' );

								// Match JavaScript rendering for priority.
								$priority = $rule['priority'] ?? 0;
								if ( $priority > 0 ) {
									echo ' <span style="color: #666; font-size: 11px;">(' . esc_html( $priority ) .
										' <span class="dashicons dashicons-info" style="font-size: 14px; vertical-align: middle; cursor: help;" ' .
										'title="' . esc_attr__( 'Higher priority rules are checked first (0-100)', 'customs-fees-for-woocommerce' ) . '"></span>)</span>';
								}
								?>
							</td>
							<td>
								<?php
								// Display country pair.
								$from = $rule['from_country'] ?? '';
								$to   = $rule['to_country'] ?? '';

								// Handle old format where 'country' is the destination.
								if ( empty( $from ) && empty( $to ) && ! empty( $rule['country'] ) ) {
									$from = '';  // Any origin.
									$to   = $rule['country'];  // Destination.
								}

								if ( empty( $from ) && empty( $to ) ) {
									echo '<em>' . esc_html__( 'All → All', 'customs-fees-for-woocommerce' ) . '</em>';
								} elseif ( empty( $from ) ) {
									$to_name = WC()->countries->countries[ $to ] ?? $to;
									/* translators: %s: destination country name */
									echo esc_html( sprintf( __( 'Any → %s', 'customs-fees-for-woocommerce' ), $to_name ) );
								} elseif ( empty( $to ) ) {
									$from_name = WC()->countries->countries[ $from ] ?? $from;
									/* translators: %s: origin country name */
									echo esc_html( sprintf( __( '%s → Any', 'customs-fees-for-woocommerce' ), $from_name ) );
								} else {
									$from_name = WC()->countries->countries[ $from ] ?? $from;
									$to_name   = WC()->countries->countries[ $to ] ?? $to;
									echo esc_html( sprintf( '%s → %s', $from_name, $to_name ) );
								}
								?>
							</td>
							<td>
								<?php
								// Display product matching criteria.
								$match_type = $rule['match_type'] ?? 'all';
								$criteria   = array();

								if ( 'all' === $match_type ) {
									echo '<em>' . esc_html__( 'All Products', 'customs-fees-for-woocommerce' ) . '</em>';
								} else {
									// Categories.
									if ( ! empty( $rule['category_ids'] ) ) {
										$cat_ids = is_array( $rule['category_ids'] ) ? $rule['category_ids'] : json_decode( $rule['category_ids'], true );
										if ( is_array( $cat_ids ) && count( $cat_ids ) > 0 ) {
											$cat_names = array();
											foreach ( array_slice( $cat_ids, 0, 2 ) as $category_id ) {
												$category_term = get_term( $category_id, 'product_cat' );
												if ( $category_term && ! is_wp_error( $category_term ) ) {
													$cat_names[] = $category_term->name;
												}
											}
											if ( count( $cat_ids ) > 2 ) {
												/* translators: %d: number of additional categories */
												$cat_names[] = sprintf( __( '+%d more', 'customs-fees-for-woocommerce' ), count( $cat_ids ) - 2 );
											}
											if ( ! empty( $cat_names ) ) {
												$criteria[] = '<span class="dashicons dashicons-category" style="font-size: 14px;"></span> ' . implode( ', ', $cat_names );
											}
										}
									}

									// HS Code.
									if ( ! empty( $rule['hs_code_pattern'] ) ) {
										$criteria[] = '<span class="dashicons dashicons-tag" style="font-size: 14px;"></span> HS: ' . esc_html( $rule['hs_code_pattern'] );
									}

									// Output criteria with allowed HTML (dashicons and breaks).
									$allowed_html = array(
										'span' => array(
											'class' => array(),
											'style' => array(),
										),
										'br'   => array(),
										'em'   => array(),
									);
									echo ! empty( $criteria ) ? wp_kses( implode( '<br>', $criteria ), $allowed_html ) : '<em>' . esc_html__( 'All Products', 'customs-fees-for-woocommerce' ) . '</em>';
								}
								?>
							</td>
							<td><?php echo esc_html( ucfirst( $rule['type'] ?? 'percentage' ) ); ?></td>
							<td>
								<?php
								if ( 'percentage' === ( $rule['type'] ?? 'percentage' ) ) {
									echo esc_html( ( $rule['rate'] ?? 0 ) . '%' );
								} else {
									echo wp_kses_post( wc_price( $rule['amount'] ?? 0 ) );
								}
								?>
							</td>
							<td>
								<?php
								$stacking_mode = $rule['stacking_mode'] ?? 'add';

								// Use WooCommerce-style status badges.
								$stacking_labels = array(
									'add'       => __( 'Stack', 'customs-fees-for-woocommerce' ),
									'override'  => __( 'Override', 'customs-fees-for-woocommerce' ),
									'exclusive' => __( 'Exclusive', 'customs-fees-for-woocommerce' ),
								);

								$stacking_colors = array(
									'add'       => '#46b450',
									'override'  => '#f0ad4e',
									'exclusive' => '#dc3232',
								);

								$stacking_descriptions = array(
									'add'       => __( 'Adds with other matching rules', 'customs-fees-for-woocommerce' ),
									'override'  => __( 'Replaces lower priority rules', 'customs-fees-for-woocommerce' ),
									'exclusive' => __( 'Only this rule applies', 'customs-fees-for-woocommerce' ),
								);

								// Output WooCommerce-style badge.
								$badge_color = $stacking_colors[ $stacking_mode ] ?? $stacking_colors['add'];
								$badge_label = $stacking_labels[ $stacking_mode ] ?? $stacking_labels['add'];
								$badge_title = $stacking_descriptions[ $stacking_mode ] ?? $stacking_descriptions['add'];

								echo '<span style="display: inline-block; padding: 3px 8px; background: ' . esc_attr( $badge_color ) . '; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600; line-height: 1;" title="' . esc_attr( $badge_title ) . '">';
								echo esc_html( $badge_label );
								echo '</span>';
								?>
							</td>
							<td>
								<button type="button" class="button cfwc-edit-rule" data-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Edit', 'customs-fees-for-woocommerce' ); ?>
								</button>
								<button type="button" class="button cfwc-delete-rule" data-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Delete', 'customs-fees-for-woocommerce' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="no-rules">
						<td colspan="7"><?php esc_html_e( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div><!-- .cfwc-rules-table-wrapper -->
	
	<!-- Hidden input for rules data -->
	<input type="hidden" name="cfwc_rules" id="cfwc_rules" value='<?php echo esc_attr( wp_json_encode( $rules ) ); ?>' />
	<!-- Hidden input to trigger WooCommerce form change detection -->
	<input type="hidden" name="cfwc_rules_changed" id="cfwc_rules_changed" value="" />
	<?php wp_nonce_field( 'cfwc_save_rules', 'cfwc_rules_nonce' ); ?>
</div>
