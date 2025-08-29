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
		<button type="button" class="button cfwc-delete-all">
			<?php esc_html_e( 'Delete All Rules', 'customs-fees-for-woocommerce' ); ?>
		</button>
		
		<div id="cfwc-preset-description" style="margin-top: 10px; display: none;">
			<em></em>
		</div>
	</div>
	
	<hr>
	
	<!-- Rules Table -->
	<div class="cfwc-rules-table">
		<div class="cfwc-rules-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
			<div>
				<h3 style="margin: 0 0 5px 0;"><?php esc_html_e( 'All Rules', 'customs-fees-for-woocommerce' ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Configure customs fee rules based on destination countries and product origins.', 'customs-fees-for-woocommerce' ); ?></p>
			</div>
			<button type="button" class="button cfwc-add-rule">
				<?php esc_html_e( 'Add New Rule', 'customs-fees-for-woocommerce' ); ?>
			</button>
		</div>
		
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 20%;"><?php esc_html_e( 'Label', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 18%;"><?php esc_html_e( 'Destination', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 18%;"><?php esc_html_e( 'Origin Country', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Type', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 12%;"><?php esc_html_e( 'Rate/Amount', 'customs-fees-for-woocommerce' ); ?></th>
					<th style="width: 20%;"><?php esc_html_e( 'Actions', 'customs-fees-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody id="cfwc-rules-tbody">
				<?php if ( ! empty( $rules ) ) : ?>
					<?php foreach ( $rules as $index => $rule ) : ?>
						<tr>
							<td><?php echo esc_html( $rule['label'] ?? '' ); ?></td>
							<td><?php echo esc_html( WC()->countries->countries[ $rule['country'] ] ?? $rule['country'] ); ?></td>
							<td>
								<?php 
								$origin = $rule['origin_country'] ?? '';
								if ( empty( $origin ) ) {
									esc_html_e( 'All Origins', 'customs-fees-for-woocommerce' );
								} elseif ( 'EU' === $origin ) {
									esc_html_e( 'EU Countries', 'customs-fees-for-woocommerce' );
								} else {
									echo esc_html( WC()->countries->countries[ $origin ] ?? $origin );
								}
								?>
							</td>
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
						<td colspan="6"><?php esc_html_e( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
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

/* Admin notice styles - Fixed positioning and width */
.cfwc-admin-notice {
	position: relative;
	margin: 5px 0 15px;
	padding: 12px 40px 12px 12px;
	border: 1px solid transparent;
	border-left-width: 4px;
	box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
	background: #fff;
	display: block;
}

.cfwc-admin-notice.error {
	border-left-color: #dc3232;
}

.cfwc-admin-notice.warning {
	border-left-color: #ffb900;
}

.cfwc-admin-notice.info {
	border-left-color: #00a0d2;
}

.cfwc-admin-notice.success {
	border-left-color: #46b450;
}

.cfwc-admin-notice .notice-message {
	margin: 0;
	padding: 0;
}

.cfwc-admin-notice button.notice-dismiss {
	position: absolute;
	top: 0;
	right: 1px;
	border: none;
	margin: 0;
	padding: 9px;
	background: none;
	color: #b4b9be;
	cursor: pointer;
	width: 36px;
	height: 36px;
	text-align: center;
}

.cfwc-admin-notice button.notice-dismiss:before {
	content: "\f153";
	font: normal 16px/20px dashicons;
	speak: never;
	display: block;
	width: 20px;
	height: 20px;
	text-align: center;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}

.cfwc-admin-notice button.notice-dismiss:hover {
	color: #c00;
}

.cfwc-admin-notice button.notice-dismiss:focus {
	outline: none;
	box-shadow: 0 0 0 1px #5b9dd9, 0 0 2px 1px rgba(30,140,190,.8);
}

/* Delete all button styles */
.cfwc-delete-all {
	margin-left: 10px;
	color: #a00;
	border-color: #a00;
}

.cfwc-delete-all:hover {
	color: #dc3232;
	border-color: #dc3232;
}

.cfwc-delete-all.confirm-delete {
	background: #d63638;
	border-color: #d63638;
	color: #fff;
}

.cfwc-delete-all.confirm-delete:hover {
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

/* Table styling improvements */
.cfwc-rules-table table {
	table-layout: fixed;
}

.cfwc-rules-table th,
.cfwc-rules-table td {
	padding: 10px;
	vertical-align: middle;
}

/* Editing row styles */
.cfwc-rule-editing {
	background: #f0f8ff;
	border: 1px solid #0073aa;
}

.cfwc-rule-editing td {
	padding: 10px 5px;
}

.cfwc-rule-field {
	width: 100%;
	max-width: 100%;
}

/* WooCommerce native select2 styling - match WooCommerce exactly */
.cfwc-rule-editing .wc-enhanced-select,
.cfwc-country-select,
.cfwc-origin-select {
	min-width: 150px !important;
	max-width: 100% !important;
	width: 100% !important;
}

/* Let WooCommerce handle Select2 styling natively */
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
	
	// Initialize Select2 using WooCommerce's native enhanced select
	function initCountrySelect(selector) {
		// Use WooCommerce's native enhanced select functionality
		if ( typeof wc_enhanced_select_params !== 'undefined' ) {
			$(selector).selectWoo({
				minimumResultsForSearch: 10,
				allowClear: true,
				placeholder: $(selector).data('placeholder') || wc_enhanced_select_params.i18n_no_matches,
				width: '100%'
			}).addClass('enhanced');
		} else if ($.fn.select2) {
			// Fallback to regular select2
			$(selector).select2({
				minimumResultsForSearch: 10,
				allowClear: true,
				placeholder: '<?php echo esc_js( __( 'Select a country', 'customs-fees-for-woocommerce' ) ); ?>',
				width: '100%'
			});
		}
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
	
	// Delete all rules
	$('.cfwc-delete-all').on('click', function() {
		// Check if there are existing rules
		var existingRules = $('.cfwc-rules-table tbody tr').not('.no-rules').length;
		if (existingRules === 0) {
			showNotice('<?php echo esc_js( __( 'No rules to delete.', 'customs-fees-for-woocommerce' ) ); ?>', 'warning');
			return;
		}
		
		// Require confirmation
		if ($(this).hasClass('confirm-delete')) {
			// Second click - proceed
			var rules = [];
			$('#cfwc_rules').val(JSON.stringify(rules));
			updateRulesTable(rules);
			enableSaveButton();
			showNotice('<?php echo esc_js( __( 'All rules deleted. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ) ); ?>', 'success');
			$(this).removeClass('confirm-delete').text('<?php echo esc_js( __( 'Delete All Rules', 'customs-fees-for-woocommerce' ) ); ?>');
		} else {
			// First click - show warning
			showNotice('<?php echo esc_js( __( 'Warning: This will delete all existing rules. Click again to confirm.', 'customs-fees-for-woocommerce' ) ); ?>', 'warning');
			$(this).addClass('confirm-delete').text('<?php echo esc_js( __( 'Click to Confirm Delete', 'customs-fees-for-woocommerce' ) ); ?>');
			
			// Reset button after 5 seconds
			var $button = $(this);
			setTimeout(function() {
				$button.removeClass('confirm-delete').text('<?php echo esc_js( __( 'Delete All Rules', 'customs-fees-for-woocommerce' ) ); ?>');
			}, 5000);
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
			error: function(xhr, status, error) {
				console.error('CFWC Preset Error:', status, error);
				console.error('Response:', xhr.responseText);
				var errorMsg = '<?php echo esc_js( __( 'An error occurred while applying the preset.', 'customs-fees-for-woocommerce' ) ); ?>';
				if (xhr.responseText) {
					errorMsg += ' ' + '<?php echo esc_js( __( 'Check browser console for details.', 'customs-fees-for-woocommerce' ) ); ?>';
				}
				showNotice(errorMsg, 'error');
			}
		});
	}
	
	// Show admin notice - Fixed positioning
	function showNotice(message, type) {
		// Remove any existing notices
		$('.cfwc-admin-notice').remove();
		
		var noticeHtml = '<div class="cfwc-admin-notice notice is-dismissible ' + type + '">' +
			'<p class="notice-message">' + message + '</p>' +
			'<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js( __( 'Dismiss this notice', 'customs-fees-for-woocommerce' ) ); ?></span></button>' +
			'</div>';
		
		var $notice = $(noticeHtml);
		
		// Insert after the preset loader section
		$('.cfwc-preset-loader').after($notice);
		
		// Dismiss on click
		$notice.find('.notice-dismiss').on('click', function() {
			$notice.fadeOut(300, function() {
				$notice.remove();
			});
		});
		
		// Auto-dismiss after 10 seconds (longer duration for better readability)
		setTimeout(function() {
			$notice.fadeOut(300, function() {
				$notice.remove();
			});
		}, 10000);
	}
	
	// Build country options
	function getCountryOptions(selectedCountry) {
		var countries = <?php echo wp_json_encode( WC()->countries->get_countries() ); ?>;
		var html = '<option value=""><?php echo esc_js( __( 'Select country...', 'customs-fees-for-woocommerce' ) ); ?></option>';
		for (var code in countries) {
			html += '<option value="' + code + '"' + (code === selectedCountry ? ' selected' : '') + '>' + countries[code] + '</option>';
		}
		return html;
	}
	
	// Build origin country options (including special options)
	function getOriginOptions(selectedOrigin) {
		var countries = <?php echo wp_json_encode( WC()->countries->get_countries() ); ?>;
		var html = '<option value=""><?php echo esc_js( __( 'All Origins', 'customs-fees-for-woocommerce' ) ); ?></option>';
		html += '<option value="EU"' + (selectedOrigin === 'EU' ? ' selected' : '') + '><?php echo esc_js( __( 'EU Countries', 'customs-fees-for-woocommerce' ) ); ?></option>';
		html += '<optgroup label="<?php echo esc_js( __( 'Specific Country', 'customs-fees-for-woocommerce' ) ); ?>">';
		for (var code in countries) {
			html += '<option value="' + code + '"' + (code === selectedOrigin ? ' selected' : '') + '>' + countries[code] + '</option>';
		}
		html += '</optgroup>';
		return html;
	}
	
	// Add new rule functionality
	$('.cfwc-add-rule').on('click', function(e) {
		e.preventDefault();
		
		// Get existing rules
		var rules = JSON.parse($('#cfwc_rules').val() || '[]');
		
		// Create new empty rule
		var newRule = {
			label: '',
			country: '',
			origin_country: '',
			type: 'percentage',
			rate: 0,
			amount: 0,
			taxable: true,
			tax_class: ''
		};
		
		// Add to rules array
		rules.push(newRule);
		
		// Update hidden field
		$('#cfwc_rules').val(JSON.stringify(rules));
		
		// Create new row HTML (editable fields)
		var newRowHtml = '<tr class="cfwc-rule-row cfwc-rule-editing">';
		
		// Label input (first column)
		newRowHtml += '<td><input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="" placeholder="<?php echo esc_attr( __( 'Fee label', 'customs-fees-for-woocommerce' ) ); ?>" style="width: 100%;" /></td>';
		
		// Destination country selector - use wc-enhanced-select class
		newRowHtml += '<td><select name="cfwc_rule_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="country" data-placeholder="<?php echo esc_attr( __( 'Choose a country...', 'customs-fees-for-woocommerce' ) ); ?>">';
		newRowHtml += getCountryOptions('');
		newRowHtml += '</select></td>';
		
		// Origin country selector - use wc-enhanced-select class
		newRowHtml += '<td><select name="cfwc_rule_origin" class="cfwc-rule-field cfwc-origin-select wc-enhanced-select" data-field="origin_country" data-placeholder="<?php echo esc_attr( __( 'Choose origin...', 'customs-fees-for-woocommerce' ) ); ?>">';
		newRowHtml += getOriginOptions('');
		newRowHtml += '</select></td>';
		
		// Type selector
		newRowHtml += '<td><select name="cfwc_rule_type" class="cfwc-rule-field" data-field="type">';
		newRowHtml += '<option value="percentage"><?php echo esc_js( __( 'Percentage', 'customs-fees-for-woocommerce' ) ); ?></option>';
		newRowHtml += '<option value="flat"><?php echo esc_js( __( 'Flat', 'customs-fees-for-woocommerce' ) ); ?></option>';
		newRowHtml += '</select></td>';
		
		// Rate/Amount input
		newRowHtml += '<td><input type="number" name="cfwc_rule_rate" class="cfwc-rule-field" data-field="rate" value="0" step="0.01" style="width: 80px;" /></td>';
		
		// Actions
		newRowHtml += '<td>';
		newRowHtml += '<button type="button" class="button button-primary cfwc-save-rule"><?php echo esc_js( __( 'Save', 'customs-fees-for-woocommerce' ) ); ?></button> ';
		newRowHtml += '<button type="button" class="button cfwc-cancel-edit"><?php echo esc_js( __( 'Cancel', 'customs-fees-for-woocommerce' ) ); ?></button>';
		newRowHtml += '</td>';
		
		newRowHtml += '</tr>';
		
		// Remove "no rules" row if exists
		$('.cfwc-rules-table tbody .no-rules').remove();
		
		// Add new row to table
		$('.cfwc-rules-table tbody').append(newRowHtml);
		
		// Initialize Select2 on new selects
		initCountrySelect('.cfwc-rules-table tbody tr:last .cfwc-country-select');
		initCountrySelect('.cfwc-rules-table tbody tr:last .cfwc-origin-select');
		
		// Scroll to new row
		$('html, body').animate({
			scrollTop: $('.cfwc-rules-table tbody tr:last').offset().top - 100
		}, 500);
		
		// Focus on label field
		$('.cfwc-rules-table tbody tr:last input[name="cfwc_rule_label"]').focus();
		
		// Handle type change
		$('.cfwc-rules-table tbody tr:last select[name="cfwc_rule_type"]').on('change', function() {
			var $row = $(this).closest('tr');
			var type = $(this).val();
			
			if (type === 'percentage') {
				$row.find('input[name="cfwc_rule_rate"]').attr('data-field', 'rate').attr('placeholder', '%');
			} else {
				$row.find('input[name="cfwc_rule_rate"]').attr('data-field', 'amount').attr('placeholder', '<?php echo esc_attr( get_woocommerce_currency_symbol() ); ?>');
			}
		});
	});
	
	// Edit rule functionality
	$(document).on('click', '.cfwc-edit-rule', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var $row = $button.closest('tr');
		var index = $button.data('index');
		var rules = JSON.parse($('#cfwc_rules').val() || '[]');
		var rule = rules[index];
		
		// Create edit row HTML
		var editRowHtml = '';
		
		// Label input (first column)
		editRowHtml += '<td><input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="' + (rule.label || '') + '" placeholder="<?php echo esc_attr( __( 'Fee label', 'customs-fees-for-woocommerce' ) ); ?>" style="width: 100%;" /></td>';
		
		// Destination country selector - use wc-enhanced-select class
		editRowHtml += '<td><select name="cfwc_rule_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="country" data-placeholder="<?php echo esc_attr( __( 'Choose a country...', 'customs-fees-for-woocommerce' ) ); ?>">';
		editRowHtml += getCountryOptions(rule.country);
		editRowHtml += '</select></td>';
		
		// Origin country selector - use wc-enhanced-select class
		editRowHtml += '<td><select name="cfwc_rule_origin" class="cfwc-rule-field cfwc-origin-select wc-enhanced-select" data-field="origin_country" data-placeholder="<?php echo esc_attr( __( 'Choose origin...', 'customs-fees-for-woocommerce' ) ); ?>">';
		editRowHtml += getOriginOptions(rule.origin_country || '');
		editRowHtml += '</select></td>';
		
		// Type selector
		editRowHtml += '<td><select name="cfwc_rule_type" class="cfwc-rule-field" data-field="type">';
		editRowHtml += '<option value="percentage"' + (rule.type === 'percentage' ? ' selected' : '') + '><?php echo esc_js( __( 'Percentage', 'customs-fees-for-woocommerce' ) ); ?></option>';
		editRowHtml += '<option value="flat"' + (rule.type === 'flat' ? ' selected' : '') + '><?php echo esc_js( __( 'Flat', 'customs-fees-for-woocommerce' ) ); ?></option>';
		editRowHtml += '</select></td>';
		
		// Rate/Amount input
		var rateField = rule.type === 'percentage' ? 'rate' : 'amount';
		var rateValue = rule.type === 'percentage' ? rule.rate : rule.amount;
		editRowHtml += '<td><input type="number" name="cfwc_rule_rate" class="cfwc-rule-field" data-field="' + rateField + '" value="' + rateValue + '" step="0.01" style="width: 80px;" /></td>';
		
		// Actions
		editRowHtml += '<td>';
		editRowHtml += '<button type="button" class="button button-primary cfwc-save-rule" data-index="' + index + '"><?php echo esc_js( __( 'Save', 'customs-fees-for-woocommerce' ) ); ?></button> ';
		editRowHtml += '<button type="button" class="button cfwc-cancel-edit"><?php echo esc_js( __( 'Cancel', 'customs-fees-for-woocommerce' ) ); ?></button>';
		editRowHtml += '</td>';
		
		// Replace row content
		$row.addClass('cfwc-rule-editing').html(editRowHtml);
		
		// Initialize Select2 on selects
		initCountrySelect($row.find('.cfwc-country-select'));
		initCountrySelect($row.find('.cfwc-origin-select'));
		
		// Handle type change
		$row.find('select[name="cfwc_rule_type"]').on('change', function() {
			var type = $(this).val();
			
			if (type === 'percentage') {
				$row.find('input[name="cfwc_rule_rate"]').attr('data-field', 'rate').attr('placeholder', '%');
			} else {
				$row.find('input[name="cfwc_rule_rate"]').attr('data-field', 'amount').attr('placeholder', '<?php echo esc_attr( get_woocommerce_currency_symbol() ); ?>');
			}
		});
	});
	
	// Save rule functionality
	$(document).on('click', '.cfwc-save-rule', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var $row = $button.closest('tr');
		var index = $button.data('index');
		var rules = JSON.parse($('#cfwc_rules').val() || '[]');
		
		// Collect data from fields
		var ruleData = {};
		$row.find('.cfwc-rule-field').each(function() {
			var field = $(this).data('field');
			var value = $(this).val();
			
			if (field === 'rate' || field === 'amount') {
				value = parseFloat(value) || 0;
			}
			
			ruleData[field] = value;
		});
		
		// Ensure all required fields are present
		ruleData.taxable = ruleData.taxable !== undefined ? ruleData.taxable : true;
		ruleData.tax_class = ruleData.tax_class || '';
		
		// For new rules, ensure amount field exists even if type is percentage
		if (!ruleData.hasOwnProperty('amount')) {
			ruleData.amount = 0;
		}
		if (!ruleData.hasOwnProperty('rate')) {
			ruleData.rate = 0;
		}
		
		// Update or add rule
		if (index !== undefined) {
			rules[index] = $.extend({}, rules[index], ruleData);
		} else {
			// Find the new rule (last one)
			rules[rules.length - 1] = ruleData;
		}
		
		// Update hidden field
		$('#cfwc_rules').val(JSON.stringify(rules));
		
		// Update table
		updateRulesTable(rules);
		
		// Enable save button
		enableSaveButton();
		
		showNotice('<?php echo esc_js( __( 'Rule saved. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ) ); ?>', 'success');
	});
	
	// Cancel edit functionality
	$(document).on('click', '.cfwc-cancel-edit', function(e) {
		e.preventDefault();
		
		var rules = JSON.parse($('#cfwc_rules').val() || '[]');
		updateRulesTable(rules);
	});
	
	// Delete rule functionality - CUSTOM CONFIRMATION
	$(document).on('click', '.cfwc-delete-rule', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var index = $button.data('index');
		
		// Double-click confirmation like Delete All
		if ($button.hasClass('confirm-delete')) {
			// Second click - proceed with deletion
			var rules = JSON.parse($('#cfwc_rules').val() || '[]');
			
			// Remove rule
			rules.splice(index, 1);
			
			// Update hidden field
			$('#cfwc_rules').val(JSON.stringify(rules));
			
			// Update table
			updateRulesTable(rules);
			
			// Enable save button
			enableSaveButton();
			
			// Show success message for deletion
			showNotice('<?php echo esc_js( __( 'Rule deleted successfully. Remember to click "Save changes" to persist.', 'customs-fees-for-woocommerce' ) ); ?>', 'success');
		} else {
			// First click - show warning
			showNotice('<?php echo esc_js( __( 'Click the delete button again to confirm deletion.', 'customs-fees-for-woocommerce' ) ); ?>', 'warning');
			$button.addClass('confirm-delete').css('background', '#d63638').css('color', '#fff');
			
			// Reset button after 3 seconds
			setTimeout(function() {
				$button.removeClass('confirm-delete').css('background', '').css('color', '');
			}, 3000);
		}
	});
	
	// Update rules table
	function updateRulesTable(rules) {
		var countries = <?php echo wp_json_encode( WC()->countries->get_countries() ); ?>;
		var tbody = $('#cfwc-rules-tbody');
		tbody.empty();
		
		if (rules.length === 0) {
			tbody.append('<tr class="no-rules"><td colspan="6"><?php echo esc_js( __( 'No rules configured. Use the preset loader above or add rules manually.', 'customs-fees-for-woocommerce' ) ); ?></td></tr>');
		} else {
			$.each(rules, function(index, rule) {
				var row = '<tr>';
				
				// Label (first column)
				row += '<td>' + (rule.label || '') + '</td>';
				
				// Destination country
				row += '<td>' + (countries[rule.country] || rule.country || '<?php echo esc_js( __( 'Not set', 'customs-fees-for-woocommerce' ) ); ?>') + '</td>';
				
				// Origin country
				var originText = '';
				if (!rule.origin_country || rule.origin_country === '') {
					originText = '<?php echo esc_js( __( 'All Origins', 'customs-fees-for-woocommerce' ) ); ?>';
				} else if (rule.origin_country === 'EU') {
					originText = '<?php echo esc_js( __( 'EU Countries', 'customs-fees-for-woocommerce' ) ); ?>';
				} else {
					originText = countries[rule.origin_country] || rule.origin_country;
				}
				row += '<td>' + originText + '</td>';
				
				// Type
				row += '<td>' + (rule.type === 'percentage' ? '<?php echo esc_js( __( 'Percentage', 'customs-fees-for-woocommerce' ) ); ?>' : '<?php echo esc_js( __( 'Flat', 'customs-fees-for-woocommerce' ) ); ?>') + '</td>';
				
				// Rate/Amount
				if (rule.type === 'percentage') {
					row += '<td>' + (rule.rate || 0) + '%</td>';
				} else {
					row += '<td><?php echo esc_js( get_woocommerce_currency_symbol() ); ?>' + (rule.amount || 0) + '</td>';
				}
				
				// Actions
				row += '<td>';
				row += '<button type="button" class="button cfwc-edit-rule" data-index="' + index + '"><?php echo esc_js( __( 'Edit', 'customs-fees-for-woocommerce' ) ); ?></button> ';
				row += '<button type="button" class="button cfwc-delete-rule" data-index="' + index + '"><?php echo esc_js( __( 'Delete', 'customs-fees-for-woocommerce' ) ); ?></button>';
				row += '</td>';
				
				row += '</tr>';
				tbody.append(row);
			});
		}
	}
});
</script>