/**
 * @file
 * Adjustment ajax UI modification.
 */

(function ($, window, Drupal) {

  var eventResponseTimeoutId;

  Drupal.Ajax.prototype.original_eventResponse = Drupal.Ajax.prototype.eventResponse;
  Drupal.Ajax.prototype.eventResponse = function (element, event) {
    clearTimeout(eventResponseTimeoutId);

    if(this.callback instanceof Array && this.callback[0] === 'Drupal\\commerce_inventory\\Element\\InventoryAdjustment' && this.callback[1] === 'ajaxTableRefresh') {
      // Only run if a number was pressed.
      if (isFinite(event.key)) {
        // Fire the original event handler with a delay.
        eventResponseTimeoutId = setTimeout(function (drupalAjax, element, event) {
          drupalAjax.original_eventResponse.apply(drupalAjax, [element, event]);
        }, 500, this, element, event);
      }
    }
    else {
      // Fire the original event handler immediately
      this.original_eventResponse.apply(this, [element, event]);
    }
  };

})(jQuery, window, Drupal);
