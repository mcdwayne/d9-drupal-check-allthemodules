(function ($, Drupal) {
  Drupal.behaviors.searchbox = {
    attach: function (context, settings) {
      jQuery('form.config-single-export-form .form-select').select2();
    }
  }
})(jQuery, Drupal);
