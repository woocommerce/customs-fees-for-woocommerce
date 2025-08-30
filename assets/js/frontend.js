/**
 * Customs Fees for WooCommerce - Frontend JavaScript
 *
 * Handles frontend functionality including tooltips
 *
 * @package CustomsFeesForWooCommerce
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Add tooltip to customs fee labels
    function addCustomsTooltips() {
      // Check if we have tooltip text
      if (typeof cfwc_params === "undefined" || !cfwc_params.tooltip_text) {
        return;
      }

      // For cart and checkout fee rows
      $("tr.fee").each(function () {
        var $label = $(this).find("th, td").first();
        var labelText = $label.text();

        // Check if this is a customs fee and doesn't already have tooltip
        if (
          (labelText.indexOf("Customs") > -1 ||
            labelText.indexOf("Import") > -1 ||
            labelText.indexOf("Duty") > -1) &&
          !$label.find(".cfwc-tooltip").length
        ) {
          // Clean the label text by removing zero-width spaces
          var cleanLabel = labelText.replace(/\u200B/g, "");

          $label.html(
            cleanLabel +
              ' <span class="cfwc-tooltip"><span class="cfwc-tooltip-icon">?</span><span class="cfwc-tooltip-text">' +
              cfwc_params.tooltip_text +
              "</span></span>"
          );
        }
      });
    }

    // Initial load
    addCustomsTooltips();

    // After cart/checkout updates
    $(document.body).on("updated_cart_totals updated_checkout", function () {
      addCustomsTooltips();
    });
  });
})(jQuery);
