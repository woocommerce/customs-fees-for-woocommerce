/*!
 * Customs Fees for WooCommerce - Frontend Script
 * Adds tooltip icons and text to matching fee rows on cart/checkout.
 */
(function ($) {
  'use strict';

  function createTooltip() {
    var $tooltip = $('<span>', { class: 'cfwc-tooltip' });
    $('<span>', { class: 'cfwc-tooltip-icon', text: '?' }).appendTo($tooltip);

    var textHtml = (window.cfwc_params && cfwc_params.tooltip_text) ? cfwc_params.tooltip_text : '';
    $('<span>', { class: 'cfwc-tooltip-text' }).html(textHtml).appendTo($tooltip);

    return $tooltip;
  }

  function textMatchesAny(text, labels) {
    if (!labels || !labels.length) {
      return false;
    }
    for (var i = 0; i < labels.length; i++) {
      var label = labels[i];
      if (label && text.indexOf(label) !== -1) {
        return true;
      }
    }
    return false;
  }

  function addTooltips() {
    if (!window.cfwc_params) {
      return;
    }
    var labels = cfwc_params.fee_labels || [];

    // Target common fee label cells:
    // - Checkout fees table: .fee th, .fee td:first-child
    // - Some themes show cart fee labels in subtotal-like rows, keep compatibility for cart-subtotal th.
    var $cells = $('.fee th, .fee td:first-child, .cart-subtotal th');

    $cells.each(function () {
      var $cell = $(this);
      var text = $.trim($cell.text());

      if (!textMatchesAny(text, labels)) {
        return;
      }
      if ($cell.find('.cfwc-tooltip').length) {
        return;
      }
      $cell.append(createTooltip());
    });
  }

  $(document).ready(function () {
    addTooltips();

    // Re-apply after dynamic updates.
    $(document.body).on('updated_checkout updated_cart_totals', function () {
      addTooltips();
    });
  });
})(jQuery);
