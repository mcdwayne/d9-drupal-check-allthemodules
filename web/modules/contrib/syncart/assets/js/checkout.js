/**
 * @file
 * Vue app for checkout.
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    phoneInputMask();
  });

  function phoneInputMask() {
    $('.field--name-field-customer-phone input').inputmask('+7 (999) 999-99-99', {'clearIncomplete': true});
  }

})(this.jQuery);
