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
	// Check if WooCommerce tax is enabled
	$tax_enabled = wc_tax_enabled();
	if ( ! $tax_enabled ) :
		?>
		<div class="notice notice-warning" style="margin-bottom: 20px;">
			<p>
				<strong><?php esc_html_e( '⚠️ Tax is disabled in WooCommerce!', 'customs-fees-for-woocommerce' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to WooCommerce tax settings */
					esc_html__( 'Customs fees are added as taxable fees. Please %s to use this plugin.', 'customs-fees-for-woocommerce' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">' . esc_html__( 'enable taxes in WooCommerce settings', 'customs-fees-for-woocommerce' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	
	<?php
	// Show setup status if needed.
	if ( class_exists( 'CFWC_Onboarding' ) ) {
		$onboarding = new CFWC_Onboarding();
		$onboarding->render_setup_status();
	}
	?>
	
	<!-- Settings Row -->
	<div style="display: flex; gap: 20px; margin-bottom: 20px;">
		<!-- Global Settings -->
		<div class="cfwc-global-settings" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; flex: 1;">
			<h3 style="margin-top: 0; margin-bottom: 15px; font-size: 14px; font-weight: 600;">
				<?php esc_html_e( 'Default Product Origin', 'customs-fees-for-woocommerce' ); ?>
			</h3>
			
			<p style="margin-bottom: 15px; color: #666; font-size: 13px;">
				<?php esc_html_e( 'This sets the default for new products only. Existing products keep their saved origin.', 'customs-fees-for-woocommerce' ); ?>
			</p>
			<div style="margin-bottom: 15px;">
				<?php
				$base_country       = get_option( 'woocommerce_default_country' );
				$base_country_parts = explode( ':', $base_country );
				$store_country      = $base_country_parts[0];
				$store_country_name = WC()->countries->countries[ $store_country ] ?? $store_country;
				$default_origin     = sanitize_text_field( get_option( 'cfwc_default_origin', 'store' ) );
				$custom_origin      = sanitize_text_field( get_option( 'cfwc_custom_default_origin', '' ) );
				?>
				
				<div style="margin-bottom: 10px;">
					<label style="display: block; margin-bottom: 5px;">
						<input type="radio" name="cfwc_default_origin_type" value="store" <?php checked( $default_origin, 'store' ); ?> />
						<strong><?php esc_html_e( 'Same as store location', 'customs-fees-for-woocommerce' ); ?></strong>
						<?php
						printf(
							/* translators: %s: Store country name */
							esc_html__( '(%s)', 'customs-fees-for-woocommerce' ),
							esc_html( $store_country_name )
						);
						?>
					</label>
					<p style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 12px;">
						<?php esc_html_e( 'Products manufactured in your store country', 'customs-fees-for-woocommerce' ); ?>
					</p>
				</div>
				
				<div style="margin-bottom: 10px;">
					<label style="display: block; margin-bottom: 5px;">
						<input type="radio" name="cfwc_default_origin_type" value="custom" <?php checked( $default_origin, 'custom' ); ?> />
						<strong><?php esc_html_e( 'Different country', 'customs-fees-for-woocommerce' ); ?></strong>
					</label>
									<div id="cfwc_custom_origin_wrapper" style="<?php echo esc_attr( ( 'custom' !== $default_origin ) ? 'display: none;' : '' ); ?> margin-left: 25px;">
					<select name="cfwc_custom_default_origin" id="cfwc_custom_default_origin" class="wc-enhanced-select" style="width: 350px;">
					<option value=""><?php esc_html_e( '— Select Country —', 'customs-fees-for-woocommerce' ); ?></option>
					<?php
					foreach ( WC()->countries->get_countries() as $code => $name ) {
						?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $custom_origin, $code ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
						<?php
					}
					?>
					</select>
				</div>
				<p style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 12px;">
					<?php esc_html_e( 'Products manufactured in a different country', 'customs-fees-for-woocommerce' ); ?>
				</p>
				</div>
				
				<div style="margin-bottom: 10px;">
					<label style="display: block; margin-bottom: 5px;">
						<input type="radio" name="cfwc_default_origin_type" value="none" <?php checked( $default_origin, 'none' ); ?> />
						<strong><?php esc_html_e( 'No default', 'customs-fees-for-woocommerce' ); ?></strong>
					</label>
					<p style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 12px;">
						<?php esc_html_e( 'Require setting origin for each product individually', 'customs-fees-for-woocommerce' ); ?>
					</p>
				</div>
				
				<input type="hidden" name="cfwc_default_origin" id="cfwc_default_origin" value="<?php echo esc_attr( $default_origin ); ?>" />
			</div>
			
			<!-- Stacking Mode Guide -->
			<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
				<h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 600;">
					<?php esc_html_e( 'Rule Stacking Modes', 'customs-fees-for-woocommerce' ); ?>
				</h4>
				<div style="font-size: 13px; line-height: 1.6;">
					<div style="margin-bottom: 8px;">
						<span class="dashicons dashicons-plus-alt" style="color: #46b450; font-size: 16px; vertical-align: middle;"></span>
						<strong><?php esc_html_e( 'Stack:', 'customs-fees-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Adds with other matching rules', 'customs-fees-for-woocommerce' ); ?>
					</div>
					<div style="margin-bottom: 8px;">
						<span class="dashicons dashicons-update" style="color: #f0ad4e; font-size: 16px; vertical-align: middle;"></span>
						<strong><?php esc_html_e( 'Override:', 'customs-fees-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Replaces lower priority rules', 'customs-fees-for-woocommerce' ); ?>
					</div>
					<div>
						<span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 16px; vertical-align: middle;"></span>
						<strong><?php esc_html_e( 'Exclusive:', 'customs-fees-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Only this rule applies, ignores all others', 'customs-fees-for-woocommerce' ); ?>
					</div>
				</div>
			</div>
		</div>
		
		<!-- How to Use Guide -->
		<div class="cfwc-help-guide" style="background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; flex: 1;">
			<h3 style="margin-top: 0; margin-bottom: 15px; font-size: 14px; font-weight: 600;">
				<span class="dashicons dashicons-info" style="color: #2271b1; font-size: 20px; vertical-align: middle;"></span>
				<?php esc_html_e( 'How It Works', 'customs-fees-for-woocommerce' ); ?>
			</h3>
			<ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
				<li><?php esc_html_e( 'Add Country of Origin to products (Products → Edit → Inventory tab)', 'customs-fees-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Configure rules below or use Quick Start presets', 'customs-fees-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Rules apply automatically at checkout based on shipping destination', 'customs-fees-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Customers see fee breakdown on cart and checkout pages', 'customs-fees-for-woocommerce' ); ?></li>
			</ol>
			
			<div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 3px; font-size: 13px;">
				<strong><?php esc_html_e( 'How Rules Apply:', 'customs-fees-for-woocommerce' ); ?></strong>
				<ul style="margin: 8px 0 0 0; padding-left: 20px;">
					<li><?php esc_html_e( '"Any → [Country]" = ALL imports to that country get the fee', 'customs-fees-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( '"[Country A] → [Country B]" = Only products from A to B', 'customs-fees-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Multiple rules for same destination will stack unless set to Override/Exclusive', 'customs-fees-for-woocommerce' ); ?></li>
				</ul>
			</div>
			
			<div style="margin-top: 20px; padding: 10px; background: #fff; border-left: 3px solid #2271b1; font-size: 13px;">
				<strong><?php esc_html_e( 'Pro Tips:', 'customs-fees-for-woocommerce' ); ?></strong>
				<ul style="margin: 5px 0 0 0; padding-left: 20px;">
					<li><?php esc_html_e( 'Use priority (0-100) to control rule order', 'customs-fees-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Category-specific rules override general ones', 'customs-fees-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Use CSV import/export for bulk updates', 'customs-fees-for-woocommerce' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Initialize Select2 on the custom origin dropdown with fixed width
		setTimeout(function() {
			if (typeof $.fn.selectWoo !== 'undefined') {
				$('#cfwc_custom_default_origin').selectWoo({
					width: '350px'
				});
			} else if (typeof $.fn.select2 !== 'undefined') {
				$('#cfwc_custom_default_origin').select2({
					width: '350px'
				});
			}
		}, 100);
		
		// Handle radio button changes for default origin
		$('input[name="cfwc_default_origin_type"]').on('change', function() {
			var value = $(this).val();
			$('#cfwc_default_origin').val(value);
			
			if (value === 'custom') {
				$('#cfwc_custom_origin_wrapper').show();
			} else {
				$('#cfwc_custom_origin_wrapper').hide();
			}
		});
	});
	</script>
	
	<?php
	// Check if there are potentially stacking rules (only count "add" mode rules)
	$has_stacking_risk  = false;
	$stacking_countries = array();
	if ( ! empty( $rules ) ) {
		$countries_add_rules = array();
		foreach ( $rules as $rule ) {
			$to_country    = $rule['to_country'] ?? $rule['country'] ?? '';
			$stacking_mode = $rule['stacking_mode'] ?? 'add';

			// Only count rules that can stack (add mode)
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

		// Also check if there are "add" mode rules mixed with exclusive/override for same country
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

		// Check for mixed stacking modes
		foreach ( $countries_all_rules as $country => $modes ) {
			if ( count( $modes ) > 1 && in_array( 'add', $modes, true ) ) {
				// Has multiple rules AND at least one is "add" mode
				$has_stacking_risk              = true;
				$stacking_countries[ $country ] = true;
			}
		}
	}
	?>
	
	<?php if ( $has_stacking_risk ) : ?>
	<div class="notice notice-warning" style="margin: 20px 0;">
		<p>
			<strong><?php esc_html_e( '⚠️ Potential Rule Stacking', 'customs-fees-for-woocommerce' ); ?></strong><br>
			<?php
			$country_names = array();
			foreach ( array_keys( $stacking_countries ) as $code ) {
				$country_names[] = WC()->countries->countries[ $code ] ?? $code;
			}
			printf(
				/* translators: %s: list of countries */
				esc_html__( 'You have multiple rules with "Add" stacking mode for %s. These fees will be combined. Consider using "Exclusive" mode if you want only the highest priority rule to apply.', 'customs-fees-for-woocommerce' ),
				esc_html( implode( ', ', $country_names ) )
			);
			?>
		</p>
	</div>
	<?php endif; ?>
	
	<!-- Quick Preset Loader -->
	<div class="cfwc-preset-loader">
		<h3><?php esc_html_e( 'Quick Start with Presets', 'customs-fees-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Load presets to quickly configure common import scenarios:', 'customs-fees-for-woocommerce' ); ?></p>
		
		<select id="cfwc-preset-select">
			<option value=""><?php esc_html_e( '-- Select a preset --', 'customs-fees-for-woocommerce' ); ?></option>
			<?php if ( ! empty( $templates ) ) : ?>
				<?php foreach ( $templates as $template_id => $template ) : ?>
					<option value="<?php echo esc_attr( $template_id ); ?>">
						<?php echo esc_html( $template['name'] ); ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		
		<button type="button" class="button button-primary cfwc-add-preset">
			<?php
			// Show different text based on whether rules exist
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
		<button type="button" class="button cfwc-delete-all">
			<?php esc_html_e( 'Delete All Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		
		<div id="cfwc-preset-description">
			<em></em>
		</div>
	</div>
	
	<hr>
	
	<!-- Mixed Rules Warning -->
	<?php
	// Check for potential conflicts between general and specific rules with "add" mode
	$has_general_add_rules  = false;
	$has_specific_add_rules = false;
	$mixed_destinations     = array();

	foreach ( $rules as $rule ) {
		$from          = $rule['from_country'] ?? $rule['origin_country'] ?? '';
		$to            = $rule['to_country'] ?? $rule['country'] ?? '';
		$stacking_mode = $rule['stacking_mode'] ?? 'add';

		// Only care about rules that can stack (add mode)
		if ( 'add' === $stacking_mode ) {
			if ( empty( $from ) && ! empty( $to ) ) {
				// General rule (any origin to specific destination)
				$has_general_add_rules     = true;
				$mixed_destinations[ $to ] = true;
			} elseif ( ! empty( $from ) && ! empty( $to ) ) {
				// Specific rule (specific origin to specific destination)
				$has_specific_add_rules    = true;
				$mixed_destinations[ $to ] = true;
			}
		}
	}

	if ( $has_general_add_rules && $has_specific_add_rules ) :
		?>
		<div class="notice notice-info" style="margin: 20px 0;">
			<p>
				<strong><?php esc_html_e( 'ℹ️ Mixed Rule Types Detected', 'customs-fees-for-woocommerce' ); ?></strong><br>
				<?php esc_html_e( 'You have both general (Any → Country) and specific (Country → Country) rules with "Add" stacking mode. These will combine unless you set them to "Exclusive" or "Override" mode.', 'customs-fees-for-woocommerce' ); ?>
				<?php esc_html_e( 'Example: A general "Any → US 10%" rule will add to "China → US 25%" if both are in "Add" mode.', 'customs-fees-for-woocommerce' ); ?>
			</p>
		</div>
	<?php endif; ?>
	
	<!-- Rules Table -->
	<div class="cfwc-rules-header">
		<div>
			<h3><?php esc_html_e( 'All Rules', 'customs-fees-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Configure customs fee rules based on destination countries, product origins, categories, and HS codes.', 'customs-fees-for-woocommerce' ); ?></p>
		</div>
		<button type="button" class="button cfwc-add-rule">
			<?php esc_html_e( 'Add New Rule', 'customs-fees-for-woocommerce' ); ?>
		</button>
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

								// Match JavaScript rendering for priority
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

								// Handle old format where 'country' is the destination
								if ( empty( $from ) && empty( $to ) && ! empty( $rule['country'] ) ) {
									$from = '';  // Any origin
									$to   = $rule['country'];  // Destination
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

								// Match JavaScript rendering exactly
								$stacking_icons = array(
									'add'       => '<span class="dashicons dashicons-plus-alt" style="color: #46b450;"></span>',
									'override'  => '<span class="dashicons dashicons-update" style="color: #f0ad4e;"></span>',
									'exclusive' => '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>',
								);

								$stacking_labels = array(
									'add'       => __( 'Stack', 'customs-fees-for-woocommerce' ),
									'override'  => __( 'Override', 'customs-fees-for-woocommerce' ),
									'exclusive' => __( 'Exclusive', 'customs-fees-for-woocommerce' ),
								);

								$stacking_descriptions = array(
									'add'       => __( 'Adds with other matching rules', 'customs-fees-for-woocommerce' ),
									'override'  => __( 'Replaces lower priority rules', 'customs-fees-for-woocommerce' ),
									'exclusive' => __( 'Only this rule applies', 'customs-fees-for-woocommerce' ),
								);

								// Output the icon and label (match JavaScript format)
								echo wp_kses_post( $stacking_icons[ $stacking_mode ] ?? $stacking_icons['add'] );
								echo ' <span title="' . esc_attr( $stacking_descriptions[ $stacking_mode ] ?? $stacking_descriptions['add'] ) . '">';
								echo esc_html( $stacking_labels[ $stacking_mode ] ?? $stacking_labels['add'] );
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