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
	<h2><?php esc_html_e( 'Fee Rules', 'customs-fees-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Configure customs fee rules based on destination countries.', 'customs-fees-for-woocommerce' ); ?></p>
	
	<!-- Quick Preset Loader -->
	<div class="cfwc-preset-loader">
		<h3><?php esc_html_e( 'Quick Start with Presets', 'customs-fees-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Load presets to quickly configure common import scenarios:', 'customs-fees-for-woocommerce' ); ?></p>
		
		<select id="cfwc-preset-select" style="min-width: 200px;">
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
			<?php esc_html_e( 'Add Preset Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		<button type="button" class="button cfwc-replace-preset">
			<?php esc_html_e( 'Replace All Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		
		<div id="cfwc-preset-description" style="margin-top: 10px; display: none;">
			<em></em>
		</div>
	</div>
	
	<hr>
	
	<!-- Rules Table -->
	<div class="cfwc-rules-table">
		<h3><?php esc_html_e( 'Current Rules', 'customs-fees-for-woocommerce' ); ?></h3>
		
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Country', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Type', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Rate/Amount', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Minimum', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Maximum', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Label', 'customs-fees-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'customs-fees-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody id="cfwc-rules-tbody">
				<?php if ( ! empty( $rules ) ) : ?>
					<?php foreach ( $rules as $index => $rule ) : ?>
						<tr>
							<td><?php echo esc_html( WC()->countries->countries[ $rule['country'] ] ?? $rule['country'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $rule['type'] ) ); ?></td>
							<td>
								<?php
								if ( 'percentage' === $rule['type'] ) {
									echo esc_html( $rule['rate'] . '%' );
								} else {
									echo wp_kses_post( wc_price( $rule['amount'] ) );
								}
								?>
							</td>
							<td><?php echo wp_kses_post( wc_price( $rule['minimum'] ?? 0 ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $rule['maximum'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( $rule['label'] ?? '' ); ?></td>
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
		
		<p>
			<button type="button" class="button button-primary cfwc-add-rule">
				<?php esc_html_e( 'Add New Rule', 'customs-fees-for-woocommerce' ); ?>
			</button>
		</p>
	</div>
	
	<!-- Hidden input for rules data -->
	<input type="hidden" name="cfwc_rules" id="cfwc_rules" value='<?php echo esc_attr( wp_json_encode( $rules ) ); ?>' />
	<!-- Hidden input to trigger WooCommerce form change detection -->
	<input type="hidden" name="cfwc_rules_changed" id="cfwc_rules_changed" value="" />
	<?php wp_nonce_field( 'cfwc_save_rules', 'cfwc_rules_nonce' ); ?>
</div>

<style>
.cfwc-preset-loader {
	background: #f9f9f9;
	border: 1px solid #ddd;
	padding: 15px;
	margin-bottom: 20px;
	border-radius: 4px;
}

.cfwc-preset-loader h3 {
	margin-top: 0;
}

.cfwc-notice {
	display: none;
	margin: 10px 0;
	padding: 10px;
	border-left: 4px solid #00a0d2;
	background: #f0f8ff;
}

.cfwc-notice.error {
	border-color: #dc3232;
	background: #fbeaea;
}

.cfwc-notice.warning {
	border-color: #ffb900;
	background: #fff8e5;
}

.cfwc-notice.info {
	border-color: #00a0d2;
	background: #f0f8ff;
}

.cfwc-notice.success {
	border-color: #46b450;
	background: #ecf7ed;
}

/* Preset buttons */
.cfwc-replace-preset {
	margin-left: 10px;
}

.cfwc-replace-preset.confirm-replace {
	background: #d63638;
	border-color: #d63638;
	color: #fff;
}

.cfwc-replace-preset.confirm-replace:hover {
	background: #b32d2e;
	border-color: #b32d2e;
	color: #fff;
}

/* Delete button hover state */
.cfwc-delete-rule:hover {
	color: #d63638;
	border-color: #d63638;
}

/* Table row fade effect */
.cfwc-rules-table tbody tr {
	transition: opacity 0.3s;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Helper function to enable the WooCommerce save button
	function enableSaveButton() {
		// Mark the form as changed
		$('#cfwc_rules_changed').val('1').trigger('change');
		
		// Trigger change on the main WooCommerce form
		$('#mainform, form').trigger('change');
		
		// Force enable all save buttons
		var $saveButtons = $('button[name="save"], input[name="save"], .woocommerce-save-button, .button-primary[type="submit"]');
		$saveButtons.each(function() {
			$(this).prop('disabled', false)
				   .removeClass('disabled')
				   .removeAttr('disabled')
				   .addClass('button-primary')
				   .css({
						'background': '#2271b1',
						'cursor': 'pointer',
						'opacity': '1',
						'pointer-events': 'auto'
				   });
		});
		
		// Pulse animation to draw attention
		$saveButtons.first().animate({opacity: 0.5}, 200).animate({opacity: 1}, 200);
	}
	
	// Preset data for descriptions
	var presetData = <?php echo wp_json_encode( $templates ); ?>;
	
	// Show preset description on selection
	$('#cfwc-preset-select').on('change', function() {
		var presetId = $(this).val();
		if (presetId && presetData[presetId]) {
			$('#cfwc-preset-description em').text(presetData[presetId].description);
			$('#cfwc-preset-description').show();
		} else {
			$('#cfwc-preset-description').hide();
		}
	});
	
	// Add preset rules (primary action - adds to existing like WooCommerce tax rates)
	$('.cfwc-add-preset').on('click', function() {
		var presetId = $('#cfwc-preset-select').val();
		if (!presetId) {
			showNotice('<?php echo esc_js( __( 'Please select a preset first.', 'customs-fees-for-woocommerce' ) ); ?>', 'error');
			return;
		}
		
		// No confirmation needed for adding - just like WooCommerce tax rates
		showNotice('<?php echo esc_js( __( 'Adding preset rules...', 'customs-fees-for-woocommerce' ) ); ?>', 'info');
		applyPreset(presetId, true); // true = add to existing
	});
	
	// Replace all rules (secondary action - requires double-click confirmation)
	$('.cfwc-replace-preset').on('click', function() {
		var presetId = $('#cfwc-preset-select').val();
		if (!presetId) {
			showNotice('<?php echo esc_js( __( 'Please select a preset first.', 'customs-fees-for-woocommerce' ) ); ?>', 'error');
			return;
		}
		
		// Check if there are existing rules
		var existingRules = $('.cfwc-rules-table tbody tr').not('.no-rules').length;
		if (existingRules > 0) {
			// Require double-click confirmation for safety
			if ($(this).hasClass('confirm-replace')) {
				// Second click - proceed
				showNotice('<?php echo esc_js( __( 'Replacing all rules...', 'customs-fees-for-woocommerce' ) ); ?>', 'info');
				applyPreset(presetId, false); // false = replace all
				$(this).removeClass('confirm-replace').text('<?php echo esc_js( __( 'Replace All Rules', 'customs-fees-for-woocommerce' ) ); ?>');
			} else {
				// First click - show warning
				showNotice('<?php echo esc_js( __( 'Warning: This will remove all existing rules. Click again to confirm.', 'customs-fees-for-woocommerce' ) ); ?>', 'warning');
				$(this).addClass('confirm-replace').text('<?php echo esc_js( __( 'Click to Confirm Replace', 'customs-fees-for-woocommerce' ) ); ?>');
				
				// Reset button after 5 seconds
				var $button = $(this);
				setTimeout(function() {
					$button.removeClass('confirm-replace').text('<?php echo esc_js( __( 'Replace All Rules', 'customs-fees-for-woocommerce' ) ); ?>');
				}, 5000);
			}
		} else {
			// No existing rules, just load the preset
			showNotice('<?php echo esc_js( __( 'Loading preset rules...', 'customs-fees-for-woocommerce' ) ); ?>', 'info');
			applyPreset(presetId, false);
		}
	});
	
	// Apply preset via AJAX
	function applyPreset(presetId, append) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'cfwc_apply_template',
				template_id: presetId,
				append: append,
				nonce: '<?php echo esc_js( wp_create_nonce( 'cfwc_admin_nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					// Update rules in hidden field
					$('#cfwc_rules').val(JSON.stringify(response.data.rules));
					// Dynamically update the table without reload
					updateRulesTable(response.data.rules);
					// Reset preset selector
					$('#cfwc-preset-select').val('');
					$('#cfwc-preset-description').hide();
					// Show success message with save reminder
					var message = response.data.message || '<?php echo esc_js( __( 'Preset applied successfully!', 'customs-fees-for-woocommerce' ) ); ?>';
					message += ' <?php echo esc_js( __( 'Remember to click "Save changes" to persist these rules.', 'customs-fees-for-woocommerce' ) ); ?>';
					showNotice(message, 'success');
					
					// Enable save button using helper function
					enableSaveButton();
				} else {
					showNotice(response.data.message || '<?php echo esc_js( __( 'Failed to apply preset.', 'customs-fees-for-woocommerce' ) ); ?>', 'error');
				}
			},
			error: function() {
				showNotice('<?php echo esc_js( __( 'An error occurred. Please try again.', 'customs-fees-for-woocommerce' ) ); ?>', 'error');
			}
		});
	}
	
	// Show inline notice
	function showNotice(message, type) {
		var notice = $('<div class="cfwc-notice ' + type + '">' + message + '</div>');
		$('.cfwc-preset-loader').append(notice);
		notice.fadeIn();
		setTimeout(function() {
			notice.fadeOut(function() {
				notice.remove();
			});
		}, 3000);
	}
	
	// Add new rule (placeholder - needs full implementation)
	$('.cfwc-add-rule').on('click', function() {
		// TODO: Implement rule editor
		console.log('Rule editor to be implemented');
	});
	
	// Edit rule (placeholder)
	$('.cfwc-edit-rule').on('click', function() {
		// TODO: Implement rule editor
		console.log('Rule editor to be implemented');
	});
	
	// Delete rule - single click with inline delete
	$(document).on('click', '.cfwc-delete-rule', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var index = $button.data('index');
		var $row = $button.closest('tr');
		
		// Simply delete the rule immediately - like WooCommerce tax rates
		// Get current rules
		var rules = JSON.parse($('#cfwc_rules').val() || '[]');
		// Remove the rule
		rules.splice(index, 1);
		// Update hidden field
		$('#cfwc_rules').val(JSON.stringify(rules));
		
		// Add strike-through effect then remove
		$row.css('opacity', '0.5').css('text-decoration', 'line-through');
		
		// Remove the row with fade effect
		setTimeout(function() {
			$row.fadeOut(200, function() {
				$row.remove();
				// Update table if no rules left
				if ($('#cfwc-rules-tbody tr').not('.no-rules').length === 0) {
					$('#cfwc-rules-tbody').html('<tr class="no-rules"><td colspan="7"><?php echo esc_js( __( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ) ); ?></td></tr>');
				}
				// Re-index remaining delete buttons
				updateRuleIndexes();
			});
		}, 300);
		
		// Show notice and enable save button
		showNotice('<?php echo esc_js( __( 'Rule removed. Click "Save changes" to persist.', 'customs-fees-for-woocommerce' ) ); ?>', 'warning');
		
		// Enable save button using helper function
		enableSaveButton();
	});
	
	// Function to update rule indexes after deletion
	function updateRuleIndexes() {
		$('#cfwc-rules-tbody tr').each(function(index) {
			$(this).find('.cfwc-edit-rule').data('index', index).attr('data-index', index);
			$(this).find('.cfwc-delete-rule').data('index', index).attr('data-index', index);
		});
	}
	
	// Function to dynamically update rules table
	function updateRulesTable(rules) {
		var tbody = $('#cfwc-rules-tbody');
		
		// Check if we need to remove "no rules" message
		if (tbody.find('.no-rules').length > 0) {
			tbody.find('.no-rules').fadeOut(200, function() {
				$(this).remove();
				addRulesToTable(rules, tbody, true);
			});
		} else {
			addRulesToTable(rules, tbody, false);
		}
	}
	
	// Helper function to add rules to table
	function addRulesToTable(rules, tbody, isEmptyTable) {
		if (rules.length === 0 && isEmptyTable) {
			tbody.html('<tr class="no-rules"><td colspan="7"><?php echo esc_js( __( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ) ); ?></td></tr>');
			return;
		}
		
		// For replace mode, clear the table
		if (!isEmptyTable) {
			tbody.empty();
		}
		
		// Get country names
		var countries = <?php echo wp_json_encode( WC()->countries->countries ); ?>;
		
		// Add each rule as a table row with fade-in effect
		$.each(rules, function(index, rule) {
			var row = $('<tr style="display:none;">');
			row.html(
				'<td>' + (countries[rule.country] || rule.country) + '</td>' +
				'<td>' + rule.type.charAt(0).toUpperCase() + rule.type.slice(1) + '</td>' +
				'<td>' + (rule.type === 'percentage' ? rule.rate + '%' : formatPrice(rule.amount)) + '</td>' +
				'<td>' + formatPrice(rule.minimum || 0) + '</td>' +
				'<td>' + formatPrice(rule.maximum || 0) + '</td>' +
				'<td>' + (rule.label || '') + '</td>' +
				'<td>' +
					'<button type="button" class="button cfwc-edit-rule" data-index="' + index + '"><?php echo esc_js( __( 'Edit', 'customs-fees-for-woocommerce' ) ); ?></button> ' +
					'<button type="button" class="button cfwc-delete-rule" data-index="' + index + '"><?php echo esc_js( __( 'Delete', 'customs-fees-for-woocommerce' ) ); ?></button>' +
				'</td>'
			);
			
			tbody.append(row);
			// Fade in with slight delay for visual effect
			setTimeout(function() {
				row.fadeIn(300);
			}, index * 50);
		});
	}
	
	// Helper function to format price (basic implementation)
	function formatPrice(amount) {
		var currencySymbol = '<?php echo esc_js( get_woocommerce_currency_symbol() ); ?>';
		var decimals = <?php echo absint( wc_get_price_decimals() ); ?>;
		var formattedAmount = parseFloat(amount).toFixed(decimals);
		
		// Basic formatting - you might want to enhance this
		return currencySymbol + formattedAmount;
	}
});
</script>

