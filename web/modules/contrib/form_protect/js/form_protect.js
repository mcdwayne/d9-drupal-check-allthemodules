/**
 * @file
 * Restores the form original action.
 */
(function ($, settings) {
  Drupal.behaviors.protect = {
    attach: function (context) {
      $.each(settings.formProtect, function (formId, formAction) {
        $('form#' + formId).attr('action', formAction);
      });
    }
  };
})(jQuery, drupalSettings);
