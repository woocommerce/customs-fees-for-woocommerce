/**
 * Admin JavaScript for Customs Fees for WooCommerce
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

(function ($) {
	"use strict";

	$( document ).ready(
		function () {
			// Get localized data.
			var cfwc_admin      = window.cfwc_admin || {};
			var ajaxurl         = cfwc_admin.ajax_url || window.ajaxurl;
			var countries       = cfwc_admin.countries || {};
			var currency_symbol = cfwc_admin.currency_symbol || "$";
			var presetData      = cfwc_admin.templates || {};
			var strings         = cfwc_admin.strings || {};

			// Initialize Select2 for preset dropdown.
			if ($.fn.select2) {
				$( "#cfwc-preset-select" ).select2(
					{
						placeholder: strings.select_preset || "-- Select a preset --",
						allowClear: true,
						width: "resolve",
						minimumResultsForSearch: 10,
					}
				);
			}

			// Helper: Create an element with attributes, styles, and text/HTML.
			function el(tag, attrs, css, text, html) {
				var $e = $( "<" + tag + ">" );
				if (attrs) {
					$e.attr( attrs );
				}
				if (css) {
					$e.css( css );
				}
				if (text !== undefined && text !== null) {
					$e.text( text );
				}
				if (html !== undefined && html !== null) {
					$e.html( html );
				}
				return $e;
			}

			// Helper function to enable the WooCommerce save button.
			function enableSaveButton() {
				// Mark the form as changed.
				$( "#cfwc_rules_changed" ).val( "1" ).trigger( "change" );

				// Trigger change on the main WooCommerce form.
				$( "#mainform, form" ).trigger( "change" );

				// Force enable all save buttons.
				var $saveButtons = $(
					'button[name="save"], input[name="save"], .woocommerce-save-button, .button-primary[type="submit"]'
				);
				$saveButtons.each(
					function () {
						$( this )
						.prop( "disabled", false )
						.removeClass( "disabled" )
						.removeAttr( "disabled" )
						.addClass( "button-primary" )
						.css(
							{
								background: "#2271b1",
								cursor: "pointer",
								opacity: "1",
								"pointer-events": "auto",
							}
						);
					}
				);

				// Pulse animation to draw attention.
				$saveButtons
					.first()
					.animate( { opacity: 0.5 }, 200 )
					.animate( { opacity: 1 }, 200 );
			}

			// Initialize Select2 using WooCommerce's native enhanced select.
			function initCountrySelect(selector) {
				// Use WooCommerce's native enhanced select functionality.
				if (typeof wc_enhanced_select_params !== "undefined") {
					$( selector )
					.selectWoo(
						{
							minimumResultsForSearch: 10,
							allowClear: true,
							placeholder:
							$( selector ).data( "placeholder" ) || wc_enhanced_select_params.i18n_no_matches,
							width: "100%",
						}
					)
						.addClass( "enhanced" );
				} else if ($.fn.select2) {
					// Fallback to regular select2.
					$( selector ).select2(
						{
							minimumResultsForSearch: 10,
							allowClear: true,
							placeholder: strings.select_country || "Select a country",
							width: "100%",
						}
					);
				}
			}

			// Show preset description on selection.
			$( "#cfwc-preset-select" ).on(
				"change",
				function () {
					var presetId = $( this ).val();
					if (presetId && presetData[presetId]) {
						$( "#cfwc-preset-description em" ).text( presetData[presetId].description );
						$( "#cfwc-preset-description" ).show();
					} else {
						$( "#cfwc-preset-description" ).hide();
					}
				}
			);

			// Handle match type changes to show/hide category and HS code fields.
			$( document ).on(
				"change",
				".cfwc-match-type",
				function () {
					var matchType          = $( this ).val();
					var $row               = $( this ).closest( "tr" );
					var $categorySelect    = $row.find( ".cfwc-category-select" );
					var $hsCodeInput       = $row.find( ".cfwc-hs-code" );
					var $requiredIndicator = $row.find( ".cfwc-field-required" );
					var $spacer            = $row.find( ".cfwc-field-spacer" );

					// Show fields based on match type.
					if (matchType === "all") {
						// All Products - hide both category and HS code fields, show "Not required".
						$categorySelect.hide().removeAttr( "required" );
						$hsCodeInput.hide().removeAttr( "required" );
						$spacer.hide();
						$requiredIndicator.show().html( "Not required" );
						$categorySelect.attr( "data-placeholder", "Select categories..." );
						$hsCodeInput.attr( "placeholder", "HS Code (e.g., 6109* or 61,62)" );
					} else if (matchType === "category") {
						// By Category - show only category field, hide HS code.
						$categorySelect.show().attr( "required", "required" );
						$hsCodeInput.hide().removeAttr( "required" );
						$spacer.hide();
						$requiredIndicator.show().html( "Required *" );
						$categorySelect.attr( "data-placeholder", "Select categories..." );
						$hsCodeInput.attr( "placeholder", "HS Code (e.g., 6109* or 61,62)" );
					} else if (matchType === "hs_code") {
						// By HS Code - show both fields but category is not required.
						$categorySelect.show().removeAttr( "required" );
						$spacer.show().css( "display", "block" ); // Show spacer between fields.
						$hsCodeInput.show().attr( "required", "required" );
						$requiredIndicator.show().html( "Category: Not required<br>HS Code: Required *" );
						$categorySelect.attr( "data-placeholder", "Select categories (optional)" );
						$hsCodeInput.attr( "placeholder", "HS Code (e.g., 6109* or 61,62)" );
					} else if (matchType === "combined") {
						// Category + HS Code - show both fields with spacing.
						$categorySelect.show().attr( "required", "required" );
						$spacer.show().css( "display", "block" ); // Show spacer between fields.
						$hsCodeInput.show().attr( "required", "required" );
						$requiredIndicator.show().html( "Both required *" );
						$categorySelect.attr( "data-placeholder", "Select categories..." );
						$hsCodeInput.attr( "placeholder", "HS Code (e.g., 6109* or 61,62)" );
					}

					// Re-initialize WooCommerce Select2/SelectWoo if needed.
					if ($categorySelect.hasClass( "wc-enhanced-select" )) {
						// For WooCommerce enhanced selects, trigger proper update.
						if ($categorySelect.data( "select2" )) {
							$categorySelect.trigger( "change.select2" );
						} else if ($.fn.selectWoo) {
							// Reinitialize if SelectWoo is available.
							$categorySelect.selectWoo();
						}
					}
				}
			);

			// Handle stacking mode changes to show appropriate help text.
			$( document ).on(
				"change",
				".cfwc-stacking-select",
				function () {
					var stackingMode   = $( this ).val();
					var $helpContainer = $( this ).siblings( ".description" );

					// Hide all help texts.
					$helpContainer.find( "span" ).hide();

					// Show the relevant help text.
					$helpContainer.find( ".stacking-help-" + stackingMode ).show();
				}
			);

			// Delete all rules with improved notification handling.
			var deleteButtonTimeout = null;
			$( ".cfwc-delete-all" ).on(
				"click",
				function () {
					// Check if there are existing rules.
					var existingRules = $( ".cfwc-rules-table tbody tr" ).not( ".no-rules" ).length;
					if (existingRules === 0) {
						showNotice( strings.no_rules_delete || "No rules to delete.", "warning" );
						return;
					}

					var $button = $( this );

					// Keep confirmation for Delete All (destructive action).
					if ($button.hasClass( "confirm-delete" )) {
						// Second click - proceed with deletion.

						// Clear any pending timeout immediately.
						if (deleteButtonTimeout) {
							clearTimeout( deleteButtonTimeout );
							deleteButtonTimeout = null;
						}

						// Remove inline warning message immediately.
						$button.next( ".cfwc-delete-warning" ).remove();

						// Clear any existing notifications first to prevent overlap.
						cfwcClearSnackbars();

						// Delete the rules.
						var rules = [];
						$( "#cfwc_rules" ).val( JSON.stringify( rules ) );
						updateRulesTable( rules );
						enableSaveButton();

						// Reset button immediately (before showing success).
						$button
						.removeClass( "confirm-delete" )
						.text( strings.delete_all || "Delete All Rules" )
						.css( "background", "" )
						.css( "color", "" );

						// Show success notice after button reset.
						setTimeout(
							function () {
								showNotice(
									strings.all_deleted ||
									'All rules deleted. Remember to click "Save changes" to persist.',
									"success"
								);
							},
							100
						); // Small delay to ensure clean transition.
					} else {
						// First click - show inline warning text instead of blocking notification.
						$button
						.addClass( "confirm-delete" )
						.text( strings.confirm_delete || "Click to Confirm Delete" )
						.css( "background", "#d63638" )
						.css( "color", "#fff" );

						// Add inline warning message next to button if not already present.
						if ( ! $button.next( ".cfwc-delete-warning" ).length) {
							$button.after(
								el(
									"span",
									{ class: "cfwc-delete-warning" },
									{ color: "#d63638", "margin-left": "10px", "font-weight": 600 },
									"⚠️ This will delete ALL rules. Click again to confirm."
								)
							);
						}

						// Reset button and remove warning after 5 seconds if not clicked again.
						deleteButtonTimeout = setTimeout(
							function () {
								$button
								.removeClass( "confirm-delete" )
								.text( strings.delete_all || "Delete All Rules" )
								.css( "background", "" )
								.css( "color", "" );
								$button.next( ".cfwc-delete-warning" ).fadeOut(
									300,
									function () {
										$( this ).remove();
									}
								);
							},
							5000
						);
					}
				}
			);

			// Apply preset via AJAX.
			function applyPreset(presetId, append) {
				// Show loading notification with unique ID.
				if (window.wp && window.wp.data && window.wp.data.dispatch) {
					cfwcClearSnackbars(); // Clear any existing.
					wp.data
					.dispatch( "core/notices" )
					.createNotice(
						"info",
						append
							? strings.adding_preset || "Adding preset rules..."
							: strings.replacing_rules || "Replacing all rules with preset...",
						{
							type: "snackbar",
							isDismissible: false,
							id: "cfwc-preset-loading", // Unique ID for loading notification.
							}
					);
				}

				$.ajax(
					{
						url: ajaxurl,
						type: "POST",
						data: {
							action: "cfwc_apply_template",
							template_id: presetId,
							append: append,
							nonce: cfwc_admin.nonce,
						},
						success: function (response) {
							if (response.success) {
								// Update rules in hidden field.
								$( "#cfwc_rules" ).val( JSON.stringify( response.data.rules ) );
								// Dynamically update the table without reload.
								updateRulesTable( response.data.rules );
								// Reset preset selector.
								$( "#cfwc-preset-select" ).val( "" ).trigger( "change" );
								$( "#cfwc-preset-description" ).hide();

								// Prepare success message.
								var presetMessage =
								response.data.message ||
								strings.preset_applied ||
								"Preset imported successfully!";

								// Clear loading notification and show success.
								if (window.wp && window.wp.data && window.wp.data.dispatch) {
									// Remove the loading notification specifically.
									wp.data.dispatch( "core/notices" ).removeNotice( "cfwc-preset-loading" );

									// Small delay to ensure smooth transition.
									setTimeout(
										function () {
											// Show success message.
											wp.data
											.dispatch( "core/notices" )
											.createNotice(
												"success",
												presetMessage + ' Remember to click "Save changes" to persist.',
												{
													type: "snackbar",
													isDismissible: true,
													autoDismiss: 4000, // 4 seconds for success.
													id: "cfwc-preset-success",
												}
											);
										},
										100
									);
								} else {
									// Fallback for old WordPress.
									showNotice(
										presetMessage +
										" " +
										(strings.save_reminder ||
										'Remember to click "Save changes" to persist these rules.'),
										"success"
									);
								}

								// Enable save button using helper function.
								enableSaveButton();
							} else {
								// Remove loading notification on error.
								if (window.wp && window.wp.data && window.wp.data.dispatch) {
									wp.data.dispatch( "core/notices" ).removeNotice( "cfwc-preset-loading" );
								}
								showNotice(
									(response.data && response.data.message) ||
									strings.preset_failed ||
									"Failed to apply preset.",
									"error"
								);
							}
						},
						error: function (xhr, status, error) {
							// Remove loading notification on AJAX error.
							if (window.wp && window.wp.data && window.wp.data.dispatch) {
								wp.data.dispatch( "core/notices" ).removeNotice( "cfwc-preset-loading" );
							}
							console.error( "CFWC Preset Error:", status, error );
							console.error( "Response:", xhr.responseText );
							var errorMsg =
							strings.preset_error || "An error occurred while applying the preset.";
							if (xhr.responseText) {
								errorMsg += " " + (strings.check_console || "Check browser console for details.");
							}
							showNotice( errorMsg, "error" );
						},
					}
				);
			}

			// Show admin notice using WooCommerce snackbar.
			var noticeTimeout = null;

			// Safe helper to clear snackbar notices (avoid WP core error on undefined contexts).
			function cfwcClearSnackbars() {
				try {
					if (window.wp && window.wp.data && window.wp.data.dispatch) {
						var dispatcher = wp.data.dispatch( "core/notices" );
						if (dispatcher && typeof dispatcher.removeNotices === "function") {
							// Clear only snackbar notices to avoid removing other admin notices.
							dispatcher.removeNotices( ["snackbar"] );
						}
					}
				} catch (e) {
					// Silently ignore if API differs on older WP versions.
				}
			}

			function showNotice(message, type, clearPrevious) {
				// Default clearPrevious to true for better UX.
				if (clearPrevious === undefined) {
					clearPrevious = true;
				}

				// Clear any pending notice timeout.
				if (noticeTimeout) {
					clearTimeout( noticeTimeout );
				}

				// Use WooCommerce snackbar if available.
				if (window.wp && window.wp.data && window.wp.data.dispatch) {
					// Clear existing notices if requested (default behavior).
					if (clearPrevious) {
						cfwcClearSnackbars();
					}

					var noticeType = "info";
					if (type === "notice-success" || type === "success") {
						noticeType = "success";
					} else if (type === "notice-error" || type === "error") {
						noticeType = "error";
					} else if (type === "notice-warning" || type === "warning") {
						noticeType = "warning";
					}

					// Use WooCommerce/WordPress snackbar notification.
					wp.data.dispatch( "core/notices" ).createNotice(
						noticeType,
						message,
						{
							type: "snackbar",
							isDismissible: true,
							// Shorter duration for success messages, normal for warnings.
							autoDismiss: noticeType === "success" ? 3000 : 5000,
							id: "cfwc-notice-" + Date.now(), // Unique ID to prevent duplicates.
						}
					);
				} else {
					// Fallback to basic notice for old WordPress versions.
					$( ".cfwc-admin-notice" ).remove();
					var $notice = el(
						"div",
						{ class: "cfwc-admin-notice notice is-dismissible " + type },
						null
					).append( el( "p", null, null, message ) );
					$( ".cfwc-preset-loader" ).after( $notice );
					noticeTimeout = setTimeout(
						function () {
							$( ".cfwc-admin-notice" ).fadeOut(
								300,
								function () {
									$( this ).remove();
								}
							);
						},
						3000
					);
				}
			}

			// -------- DOM Builders (reduce manual HTML strings) --------

			function buildCountrySelect(opts) {
				// opts: { name, field, placeholder, selected, widthPercent ('47%' default) }
				var width = opts.widthPercent || "47%";
				var $sel  = el(
					"select",
					{
						name: opts.name,
						class: "cfwc-rule-field cfwc-country-select wc-enhanced-select",
						"data-field": opts.field,
						"data-placeholder": opts.placeholder,
					},
					{ width: width }
				);
				// First option
				var firstText =
					opts.firstOptionText ||
					(opts.field === "from_country"
					? strings.any_origin || "Any Origin"
					: strings.any_destination || "Any Destination");
				$sel.append( el( "option", { value: "" }, null, firstText ) );

				for (var code in countries) {
					var $opt = el( "option", { value: code }, null, countries[code] );
					if (code === (opts.selected || "")) {
						$opt.prop( "selected", true );
					}
					$sel.append( $opt );
				}
				return $sel;
			}

			function buildCategorySelect(selectedIds, show, placeholderText) {
				var $sel = el(
					"select",
					{
						name: "cfwc_rule_categories",
						class: "cfwc-rule-field cfwc-category-select wc-enhanced-select",
						"data-field": "category_ids",
						multiple: "multiple",
						"data-placeholder": placeholderText || "Select categories...",
					},
					{ width: "100%", "margin-bottom": "5px", display: show ? "" : "none" }
				);

				if (cfwc_admin.categories) {
					$.each(
						cfwc_admin.categories,
						function (id, name) {
							var selected =
							(Array.isArray( selectedIds ) ? selectedIds : []).includes( parseInt( id, 10 ) );
							$sel.append( el( "option", { value: id, selected: selected ? "selected" : null }, null, name ) );
						}
					);
				}
				return $sel;
			}

			function buildMatchTypeSelect(selected) {
				var $sel    = el(
					"select",
					{
						name: "cfwc_rule_match_type",
						class: "cfwc-rule-field cfwc-match-type",
						"data-field": "match_type",
					},
					{ width: "100%", "margin-bottom": "5px" }
				);
				var options = [
					{ v: "all", l: "All Products" },
					{ v: "category", l: "By Category" },
					{ v: "hs_code", l: "By HS Code" },
					{ v: "combined", l: "Category + HS Code" },
				];
				options.forEach(
					function (o) {
						$sel.append( el( "option", { value: o.v, selected: selected === o.v ? "selected" : null }, null, o.l ) );
					}
				);
				return $sel;
			}

			function buildTypeSelect(selected) {
				var $sel = el( "select", { name: "cfwc_rule_type", class: "cfwc-rule-field", "data-field": "type" } );
				$sel.append(
					el(
						"option",
						{ value: "percentage", selected: selected === "percentage" ? "selected" : null },
						null,
						strings.percentage || "Percentage"
					)
				);
				$sel.append(
					el( "option", { value: "flat", selected: selected === "flat" ? "selected" : null }, null, strings.flat || "Flat" )
				);
				return $sel;
			}

			function buildStackingSelect(selected) {
				var $sel    = el(
					"select",
					{
						name: "cfwc_rule_stacking",
						class: "cfwc-rule-field cfwc-stacking-select",
						"data-field": "stacking_mode",
					},
					{ width: "100%" }
				);
				var options = [
					{ v: "add", l: "Stack (Add to other fees)" },
					{ v: "override", l: "Override (Replace lower priority)" },
					{ v: "exclusive", l: "Exclusive (Only this fee)" },
				];
				options.forEach(
					function (o) {
						$sel.append( el( "option", { value: o.v, selected: (selected || "add") === o.v ? "selected" : null }, null, o.l ) );
					}
				);
				return $sel;
			}

			function buildStackingHelp(mode) {
				var $desc = el(
					"span",
					{ class: "description" },
					{ "font-size": "11px", color: "#666", "margin-top": "5px", display: "block" }
				);
				$desc.append(
					el( "span", { class: "stacking-help-add", style: mode === "add" ? "" : "display: none;" }, null, "Adds with other matching rules" )
				);
				$desc.append(
					el(
						"span",
						{ class: "stacking-help-override", style: mode === "override" ? "" : "display: none;" },
						null,
						"Replaces lower priority rules"
					)
				);
				$desc.append(
					el(
						"span",
						{ class: "stacking-help-exclusive", style: mode === "exclusive" ? "" : "display: none;" },
						null,
						"No other rules apply"
					)
				);
				return $desc;
			}

			function buildRequiredIndicator(text) {
				return el(
					"span",
					{ class: "cfwc-field-required" },
					{ display: "block", "font-size": "11px", color: "#999", "margin-top": "2px" },
					null,
					text || "Not required"
				);
			}

			function buildFieldSpacer(show) {
				return el(
					"span",
					{ class: "cfwc-field-spacer" },
					{ display: show ? "block" : "none", height : "5px", width : "100%" },
					null,
					"&nbsp;"
				);
			}

			function buildRuleEditRow(rule, index, isNew) {
				var matchType           = rule.match_type || "all";
				var showCategories      = matchType !== "all";
				var showHsCode          = matchType === "hs_code" || matchType === "combined";
				var categoryPlaceholder = matchType === "hs_code" ? "Select categories (optional)" : "Select categories...";

				var $tr = el( "tr", { class: "cfwc-rule-row cfwc-rule-editing" } );

				// Column: Label + Priority
				var $tdLabel = el( "td" );
				$tdLabel.append(
					el(
						"input",
						{
							type: "text",
							name: "cfwc_rule_label",
							class: "cfwc-rule-field",
							"data-field": "label",
							value: rule.label || "",
							placeholder: strings.fee_label || "Fee label",
						},
						{ width: "100%", "margin-bottom": "5px" }
					)
				);
				$tdLabel.append(
					el(
						"input",
						{
							type: "number",
							name: "cfwc_rule_priority",
							class: "cfwc-rule-field",
							"data-field": "priority",
							value: rule.priority || 0,
							placeholder: "Priority (0-100)",
							title: "Higher priority rules are checked first (0-100)",
							min: 0,
							max: 100,
						},
						{ width: "100%" }
					)
				);
				$tdLabel.append(
					el(
						"span",
						null,
						{ "font-size": "11px", color: "#666", "margin-top": "2px", display: "inline-block" },
						"Higher numbers = higher priority"
					)
				);
				$tr.append( $tdLabel );

				// Column: Countries (From and To)
				var $tdCountries = el( "td" );
				$tdCountries.append(
					buildCountrySelect(
						{
							name: "cfwc_rule_from_country",
							field: "from_country",
							placeholder: strings.from_country || "From (any)",
							selected: rule.from_country || rule.origin_country || "",
							widthPercent: "47%",
							firstOptionText: "Any Origin",
						}
					)
				);
				$tdCountries.append( el( "span", null, { display: "inline-block", width: "10px" }, null, "&nbsp;" ) );
				$tdCountries.append(
					buildCountrySelect(
						{
							name: "cfwc_rule_to_country",
							field: "to_country",
							placeholder: strings.to_country || "To (any)",
							selected: rule.to_country || rule.country || "",
							widthPercent: "47%",
							firstOptionText: "Any Destination",
						}
					)
				);
				$tr.append( $tdCountries );

				// Column: Products (Categories & HS Code)
				var $tdProducts = el( "td" );

				$tdProducts.append( buildMatchTypeSelect( matchType ) );

				$tdProducts.append( buildCategorySelect( rule.category_ids || [], showCategories, categoryPlaceholder ) );

				$tdProducts.append( buildFieldSpacer( matchType === "combined" || matchType === "hs_code" ) );

				var $hs = el(
					"input",
					{
						type: "text",
						name: "cfwc_rule_hs_code",
						class: "cfwc-rule-field cfwc-hs-code",
						"data-field": "hs_code_pattern",
						value: rule.hs_code_pattern || "",
						placeholder: "HS Code (e.g., 6109* or 61,62)",
					},
					{ width: "100%", display: showHsCode ? "" : "none" }
				);
				if (showHsCode) {
					$hs.attr( "required", "required" );
				}
				$tdProducts.append( $hs );

				var requiredText = "Not required";
				if (matchType === "category") {
					requiredText = "Required *";
				} else if (matchType === "hs_code") {
					requiredText = "Category: Not required<br>HS Code: Required *";
				} else if (matchType === "combined") {
					requiredText = "Both required *";
				}
				$tdProducts.append( buildRequiredIndicator( requiredText ) );
				$tr.append( $tdProducts );

				// Column: Type
				var $tdType = el( "td" );
				$tdType.append( buildTypeSelect( rule.type || "percentage" ) );
				$tr.append( $tdType );

				// Column: Rate/Amount
				var rateField = (rule.type || "percentage") === "percentage" ? "rate" : "amount";
				var rateValue =
				(rule.type || "percentage") === "percentage" ? rule.rate || 0 : rule.amount || 0;

				var $tdRate = el( "td" );
				$tdRate.append(
					el(
						"input",
						{
							type: "number",
							name: "cfwc_rule_rate",
							class: "cfwc-rule-field",
							"data-field": rateField,
							value: rateValue,
							step: "0.01",
							required: "required",
						},
						{ width: "80px" }
					)
				);
				$tdRate.append(
					el(
						"span",
						null,
						{ "font-size": "11px", color: "#999", display: "block", "margin-top": "2px" },
						"Required *"
					)
				);
				$tr.append( $tdRate );

				// Column: Stacking
				var $tdStacking = el( "td" );
				var mode        = rule.stacking_mode || "add";
				$tdStacking.append( buildStackingSelect( mode ) );
				$tdStacking.append( buildStackingHelp( mode ) );
				$tr.append( $tdStacking );

				// Column: Actions
				var $tdActions = el( "td" );
				var $save      = el( "button", { type: "button", class: "button button-primary cfwc-save-rule" }, null, strings.save || "Save" );
				if ( ! isNew) {
					$save.attr( "data-index", index );
				}
				var $cancel = el( "button", { type: "button", class: "button cfwc-cancel-edit" }, null, strings.cancel || "Cancel" );
				$tdActions.append( $save ).append( " " ).append( $cancel );
				$tr.append( $tdActions );

				// Initialize enhanced selects for this row after a tick
				setTimeout(
					function () {
						initCountrySelect( $tr.find( ".cfwc-country-select" ) );
						initCountrySelect( $tr.find( ".cfwc-category-select" ) );
					},
					0
				);

				// Type change handler to toggle data-field and placeholder.
				$tr.find( 'select[name="cfwc_rule_type"]' ).on(
					"change",
					function () {
						var type   = $( this ).val();
						var $input = $tr.find( 'input[name="cfwc_rule_rate"]' );
						if (type === "percentage") {
							$input.attr( "data-field", "rate" ).attr( "placeholder", "%" );
						} else {
							$input.attr( "data-field", "amount" ).attr( "placeholder", currency_symbol );
						}
					}
				);

				return $tr;
			}

			function buildStackingBadge(stackingMode) {
				var mode                 = stackingMode || "add";
				var stackingLabels       = {
					add: strings.stack || "Stack",
					override: strings.override || "Override",
					exclusive: strings.exclusive || "Exclusive",
				};
				var stackingColors       = {
					add: "#46b450",
					override: "#f0ad4e",
					exclusive: "#dc3232",
				};
				var stackingDescriptions = {
					add: strings.stack_desc || "Adds with other matching rules",
					override: strings.override_desc || "Replaces lower priority rules",
					exclusive: strings.exclusive_desc || "Only this rule applies",
				};

				return el(
					"span",
					{
						title: stackingDescriptions[mode],
					},
					{
						display: "inline-block",
						padding: "3px 8px",
						background: stackingColors[mode],
						color: "#fff",
						"border-radius": "3px",
						"font-size": "11px",
						"font-weight": 600,
						"line-height": 1,
					},
					stackingLabels[mode]
				);
			}

			// Store state for edit/add operations.
			var originalRules = null;
			var editingIndex  = null;
			var isAddingNew   = false;

			// Add preset rules (primary action - adds to existing like WooCommerce tax rates).
			$( ".cfwc-add-preset" ).on(
				"click",
				function () {
					var presetId = $( "#cfwc-preset-select" ).val();
					if ( ! presetId) {
						showNotice( strings.select_preset_first || "Please select a preset first.", "error" );
						return;
					}
					applyPreset( presetId, true ); // true = add to existing.
				}
			);

			// Replace all rules with preset (clear and apply).
			$( ".cfwc-replace-preset" ).on(
				"click",
				function () {
					var presetId = $( "#cfwc-preset-select" ).val();
					if ( ! presetId) {
						showNotice( strings.select_preset_first || "Please select a preset first.", "error" );
						return;
					}

					// Confirm replacement since it's destructive.
					if ( ! $( this ).hasClass( "confirm-replace" )) {
						$( this )
						.addClass( "confirm-replace" )
						.text( strings.confirm_replace || "Click again to confirm" )
						.css( "background", "#d63638" );

						// Reset after 5 seconds.
						var $btn = $( this );
						setTimeout(
							function () {
								$btn
								.removeClass( "confirm-replace" )
								.text( strings.replace_all || "Replace All Rules" )
								.css( "background", "" );
							},
							5000
						);
						return;
					}

					// Clear existing rules and apply preset.
					applyPreset( presetId, false ); // false = replace all.
				}
			);

			// Add new rule functionality.
			$( ".cfwc-add-rule" ).on(
				"click",
				function (e) {
					e.preventDefault();

					// Save current state before adding.
					originalRules = JSON.parse( $( "#cfwc_rules" ).val() || "[]" );
					isAddingNew   = true;
					editingIndex  = originalRules.length;

					// Remove "no rules" row if exists.
					$( ".cfwc-rules-table tbody .no-rules" ).remove();

					var newRuleDefaults = {
						label: "",
						priority: 0,
						from_country: "",
						to_country: "",
						match_type: "all",
						category_ids: [],
						hs_code_pattern: "",
						type: "percentage",
						rate: 0,
						amount: 0,
						stacking_mode: "add",
					};

					var $row = buildRuleEditRow( newRuleDefaults, editingIndex, true );
					$( ".cfwc-rules-table tbody" ).append( $row );

					// Scroll to new row.
					$( "html, body" ).animate(
						{
							scrollTop: $( ".cfwc-rules-table tbody tr:last" ).offset().top - 100,
						},
						500
					);

					// Focus on label field.
					$( '.cfwc-rules-table tbody tr:last input[name="cfwc_rule_label"]' ).focus();
				}
			);

			// Edit rule functionality.
			$( document ).on(
				"click",
				".cfwc-edit-rule",
				function (e) {
					e.preventDefault();

					var $button = $( this );
					var $row    = $button.closest( "tr" );
					var index   = $button.data( "index" );

					// Save original state before editing.
					originalRules = JSON.parse( $( "#cfwc_rules" ).val() || "[]" );
					isAddingNew   = false;
					editingIndex  = index;

					var rules = originalRules;
					var rule  = rules[index];

					var $editRow = buildRuleEditRow( rule, index, false );
					$row.replaceWith( $editRow );
				}
			);

			// Save rule functionality.
			$( document ).on(
				"click",
				".cfwc-save-rule",
				function (e) {
					e.preventDefault();

					var $button = $( this );
					var $row    = $button.closest( "tr" );
					var index   = $button.data( "index" );
					var rules   = JSON.parse( $( "#cfwc_rules" ).val() || "[]" );

					// Collect data from fields.
					var ruleData = {};
					$row.find( ".cfwc-rule-field" ).each(
						function () {
							var field = $( this ).data( "field" );
							var value = $( this ).val();

							// Handle different field types.
							if (field === "rate" || field === "amount") {
								value = parseFloat( value ) || 0;
							} else if (field === "priority") {
								value = parseInt( value, 10 ) || 0;
							} else if (field === "category_ids") {
								// For multi-select categories.
								value = $( this ).val() || [];
								if (value.length > 0) {
									value = value.map(
										function (v) {
											return parseInt( v, 10 );
										}
									);
								}
							}

							ruleData[field] = value;
						}
					);

					// Ensure all required fields are present.
					ruleData.taxable   = ruleData.taxable !== undefined ? ruleData.taxable : true;
					ruleData.tax_class = ruleData.tax_class || "";

					// For new rules, ensure amount field exists even if type is percentage.
					if ( ! ruleData.hasOwnProperty( "amount" )) {
						ruleData.amount = 0;
					}
					if ( ! ruleData.hasOwnProperty( "rate" )) {
						ruleData.rate = 0;
					}

					// Update or add rule.
					if (isAddingNew) {
						// For new rules, add to array.
						rules.push( ruleData );
					} else {
						// For existing rules, update at index.
						rules[index] = $.extend( {}, rules[index], ruleData );
					}

					// Update hidden field.
					$( "#cfwc_rules" ).val( JSON.stringify( rules ) );

					// Reset state.
					originalRules = null;
					editingIndex  = null;
					isAddingNew   = false;

					// Update table.
					updateRulesTable( rules );

					// Enable save button.
					enableSaveButton();

					showNotice(
						strings.rule_saved || 'Rule saved. Remember to click "Save changes" to persist.',
						"success"
					);
				}
			);

			// Cancel edit functionality.
			$( document ).on(
				"click",
				".cfwc-cancel-edit",
				function (e) {
					e.preventDefault();

					// Restore original rules if we were editing/adding.
					if (originalRules !== null) {
						$( "#cfwc_rules" ).val( JSON.stringify( originalRules ) );
					}

					// Reset state.
					var rules     = JSON.parse( $( "#cfwc_rules" ).val() || "[]" );
					originalRules = null;
					editingIndex  = null;
					isAddingNew   = false;

					updateRulesTable( rules );
				}
			);

			// Delete rule functionality - Instant delete like WooCommerce tax table.
			var deleteNoticeTimer = null;
			$( document ).on(
				"click",
				".cfwc-delete-rule",
				function (e) {
					e.preventDefault();

					var $button = $( this );
					var index   = $button.data( "index" );
					var rules   = JSON.parse( $( "#cfwc_rules" ).val() || "[]" );

					// Remove rule instantly.
					rules.splice( index, 1 );

					// Update hidden field.
					$( "#cfwc_rules" ).val( JSON.stringify( rules ) );

					// Update table.
					updateRulesTable( rules );

					// Enable save button.
					enableSaveButton();

					// Debounce the notification to avoid multiple overlapping messages.
					clearTimeout( deleteNoticeTimer );
					deleteNoticeTimer = setTimeout(
						function () {
							showNotice(
								strings.rule_deleted || 'Rule deleted successfully. Remember to click "Save changes" to persist.',
								"success"
							);
						},
						200
					);
				}
			);

			// Update rules table.
			function updateRulesTable(rules) {
				var $tbody = $( "#cfwc-rules-tbody" );
				$tbody.empty();

				// Update the "Add to Existing Rules" button text based on whether rules exist.
				var addPresetBtn = $( ".cfwc-add-preset" );
				if (rules.length === 0) {
					// No rules - show "Import Preset Rules".
					addPresetBtn.text( strings.import_preset || "Import Preset Rules" );

					var $no = el( "tr", { class: "no-rules" } );
					$no.append(
						el(
							"td",
							{ colspan: 7 },
							null,
							strings.no_rules ||
							"No rules configured. Use the preset loader above or add rules manually."
						)
					);
					$tbody.append( $no );
					return;
				}

				// Has rules - show "Add to Existing Rules".
				addPresetBtn.text( strings.add_to_existing || "Add to Existing Rules" );

				$.each(
					rules,
					function (index, rule) {
						var $tr = el( "tr" );

						// Label with priority (first column).
						var $tdLabel = el( "td" );
						$tdLabel.append( document.createTextNode( rule.label || "" ) );
						if (rule.priority && rule.priority > 0) {
							var $wrap = el(
								"span",
								null,
								{ color: "#666", "font-size": "11px" },
								null,
								"(" + rule.priority + " "
							);
							var $ico  = el(
								"span",
								{
									class: "dashicons dashicons-info",
									title: "Higher priority rules are checked first (0-100)",
								},
								{ "font-size": "14px", "vertical-align": "middle", cursor: "help" }
							);
							$wrap.append( $ico ).append( ")" );
							$tdLabel.append( " " ).append( $wrap );
						}
						$tr.append( $tdLabel );

						// Countries (From → To).
						var $tdCountries = el( "td" );
						var from         = rule.from_country || "";
						var to           = rule.to_country || "";
						// Handle legacy 'country' field (destination).
						if ( ! from && ! to && rule.country) {
							from = "";
							to   = rule.country;
						}

						if ( ! from && ! to) {
							$tdCountries.append( el( "em", null, null, "All → All" ) );
						} else if ( ! from) {
							$tdCountries.append(
								document.createTextNode( "Any → " + (countries[to] || to) )
							);
						} else if ( ! to) {
							$tdCountries.append(
								document.createTextNode( (countries[from] || from) + " → Any" )
							);
						} else {
							$tdCountries.append(
								document.createTextNode( (countries[from] || from) + " → " + (countries[to] || to) )
							);
						}
						$tr.append( $tdCountries );

						// Products (Categories & HS Code).
						var $tdProducts = el( "td" );
						var matchType   = rule.match_type || "all";
						if (matchType === "all") {
							$tdProducts.append( el( "em", null, null, "All Products" ) );
						} else {
							var hasLine = false;

							// Categories.
							if (rule.category_ids && rule.category_ids.length > 0) {
								var catIds   = Array.isArray( rule.category_ids )
								? rule.category_ids
								: JSON.parse( rule.category_ids || "[]" );
								var catNames = [];
								$.each(
									catIds.slice( 0, 2 ),
									function (i, catId) {
										if (cfwc_admin.categories && cfwc_admin.categories[catId]) {
											catNames.push( cfwc_admin.categories[catId] );
										}
									}
								);
								if (catIds.length > 2) {
									catNames.push( "+" + (catIds.length - 2) + " more" );
								}
								if (catNames.length > 0) {
									var $line1 = el( "span" );
									$line1.append( el( "span", { class: "dashicons dashicons-category" }, { "font-size": "14px" } ) );
									$line1.append( document.createTextNode( " " + catNames.join( ", " ) ) );
									$tdProducts.append( $line1 );
									hasLine = true;
								}
							}

							// HS Code.
							if (rule.hs_code_pattern) {
								if (hasLine) {
									$tdProducts.append( el( "br" ) );
								}
								var $line2 = el( "span" );
								$line2.append( el( "span", { class: "dashicons dashicons-tag" }, { "font-size": "14px" } ) );
								$line2.append( document.createTextNode( " HS: " + rule.hs_code_pattern ) );
								$tdProducts.append( $line2 );
								hasLine = true;
							}

							if ( ! hasLine) {
								$tdProducts.append( el( "em", null, null, "All Products" ) );
							}
						}
						$tr.append( $tdProducts );

						// Type.
						$tr.append(
							el(
								"td",
								null,
								null,
								(rule.type === "percentage" ? (strings.percentage || "Percentage") : (strings.flat || "Flat"))
							)
						);

						// Rate/Amount.
						if (rule.type === "percentage") {
							$tr.append( el( "td", null, null, (rule.rate || 0) + "%" ) );
						} else {
							$tr.append( el( "td", null, null, currency_symbol + (rule.amount || 0) ) );
						}

						// Stacking badge.
						$tr.append( el( "td" ).append( buildStackingBadge( rule.stacking_mode ) ) );

						// Actions.
						var $tdActions = el( "td" );
						$tdActions.append(
							el(
								"button",
								{ type: "button", class: "button cfwc-edit-rule", "data-index": index },
								null,
								strings.edit || "Edit"
							)
						);
						$tdActions.append( " " );
						$tdActions.append(
							el(
								"button",
								{ type: "button", class: "button cfwc-delete-rule", "data-index": index },
								null,
								strings.delete || "Delete"
							)
						);
						$tr.append( $tdActions );

						$tbody.append( $tr );
					}
				);
			}

			// Expose updateRulesTable globally if needed by PHP-injected initial values.
			window.cfwcUpdateRulesTable = updateRulesTable;
		}
	);

	// Quick edit support for HS Code and Country of Origin.
	$( document ).ready(
		function () {
			if (typeof inlineEditPost !== "undefined") {
				// Store the original quick edit function.
				var $wp_inline_edit = inlineEditPost.edit;

				// Override the function.
				inlineEditPost.edit = function (id) {
					// Call the original function.
					$wp_inline_edit.apply( this, arguments );

					// Get the post ID.
					var $post_id = 0;
					if (typeof id == "object") {
						$post_id = parseInt( this.getId( id ) );
					}

					if ($post_id > 0) {
						// Get the edit row.
						var $edit_row = $( "#edit-" + $post_id );

						// Use AJAX to get the current values.
						$.ajax(
							{
								url: ajaxurl,
								type: "POST",
								data: {
									action: "cfwc_get_quick_edit_data",
									product_id: $post_id,
								},
								success: function (response) {
									if (response.success && response.data) {
										// Set HS Code if available.
										if (response.data.hs_code) {
											$( 'input[name="_cfwc_hs_code"]', $edit_row ).val( response.data.hs_code );
										}

										// Set Country of Origin if available.
										if (response.data.country_of_origin) {
											$( 'select[name="_cfwc_country_of_origin"]', $edit_row ).val(
												response.data.country_of_origin
											);
										}
									}
								},
							}
						);
					}
				};
			}
		}
	);
})( jQuery );
