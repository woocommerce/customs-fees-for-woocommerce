/**
 * Admin JavaScript for Customs Fees for WooCommerce
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Get localized data
    var cfwc_admin = window.cfwc_admin || {};
    var ajaxurl = cfwc_admin.ajax_url || window.ajaxurl;
    var countries = cfwc_admin.countries || {};
    var currency_symbol = cfwc_admin.currency_symbol || "$";
    var presetData = cfwc_admin.templates || {};
    var strings = cfwc_admin.strings || {};

    // No need for tooltip handlers - browser handles title attributes natively

    // Helper function to escape HTML
    function escapeHtml(str) {
      if (!str) return "";
      var div = document.createElement("div");
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }

    // Helper function to enable the WooCommerce save button
    function enableSaveButton() {
      // Mark the form as changed
      $("#cfwc_rules_changed").val("1").trigger("change");

      // Trigger change on the main WooCommerce form
      $("#mainform, form").trigger("change");

      // Force enable all save buttons
      var $saveButtons = $(
        'button[name="save"], input[name="save"], .woocommerce-save-button, .button-primary[type="submit"]'
      );
      $saveButtons.each(function () {
        $(this)
          .prop("disabled", false)
          .removeClass("disabled")
          .removeAttr("disabled")
          .addClass("button-primary")
          .css({
            background: "#2271b1",
            cursor: "pointer",
            opacity: "1",
            "pointer-events": "auto",
          });
      });

      // Pulse animation to draw attention
      $saveButtons
        .first()
        .animate({ opacity: 0.5 }, 200)
        .animate({ opacity: 1 }, 200);
    }

    // Initialize Select2 using WooCommerce's native enhanced select
    function initCountrySelect(selector) {
      // Use WooCommerce's native enhanced select functionality
      if (typeof wc_enhanced_select_params !== "undefined") {
        $(selector)
          .selectWoo({
            minimumResultsForSearch: 10,
            allowClear: true,
            placeholder:
              $(selector).data("placeholder") ||
              wc_enhanced_select_params.i18n_no_matches,
            width: "100%",
          })
          .addClass("enhanced");
      } else if ($.fn.select2) {
        // Fallback to regular select2
        $(selector).select2({
          minimumResultsForSearch: 10,
          allowClear: true,
          placeholder: strings.select_country || "Select a country",
          width: "100%",
        });
      }
    }

    // Show preset description on selection
    $("#cfwc-preset-select").on("change", function () {
      var presetId = $(this).val();
      if (presetId && presetData[presetId]) {
        $("#cfwc-preset-description em").text(presetData[presetId].description);
        $("#cfwc-preset-description").show();
      } else {
        $("#cfwc-preset-description").hide();
      }
    });

    // Handle match type changes to show/hide category and HS code fields
    $(document).on("change", ".cfwc-match-type", function () {
      var matchType = $(this).val();
      var $row = $(this).closest("tr");
      var $categorySelect = $row.find(".cfwc-category-select");
      var $hsCodeInput = $row.find(".cfwc-hs-code");

      // Hide all first
      $categorySelect.hide();
      $hsCodeInput.hide();

      // Show based on match type
      if (matchType === "category" || matchType === "combined") {
        $categorySelect.show();
      }
      if (matchType === "hs_code" || matchType === "combined") {
        $hsCodeInput.show();
      }
    });

    // Handle stacking mode changes to show appropriate help text
    $(document).on("change", ".cfwc-stacking-select", function () {
      var stackingMode = $(this).val();
      var $helpContainer = $(this).siblings(".cfwc-stacking-help");

      // Hide all help texts
      $helpContainer.find("span").hide();

      // Show the relevant help text
      $helpContainer.find(".stacking-help-" + stackingMode).show();
    });

    // Add preset rules (primary action - adds to existing like WooCommerce tax rates)
    $(".cfwc-add-preset").on("click", function () {
      var presetId = $("#cfwc-preset-select").val();
      if (!presetId) {
        showNotice(
          strings.select_preset_first || "Please select a preset first.",
          "error"
        );
        return;
      }

      // No confirmation needed for adding - just like WooCommerce tax rates
      showNotice(strings.adding_preset || "Adding preset rules...", "info");
      applyPreset(presetId, true); // true = add to existing
    });

    // Replace all rules with preset (clear and apply)
    $(".cfwc-replace-preset").on("click", function () {
      var presetId = $("#cfwc-preset-select").val();
      if (!presetId) {
        showNotice(
          strings.select_preset_first || "Please select a preset first.",
          "error"
        );
        return;
      }

      // Confirm replacement since it's destructive
      if (!$(this).hasClass("confirm-replace")) {
        $(this)
          .addClass("confirm-replace")
          .text(strings.confirm_replace || "Click again to confirm")
          .css("background", "#d63638");

        // Reset after 5 seconds
        var $btn = $(this);
        setTimeout(function () {
          $btn
            .removeClass("confirm-replace")
            .text(strings.replace_all || "Replace All Rules")
            .css("background", "");
        }, 5000);
        return;
      }

      // Clear existing rules and apply preset
      showNotice(
        strings.replacing_rules || "Replacing all rules with preset...",
        "info"
      );
      applyPreset(presetId, false); // false = replace all
    });

    // Delete all rules
    $(".cfwc-delete-all").on("click", function () {
      // Check if there are existing rules
      var existingRules = $(".cfwc-rules-table tbody tr").not(
        ".no-rules"
      ).length;
      if (existingRules === 0) {
        showNotice(strings.no_rules_delete || "No rules to delete.", "warning");
        return;
      }

      // Keep confirmation for Delete All (destructive action)
      if ($(this).hasClass("confirm-delete")) {
        // Second click - proceed
        var rules = [];
        $("#cfwc_rules").val(JSON.stringify(rules));
        updateRulesTable(rules);
        enableSaveButton();
        showNotice(
          strings.all_deleted ||
            'All rules deleted. Remember to click "Save changes" to persist.',
          "success"
        );
        $(this)
          .removeClass("confirm-delete")
          .text(strings.delete_all || "Delete All Rules")
          .css("background", "")
          .css("color", "");
      } else {
        // First click - show warning and style as danger
        showNotice(
          strings.delete_warning ||
            "⚠️ This will delete ALL rules. Click again to confirm.",
          "warning"
        );
        $(this)
          .addClass("confirm-delete")
          .text(strings.confirm_delete || "Click to Confirm Delete")
          .css("background", "#d63638")
          .css("color", "#fff");

        // Reset button after 5 seconds (match notification duration)
        var $button = $(this);
        setTimeout(function () {
          $button
            .removeClass("confirm-delete")
            .text(strings.delete_all || "Delete All Rules")
            .css("background", "")
            .css("color", "");
        }, 5000);
      }
    });

    // Apply preset via AJAX
    function applyPreset(presetId, append) {
      $.ajax({
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
            // Update rules in hidden field
            $("#cfwc_rules").val(JSON.stringify(response.data.rules));
            // Dynamically update the table without reload
            updateRulesTable(response.data.rules);
            // Reset preset selector
            $("#cfwc-preset-select").val("");
            $("#cfwc-preset-description").hide();

            // Show preset success message (shorter duration)
            var presetMessage =
              response.data.message ||
              strings.preset_applied ||
              "Preset applied successfully!";

            // Use shorter duration for preset success
            if (window.wp && window.wp.data && window.wp.data.dispatch) {
              // Clear existing notices first
              wp.data.dispatch("core/notices").removeNotices();

              // Show success and save reminder in ONE notice
              var fullMessage =
                presetMessage +
                "\n\n" +
                (strings.save_reminder ||
                  '💾 Remember to click "Save changes" to persist these rules.');

              wp.data
                .dispatch("core/notices")
                .createNotice("success", fullMessage, {
                  type: "snackbar",
                  isDismissible: true,
                  autoDismiss: 5000, // 5 seconds total
                  actions: [
                    {
                      label: "Dismiss",
                      onClick: function () {
                        wp.data.dispatch("core/notices").removeNotices();
                      },
                    },
                  ],
                });
            } else {
              // Fallback for old WordPress
              showNotice(
                presetMessage +
                  " " +
                  (strings.save_reminder ||
                    'Remember to click "Save changes" to persist these rules.'),
                "success"
              );
            }

            // Enable save button using helper function
            enableSaveButton();
          } else {
            showNotice(
              response.data.message ||
                strings.preset_failed ||
                "Failed to apply preset.",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("CFWC Preset Error:", status, error);
          console.error("Response:", xhr.responseText);
          var errorMsg =
            strings.preset_error ||
            "An error occurred while applying the preset.";
          if (xhr.responseText) {
            errorMsg +=
              " " +
              (strings.check_console || "Check browser console for details.");
          }
          showNotice(errorMsg, "error");
        },
      });
    }

    // Show admin notice using WooCommerce snackbar
    var noticeTimeout = null;
    function showNotice(message, type) {
      // Clear any pending notice timeout
      if (noticeTimeout) {
        clearTimeout(noticeTimeout);
      }

      // Use WooCommerce snackbar if available
      if (window.wp && window.wp.data && window.wp.data.dispatch) {
        // Clear ALL existing notices first to prevent stacking
        wp.data.dispatch("core/notices").removeNotices();

        var noticeType = "info";
        if (type === "notice-success" || type === "success") {
          noticeType = "success";
        } else if (type === "notice-error" || type === "error") {
          noticeType = "error";
        } else if (type === "notice-warning" || type === "warning") {
          noticeType = "warning";
        }

        // Use WooCommerce/WordPress snackbar notification
        wp.data.dispatch("core/notices").createNotice(noticeType, message, {
          type: "snackbar",
          isDismissible: true,
          // 5 seconds for all notifications for consistency
          autoDismiss: 5000,
          id: "cfwc-notice-" + Date.now(), // Unique ID to prevent duplicates
        });
      } else {
        // Fallback to basic notice for old WordPress versions
        $(".cfwc-admin-notice").remove();
        var noticeHtml =
          '<div class="cfwc-admin-notice notice is-dismissible ' +
          type +
          '">' +
          "<p>" +
          message +
          "</p></div>";
        $(".cfwc-preset-loader").after(noticeHtml);
        noticeTimeout = setTimeout(function () {
          $(".cfwc-admin-notice").fadeOut(300, function () {
            $(this).remove();
          });
        }, 3000);
      }
    }

    // Build country options
    function getCountryOptions(selectedCountry) {
      var html =
        '<option value="">' +
        (strings.select_country_placeholder || "Select country...") +
        "</option>";
      for (var code in countries) {
        html +=
          '<option value="' +
          code +
          '"' +
          (code === selectedCountry ? " selected" : "") +
          ">" +
          countries[code] +
          "</option>";
      }
      return html;
    }

    // Build origin country options (including special options)
    function getOriginOptions(selectedOrigin) {
      var html =
        '<option value="">' +
        (strings.all_origins || "All Origins") +
        "</option>";
      html +=
        '<option value="EU"' +
        (selectedOrigin === "EU" ? " selected" : "") +
        ">" +
        (strings.eu_countries || "EU Countries") +
        "</option>";
      html +=
        '<optgroup label="' +
        (strings.specific_country || "Specific Country") +
        '">';
      for (var code in countries) {
        html +=
          '<option value="' +
          code +
          '"' +
          (code === selectedOrigin ? " selected" : "") +
          ">" +
          countries[code] +
          "</option>";
      }
      html += "</optgroup>";
      return html;
    }

    // Store state for edit/add operations
    var originalRules = null;
    var editingIndex = null;
    var isAddingNew = false;

    // Add new rule functionality
    $(".cfwc-add-rule").on("click", function (e) {
      e.preventDefault();

      // Save current state before adding
      originalRules = JSON.parse($("#cfwc_rules").val() || "[]");
      isAddingNew = true;
      editingIndex = originalRules.length;

      // Create new row HTML (editable fields)
      var newRowHtml = '<tr class="cfwc-rule-row cfwc-rule-editing">';

      // Label input with priority (first column)
      newRowHtml +=
        "<td>" +
        '<input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="" placeholder="' +
        (strings.fee_label || "Fee label") +
        '" style="width: 100%; margin-bottom: 5px;" />' +
        '<div style="display: flex; align-items: center; gap: 5px;">' +
        '<label style="font-size: 11px; color: #666; margin: 0;">Priority:</label>' +
        '<input type="number" name="cfwc_rule_priority" class="cfwc-rule-field" data-field="priority" value="0" title="Higher priority rules are checked first (0-100)" style="width: 60px;" min="0" max="100" />' +
        '<span class="dashicons dashicons-info" style="font-size: 16px; color: #999; cursor: help;" title="Higher numbers = higher priority. Rules with higher priority are applied first."></span>' +
        "</div>" +
        "</td>";

      // Countries column (From → To)
      newRowHtml += "<td>";
      newRowHtml +=
        '<select name="cfwc_rule_from_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="from_country" data-placeholder="' +
        (strings.from_country || "From (any)") +
        '" style="width: 48%;">';
      newRowHtml +=
        '<option value="">Any Origin</option>' + getCountryOptions("");
      newRowHtml += "</select>";
      newRowHtml += " → ";
      newRowHtml +=
        '<select name="cfwc_rule_to_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="to_country" data-placeholder="' +
        (strings.to_country || "To (any)") +
        '" style="width: 48%;">';
      newRowHtml +=
        '<option value="">Any Destination</option>' + getCountryOptions("");
      newRowHtml += "</select></td>";

      // Products column (Categories & HS Code)
      newRowHtml += "<td>";
      newRowHtml +=
        '<select name="cfwc_rule_match_type" class="cfwc-rule-field cfwc-match-type" data-field="match_type" style="width: 100%; margin-bottom: 5px;">';
      newRowHtml += '<option value="all">All Products</option>';
      newRowHtml += '<option value="category">By Category</option>';
      newRowHtml += '<option value="hs_code">By HS Code</option>';
      newRowHtml += '<option value="combined">Category + HS Code</option>';
      newRowHtml += "</select>";
      // Category selector (hidden by default)
      newRowHtml +=
        '<select name="cfwc_rule_categories" class="cfwc-rule-field cfwc-category-select wc-enhanced-select" data-field="category_ids" multiple="multiple" style="width: 100%; display: none; margin-bottom: 5px;" data-placeholder="Select categories...">';
      if (cfwc_admin.categories) {
        $.each(cfwc_admin.categories, function (id, name) {
          newRowHtml += '<option value="' + id + '">' + name + "</option>";
        });
      }
      newRowHtml += "</select>";
      // HS Code pattern input (hidden by default)
      newRowHtml +=
        '<input type="text" name="cfwc_rule_hs_code" class="cfwc-rule-field cfwc-hs-code" data-field="hs_code_pattern" placeholder="HS Code (e.g., 6109* or 61,62)" style="width: 100%; display: none;" />';
      newRowHtml += "</td>";

      // Type selector
      newRowHtml +=
        '<td><select name="cfwc_rule_type" class="cfwc-rule-field" data-field="type">';
      newRowHtml +=
        '<option value="percentage">' +
        (strings.percentage || "Percentage") +
        "</option>";
      newRowHtml +=
        '<option value="flat">' + (strings.flat || "Flat") + "</option>";
      newRowHtml += "</select></td>";

      // Rate/Amount input
      newRowHtml +=
        '<td><input type="number" name="cfwc_rule_rate" class="cfwc-rule-field" data-field="rate" value="0" step="0.01" style="width: 80px;" /></td>';

      // Stacking mode
      newRowHtml += "<td>";
      newRowHtml +=
        '<select name="cfwc_rule_stacking" class="cfwc-rule-field cfwc-stacking-select" data-field="stacking_mode" style="width: 100%;">';
      newRowHtml += '<option value="add">Stack (Add to other fees)</option>';
      newRowHtml +=
        '<option value="override">Override (Replace lower priority)</option>';
      newRowHtml +=
        '<option value="exclusive">Exclusive (Only this fee)</option>';
      newRowHtml += "</select>";
      newRowHtml +=
        '<div class="cfwc-stacking-help" style="font-size: 11px; color: #666; margin-top: 5px;">';
      newRowHtml +=
        '<span class="stacking-help-add" style="display: block;"><span class="dashicons dashicons-plus-alt" style="color: #46b450; font-size: 14px;"></span> Adds with other matching rules</span>';
      newRowHtml +=
        '<span class="stacking-help-override" style="display: none;"><span class="dashicons dashicons-update" style="color: #f0ad4e; font-size: 14px;"></span> Replaces lower priority rules</span>';
      newRowHtml +=
        '<span class="stacking-help-exclusive" style="display: none;"><span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 14px;"></span> No other rules apply</span>';
      newRowHtml += "</div>";
      newRowHtml += "</td>";

      // Actions
      newRowHtml += "<td>";
      newRowHtml +=
        '<button type="button" class="button button-primary cfwc-save-rule">' +
        (strings.save || "Save") +
        "</button> ";
      newRowHtml +=
        '<button type="button" class="button cfwc-cancel-edit">' +
        (strings.cancel || "Cancel") +
        "</button>";
      newRowHtml += "</td>";

      newRowHtml += "</tr>";

      // Remove "no rules" row if exists
      $(".cfwc-rules-table tbody .no-rules").remove();

      // Add new row to table
      $(".cfwc-rules-table tbody").append(newRowHtml);

      // Initialize Select2 on new selects with delay for proper rendering
      setTimeout(function () {
        initCountrySelect(
          ".cfwc-rules-table tbody tr:last .cfwc-country-select"
        );
        initCountrySelect(
          ".cfwc-rules-table tbody tr:last .cfwc-category-select"
        );
      }, 100);

      // Scroll to new row
      $("html, body").animate(
        {
          scrollTop: $(".cfwc-rules-table tbody tr:last").offset().top - 100,
        },
        500
      );

      // Focus on label field
      $(
        '.cfwc-rules-table tbody tr:last input[name="cfwc_rule_label"]'
      ).focus();

      // Handle type change
      $('.cfwc-rules-table tbody tr:last select[name="cfwc_rule_type"]').on(
        "change",
        function () {
          var $row = $(this).closest("tr");
          var type = $(this).val();

          if (type === "percentage") {
            $row
              .find('input[name="cfwc_rule_rate"]')
              .attr("data-field", "rate")
              .attr("placeholder", "%");
          } else {
            $row
              .find('input[name="cfwc_rule_rate"]')
              .attr("data-field", "amount")
              .attr("placeholder", currency_symbol);
          }
        }
      );
    });

    // Edit rule functionality
    $(document).on("click", ".cfwc-edit-rule", function (e) {
      e.preventDefault();

      var $button = $(this);
      var $row = $button.closest("tr");
      var index = $button.data("index");

      // Save original state before editing
      originalRules = JSON.parse($("#cfwc_rules").val() || "[]");
      isAddingNew = false;
      editingIndex = index;

      var rules = originalRules;
      var rule = rules[index];

      // Create edit row HTML matching the new structure
      var editRowHtml = "";

      // Label input with priority (first column)
      editRowHtml +=
        "<td>" +
        '<input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="' +
        (rule.label || "") +
        '" placeholder="' +
        (strings.fee_label || "Fee label") +
        '" style="width: 100%; margin-bottom: 5px;" />' +
        '<div style="display: flex; align-items: center; gap: 5px;">' +
        '<label style="font-size: 11px; color: #666; margin: 0;">Priority:</label>' +
        '<input type="number" name="cfwc_rule_priority" class="cfwc-rule-field" data-field="priority" value="' +
        (rule.priority || 0) +
        '" title="Higher priority rules are checked first (0-100)" style="width: 60px;" min="0" max="100" />' +
        '<span class="dashicons dashicons-info" style="font-size: 16px; color: #999; cursor: help;" title="Higher numbers = higher priority. Rules with higher priority are applied first."></span>' +
        "</div>" +
        "</td>";

      // Countries column (From → To)
      editRowHtml += "<td>";
      editRowHtml +=
        '<select name="cfwc_rule_from_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="from_country" data-placeholder="' +
        (strings.from_country || "From (any)") +
        '" style="width: 48%;">';
      editRowHtml +=
        '<option value="">Any Origin</option>' +
        getCountryOptions(
          rule.from_country || rule.origin_country || rule.country || ""
        );
      editRowHtml += "</select>";
      editRowHtml += " → ";
      editRowHtml +=
        '<select name="cfwc_rule_to_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="to_country" data-placeholder="' +
        (strings.to_country || "To (any)") +
        '" style="width: 48%;">';
      editRowHtml +=
        '<option value="">Any Destination</option>' +
        getCountryOptions(rule.to_country || rule.country || "");
      editRowHtml += "</select></td>";

      // Products column (Categories & HS Code)
      editRowHtml += "<td>";
      editRowHtml +=
        '<select name="cfwc_rule_match_type" class="cfwc-rule-field cfwc-match-type" data-field="match_type" style="width: 100%; margin-bottom: 5px;">';
      editRowHtml +=
        '<option value="all"' +
        ((rule.match_type || "all") === "all" ? " selected" : "") +
        ">All Products</option>";
      editRowHtml +=
        '<option value="category"' +
        (rule.match_type === "category" ? " selected" : "") +
        ">By Category</option>";
      editRowHtml +=
        '<option value="hs_code"' +
        (rule.match_type === "hs_code" ? " selected" : "") +
        ">By HS Code</option>";
      editRowHtml +=
        '<option value="combined"' +
        (rule.match_type === "combined" ? " selected" : "") +
        ">Category + HS Code</option>";
      editRowHtml += "</select>";

      // Category selector (show/hide based on match_type)
      var showCategories =
        rule.match_type === "category" || rule.match_type === "combined";
      editRowHtml +=
        '<select name="cfwc_rule_categories" class="cfwc-rule-field cfwc-category-select wc-enhanced-select" data-field="category_ids" multiple="multiple" style="width: 100%; margin-bottom: 5px;' +
        (showCategories ? "" : " display: none;") +
        '" data-placeholder="Select categories...">';
      if (cfwc_admin.categories) {
        var selectedCats = rule.category_ids || [];
        $.each(cfwc_admin.categories, function (id, name) {
          var selected = selectedCats.includes(parseInt(id)) ? " selected" : "";
          editRowHtml +=
            '<option value="' + id + '"' + selected + ">" + name + "</option>";
        });
      }
      editRowHtml += "</select>";

      // HS Code pattern input (show/hide based on match_type)
      var showHsCode =
        rule.match_type === "hs_code" || rule.match_type === "combined";
      editRowHtml +=
        '<input type="text" name="cfwc_rule_hs_code" class="cfwc-rule-field cfwc-hs-code" data-field="hs_code_pattern" value="' +
        (rule.hs_code_pattern || "") +
        '" placeholder="HS Code (e.g., 6109* or 61,62)" style="width: 100%;' +
        (showHsCode ? "" : " display: none;") +
        '" />';
      editRowHtml += "</td>";

      // Type selector
      editRowHtml +=
        '<td><select name="cfwc_rule_type" class="cfwc-rule-field" data-field="type">';
      editRowHtml +=
        '<option value="percentage"' +
        (rule.type === "percentage" ? " selected" : "") +
        ">" +
        (strings.percentage || "Percentage") +
        "</option>";
      editRowHtml +=
        '<option value="flat"' +
        (rule.type === "flat" ? " selected" : "") +
        ">" +
        (strings.flat || "Flat") +
        "</option>";
      editRowHtml += "</select></td>";

      // Rate/Amount input
      var rateField = rule.type === "percentage" ? "rate" : "amount";
      var rateValue = rule.type === "percentage" ? rule.rate : rule.amount;
      editRowHtml +=
        '<td><input type="number" name="cfwc_rule_rate" class="cfwc-rule-field" data-field="' +
        rateField +
        '" value="' +
        rateValue +
        '" step="0.01" style="width: 80px;" /></td>';

      // Stacking mode
      editRowHtml += "<td>";
      editRowHtml +=
        '<select name="cfwc_rule_stacking" class="cfwc-rule-field cfwc-stacking-select" data-field="stacking_mode" style="width: 100%;">';
      editRowHtml +=
        '<option value="add"' +
        ((rule.stacking_mode || "add") === "add" ? " selected" : "") +
        ">Stack (Add to other fees)</option>";
      editRowHtml +=
        '<option value="override"' +
        (rule.stacking_mode === "override" ? " selected" : "") +
        ">Override (Replace lower priority)</option>";
      editRowHtml +=
        '<option value="exclusive"' +
        (rule.stacking_mode === "exclusive" ? " selected" : "") +
        ">Exclusive (Only this fee)</option>";
      editRowHtml += "</select>";
      editRowHtml +=
        '<div class="cfwc-stacking-help" style="font-size: 11px; color: #666; margin-top: 5px;">';
      var currentMode = rule.stacking_mode || "add";
      editRowHtml +=
        '<span class="stacking-help-add" style="' +
        (currentMode === "add" ? "display: block;" : "display: none;") +
        '"><span class="dashicons dashicons-plus-alt" style="color: #46b450; font-size: 14px;"></span> Adds with other matching rules</span>';
      editRowHtml +=
        '<span class="stacking-help-override" style="' +
        (currentMode === "override" ? "display: block;" : "display: none;") +
        '"><span class="dashicons dashicons-update" style="color: #f0ad4e; font-size: 14px;"></span> Replaces lower priority rules</span>';
      editRowHtml +=
        '<span class="stacking-help-exclusive" style="' +
        (currentMode === "exclusive" ? "display: block;" : "display: none;") +
        '"><span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 14px;"></span> No other rules apply</span>';
      editRowHtml += "</div>";
      editRowHtml += "</td>";

      // Actions
      editRowHtml += "<td>";
      editRowHtml +=
        '<button type="button" class="button button-primary cfwc-save-rule" data-index="' +
        index +
        '">' +
        (strings.save || "Save") +
        "</button> ";
      editRowHtml +=
        '<button type="button" class="button cfwc-cancel-edit">' +
        (strings.cancel || "Cancel") +
        "</button>";
      editRowHtml += "</td>";

      // Replace row content
      $row.addClass("cfwc-rule-editing").html(editRowHtml);

      // Initialize Select2 on selects
      initCountrySelect($row.find(".cfwc-country-select"));
      initCountrySelect($row.find(".cfwc-category-select"));

      // Handle match type change to show/hide fields
      $row.find(".cfwc-match-type").on("change", function () {
        var matchType = $(this).val();
        var $categorySelect = $row.find(".cfwc-category-select");
        var $hsCodeInput = $row.find(".cfwc-hs-code");

        // Hide all first
        $categorySelect.hide();
        $hsCodeInput.hide();

        // Show based on match type
        if (matchType === "category" || matchType === "combined") {
          $categorySelect.show();
        }
        if (matchType === "hs_code" || matchType === "combined") {
          $hsCodeInput.show();
        }
      });

      // Handle type change
      $row.find('select[name="cfwc_rule_type"]').on("change", function () {
        var type = $(this).val();

        if (type === "percentage") {
          $row
            .find('input[name="cfwc_rule_rate"]')
            .attr("data-field", "rate")
            .attr("placeholder", "%");
        } else {
          $row
            .find('input[name="cfwc_rule_rate"]')
            .attr("data-field", "amount")
            .attr("placeholder", currency_symbol);
        }
      });
    });

    // Save rule functionality
    $(document).on("click", ".cfwc-save-rule", function (e) {
      e.preventDefault();

      var $button = $(this);
      var $row = $button.closest("tr");
      var index = $button.data("index");
      var rules = JSON.parse($("#cfwc_rules").val() || "[]");

      // Collect data from fields
      var ruleData = {};
      $row.find(".cfwc-rule-field").each(function () {
        var field = $(this).data("field");
        var value = $(this).val();

        // Handle different field types
        if (field === "rate" || field === "amount") {
          value = parseFloat(value) || 0;
        } else if (field === "priority") {
          value = parseInt(value) || 0;
        } else if (field === "category_ids") {
          // For multi-select categories
          value = $(this).val() || [];
          if (value.length > 0) {
            value = value.map(function (v) {
              return parseInt(v);
            });
          }
        }

        ruleData[field] = value;
      });

      // Ensure all required fields are present
      ruleData.taxable =
        ruleData.taxable !== undefined ? ruleData.taxable : true;
      ruleData.tax_class = ruleData.tax_class || "";

      // For new rules, ensure amount field exists even if type is percentage
      if (!ruleData.hasOwnProperty("amount")) {
        ruleData.amount = 0;
      }
      if (!ruleData.hasOwnProperty("rate")) {
        ruleData.rate = 0;
      }

      // Update or add rule
      if (isAddingNew) {
        // For new rules, add to array
        rules.push(ruleData);
      } else {
        // For existing rules, update at index
        rules[index] = $.extend({}, rules[index], ruleData);
      }

      // Update hidden field
      $("#cfwc_rules").val(JSON.stringify(rules));

      // Reset state
      originalRules = null;
      editingIndex = null;
      isAddingNew = false;

      // Update table
      updateRulesTable(rules);

      // Enable save button
      enableSaveButton();

      showNotice(
        strings.rule_saved ||
          'Rule saved. Remember to click "Save changes" to persist.',
        "success"
      );
    });

    // Cancel edit functionality
    $(document).on("click", ".cfwc-cancel-edit", function (e) {
      e.preventDefault();

      // Restore original rules if we were editing/adding
      if (originalRules !== null) {
        $("#cfwc_rules").val(JSON.stringify(originalRules));
      }

      // Reset state
      var rules = JSON.parse($("#cfwc_rules").val() || "[]");
      originalRules = null;
      editingIndex = null;
      isAddingNew = false;

      updateRulesTable(rules);
    });

    // Delete rule functionality - Instant delete like WooCommerce tax table
    var deleteNoticeTimer = null;
    $(document).on("click", ".cfwc-delete-rule", function (e) {
      e.preventDefault();

      var $button = $(this);
      var index = $button.data("index");
      var rules = JSON.parse($("#cfwc_rules").val() || "[]");

      // Remove rule instantly
      rules.splice(index, 1);

      // Update hidden field
      $("#cfwc_rules").val(JSON.stringify(rules));

      // Update table
      updateRulesTable(rules);

      // Enable save button
      enableSaveButton();

      // Debounce the notification to avoid multiple overlapping messages
      clearTimeout(deleteNoticeTimer);
      deleteNoticeTimer = setTimeout(function () {
        showNotice(
          strings.rule_deleted ||
            'Rule deleted successfully. Remember to click "Save changes" to persist.',
          "success"
        );
      }, 200);
    });

    // Update rules table
    function updateRulesTable(rules) {
      var tbody = $("#cfwc-rules-tbody");
      tbody.empty();

      // Update the "Add to Existing Rules" button text based on whether rules exist
      var addPresetBtn = $(".cfwc-add-preset");
      if (rules.length === 0) {
        // No rules - show "Import Preset Rules"
        addPresetBtn.text(strings.import_preset || "Import Preset Rules");
        tbody.append(
          '<tr class="no-rules"><td colspan="7">' +
            (strings.no_rules ||
              "No rules configured. Use the preset loader above or add rules manually.") +
            "</td></tr>"
        );
      } else {
        // Has rules - show "Add to Existing Rules"
        addPresetBtn.text(strings.add_to_existing || "Add to Existing Rules");
        $.each(rules, function (index, rule) {
          var row = "<tr>";

          // Label with priority (first column)
          row += "<td>" + escapeHtml(rule.label || "");
          if (rule.priority && rule.priority > 0) {
            row +=
              ' <span style="color: #666; font-size: 11px;">(' +
              rule.priority +
              ' <span class="dashicons dashicons-info" style="font-size: 14px; vertical-align: middle; cursor: help;" ' +
              'title="Higher priority rules are checked first (0-100)"></span>)</span>';
          }
          row += "</td>";

          // Countries (From → To)
          // Handle both old and new formats
          var from = rule.from_country || "";
          var to = rule.to_country || "";

          // If old format (only 'country' field exists), it's the destination
          if (!from && !to && rule.country) {
            from = ""; // Any origin
            to = rule.country; // The 'country' field is the destination in old format
          }

          if (!from && !to) {
            row += "<td><em>All → All</em></td>";
          } else if (!from) {
            var toName = countries[to] || to;
            row += "<td>Any → " + toName + "</td>";
          } else if (!to) {
            var fromName = countries[from] || from;
            row += "<td>" + fromName + " → Any</td>";
          } else {
            var fromName = countries[from] || from;
            var toName = countries[to] || to;
            row += "<td>" + fromName + " → " + toName + "</td>";
          }

          // Products (Categories & HS Code)
          row += "<td>";
          var matchType = rule.match_type || "all";
          if (matchType === "all") {
            row += "<em>All Products</em>";
          } else {
            var criteria = [];

            // Categories
            if (rule.category_ids && rule.category_ids.length > 0) {
              var catNames = [];
              var catIds = Array.isArray(rule.category_ids)
                ? rule.category_ids
                : JSON.parse(rule.category_ids || "[]");
              $.each(catIds.slice(0, 2), function (i, catId) {
                if (cfwc_admin.categories && cfwc_admin.categories[catId]) {
                  catNames.push(cfwc_admin.categories[catId]);
                }
              });
              if (catIds.length > 2) {
                catNames.push("+" + (catIds.length - 2) + " more");
              }
              if (catNames.length > 0) {
                criteria.push(
                  '<span class="dashicons dashicons-category" style="font-size: 14px;"></span> ' +
                    catNames.join(", ")
                );
              }
            }

            // HS Code
            if (rule.hs_code_pattern) {
              criteria.push(
                '<span class="dashicons dashicons-tag" style="font-size: 14px;"></span> HS: ' +
                  rule.hs_code_pattern
              );
            }

            row +=
              criteria.length > 0
                ? criteria.join("<br>")
                : "<em>All Products</em>";
          }
          row += "</td>";

          // Type
          row +=
            "<td>" +
            (rule.type === "percentage"
              ? strings.percentage || "Percentage"
              : strings.flat || "Flat") +
            "</td>";

          // Rate/Amount
          if (rule.type === "percentage") {
            row += "<td>" + (rule.rate || 0) + "%</td>";
          } else {
            row += "<td>" + currency_symbol + (rule.amount || 0) + "</td>";
          }

          // Stacking mode
          var stackingMode = rule.stacking_mode || "add";
          var stackingIcons = {
            add: '<span class="dashicons dashicons-plus-alt" style="color: #46b450;"></span>',
            override:
              '<span class="dashicons dashicons-update" style="color: #f0ad4e;"></span>',
            exclusive:
              '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>',
          };
          var stackingLabels = {
            add: "Stack",
            override: "Override",
            exclusive: "Exclusive",
          };
          var stackingDescriptions = {
            add: "Adds with other matching rules",
            override: "Replaces lower priority rules",
            exclusive: "Only this rule applies",
          };
          // Always default to 'add' if not set
          var iconToShow = stackingIcons[stackingMode] || stackingIcons.add;
          var labelToShow = stackingLabels[stackingMode] || stackingLabels.add;
          var descToShow =
            stackingDescriptions[stackingMode] || stackingDescriptions.add;
          row +=
            "<td>" +
            iconToShow +
            ' <span title="' +
            descToShow +
            '">' +
            labelToShow +
            "</span></td>";

          // Actions
          row += "<td>";
          row +=
            '<button type="button" class="button cfwc-edit-rule" data-index="' +
            index +
            '">' +
            (strings.edit || "Edit") +
            "</button> ";
          row +=
            '<button type="button" class="button cfwc-delete-rule" data-index="' +
            index +
            '">' +
            (strings.delete || "Delete") +
            "</button>";
          row += "</td>";

          row += "</tr>";
          tbody.append(row);
        });
      }
    }
  });
  // Quick edit support for HS Code and Country of Origin.
  $(document).ready(function () {
    if (typeof inlineEditPost !== "undefined") {
      // Store the original quick edit function.
      var $wp_inline_edit = inlineEditPost.edit;

      // Override the function.
      inlineEditPost.edit = function (id) {
        // Call the original function.
        $wp_inline_edit.apply(this, arguments);

        // Get the post ID.
        var $post_id = 0;
        if (typeof id == "object") {
          $post_id = parseInt(this.getId(id));
        }

        if ($post_id > 0) {
          // Get the edit row.
          var $edit_row = $("#edit-" + $post_id);

          // Use AJAX to get the current values.
          $.ajax({
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
                  $('input[name="_cfwc_hs_code"]', $edit_row).val(
                    response.data.hs_code
                  );
                }

                // Set Country of Origin if available.
                if (response.data.country_of_origin) {
                  $('select[name="_cfwc_country_of_origin"]', $edit_row).val(
                    response.data.country_of_origin
                  );
                }
              }
            },
          });
        }
      };
    }
  });
})(jQuery);
