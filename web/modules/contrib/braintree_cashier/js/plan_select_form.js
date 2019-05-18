(function (Drupal) {

  'use strict';

  /**
   * Clear the coupon code field when a new plan is selected.
   */
  Drupal.behaviors.braintreeCashierPlanSelect = {
    attach: function (context, settings) {
      jQuery('#edit-plan-entity-id').on('change', function () {
        jQuery('#coupon-result').empty();
        jQuery('#coupon-code').val('');
      });
    }
  };

})(Drupal);
