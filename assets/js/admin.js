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

        // Reset button after 7 seconds (match warning notification duration)
        var $button = $(this);
        setTimeout(function () {
          $button
            .removeClass("confirm-delete")
            .text(strings.delete_all || "Delete All Rules")
            .css("background", "")
            .css("color", "");
        }, 7000);
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
              wp.data
                .dispatch("core/notices")
                .createNotice("success", presetMessage, {
                  type: "snackbar",
                  isDismissible: true,
                  autoDismiss: 3000, // 3 seconds for preset success
                });

              // Show save reminder after a delay
              setTimeout(function () {
                var saveMessage =
                  strings.save_reminder ||
                  '💾 Remember to click "Save changes" to persist these rules.';
                wp.data
                  .dispatch("core/notices")
                  .createNotice("info", saveMessage, {
                    type: "snackbar",
                    isDismissible: true,
                    autoDismiss: 7000, // 7 seconds for save reminder
                  });
              }, 2500); // Show save reminder after 2.5 seconds
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
    function showNotice(message, type) {
      // Use WooCommerce snackbar if available
      if (window.wp && window.wp.data && window.wp.data.dispatch) {
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
          // 5 seconds for success, 7 seconds for errors/warnings
          autoDismiss: noticeType === "success" ? 5000 : 7000,
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
        setTimeout(function () {
          $(".cfwc-admin-notice").fadeOut(300, function () {
            $(this).remove();
          });
        }, 5000);
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

      // Label input (first column)
      newRowHtml +=
        '<td><input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="" placeholder="' +
        (strings.fee_label || "Fee label") +
        '" style="width: 100%;" /></td>';

      // Destination country selector - use wc-enhanced-select class
      newRowHtml +=
        '<td><select name="cfwc_rule_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="country" data-placeholder="' +
        (strings.choose_country || "Choose a country...") +
        '">';
      newRowHtml += getCountryOptions("");
      newRowHtml += "</select></td>";

      // Origin country selector - use wc-enhanced-select class
      newRowHtml +=
        '<td><select name="cfwc_rule_origin" class="cfwc-rule-field cfwc-origin-select wc-enhanced-select" data-field="origin_country" data-placeholder="' +
        (strings.choose_origin || "Choose origin...") +
        '">';
      newRowHtml += getOriginOptions("");
      newRowHtml += "</select></td>";

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

      // Initialize Select2 on new selects
      initCountrySelect(".cfwc-rules-table tbody tr:last .cfwc-country-select");
      initCountrySelect(".cfwc-rules-table tbody tr:last .cfwc-origin-select");

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

      // Create edit row HTML
      var editRowHtml = "";

      // Label input (first column)
      editRowHtml +=
        '<td><input type="text" name="cfwc_rule_label" class="cfwc-rule-field" data-field="label" value="' +
        (rule.label || "") +
        '" placeholder="' +
        (strings.fee_label || "Fee label") +
        '" style="width: 100%;" /></td>';

      // Destination country selector - use wc-enhanced-select class
      editRowHtml +=
        '<td><select name="cfwc_rule_country" class="cfwc-rule-field cfwc-country-select wc-enhanced-select" data-field="country" data-placeholder="' +
        (strings.choose_country || "Choose a country...") +
        '">';
      editRowHtml += getCountryOptions(rule.country);
      editRowHtml += "</select></td>";

      // Origin country selector - use wc-enhanced-select class
      editRowHtml +=
        '<td><select name="cfwc_rule_origin" class="cfwc-rule-field cfwc-origin-select wc-enhanced-select" data-field="origin_country" data-placeholder="' +
        (strings.choose_origin || "Choose origin...") +
        '">';
      editRowHtml += getOriginOptions(rule.origin_country || "");
      editRowHtml += "</select></td>";

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
      initCountrySelect($row.find(".cfwc-origin-select"));

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

        if (field === "rate" || field === "amount") {
          value = parseFloat(value) || 0;
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

      // Show success message for deletion
      showNotice(
        strings.rule_deleted ||
          'Rule deleted. Remember to click "Save changes" to persist.',
        "success"
      );
    });

    // Update rules table
    function updateRulesTable(rules) {
      var tbody = $("#cfwc-rules-tbody");
      tbody.empty();

      if (rules.length === 0) {
        tbody.append(
          '<tr class="no-rules"><td colspan="6">' +
            (strings.no_rules ||
              "No rules configured. Use the preset loader above or add rules manually.") +
            "</td></tr>"
        );
      } else {
        $.each(rules, function (index, rule) {
          var row = "<tr>";

          // Label (first column)
          row += "<td>" + (rule.label || "") + "</td>";

          // Destination country
          row +=
            "<td>" +
            (countries[rule.country] ||
              rule.country ||
              strings.not_set ||
              "Not set") +
            "</td>";

          // Origin country
          var originText = "";
          if (!rule.origin_country || rule.origin_country === "") {
            originText = strings.all_origins || "All Origins";
          } else if (rule.origin_country === "EU") {
            originText = strings.eu_countries || "EU Countries";
          } else {
            originText = countries[rule.origin_country] || rule.origin_country;
          }
          row += "<td>" + originText + "</td>";

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
})(jQuery);
