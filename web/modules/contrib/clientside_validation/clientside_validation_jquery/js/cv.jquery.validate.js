/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */
(function ($, Drupal, drupalSettings) {

  /**
   * Attaches jQuery validate behavoir to forms.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches the outline behavior to the right context.
   */
  Drupal.behaviors.cvJqueryValidate = {
    attach: function (context) {
      if (typeof drupalSettings.cvJqueryValidateOptions === 'undefined') {
        drupalSettings.cvJqueryValidateOptions = {};
      }

      // Allow all modules to update the validate options.
      // Example of how to do this is shown below.
      $(document).trigger('cv-jquery-validate-options-update', drupalSettings.cvJqueryValidateOptions);

      $(context).find('form').each(function() {
        $(this).validate(drupalSettings.cvJqueryValidateOptions);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
