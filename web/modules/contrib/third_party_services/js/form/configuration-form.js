/**
 * @file
 * Third-party services configuration from handler.
 */

(function ($, settings, config) {
  'use strict';

  var $window = $(window);
  var updateFormState = function ($context) {
    var $form = $context.find('form.third_party_services__configuration_form');

    if ($form.length) {
      // Update checkboxes only for anonymous users. For authorized ones
      // it is not needed since settings are stored in database for them.
      if (!settings.user.uid) {
        for (var i = 0; i < config.allowed.length; i++) {
          $form.find('[id*="' + config.allowed[i] + '"]').prop('checked', true);
        }
      }

      $window.trigger('third-party-services--form-update', [$form, $context, config]);
    }

    return $form;
  };

  updateFormState($('body'));

  /**
   * @param {Event} event
   * @param {Drupal.dialog} dialog
   * @param {jQuery} $element
   * @param {Object} dialogSettings
   */
  $window.on('dialog:aftercreate', function (dialogEvent, dialog, $dialog, dialogSettings) {
    updateFormState($dialog.closest('.ui-dialog')).find('.button-cancel').on('click', function (event) {
      event.preventDefault();
      dialog.close();
    });
  });
})(window.jQuery, window.drupalSettings, window.thirdPartyServices);
