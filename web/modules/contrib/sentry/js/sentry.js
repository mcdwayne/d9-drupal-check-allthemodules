/**
 * @file
 * Attaches popup and show/hide functionality to disclaimer.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.sentry = {
    attach: function (context, settings) {

      // Show/Hide based on Javascript availability.
      $('.sentryNoScript').hide();

      // Go trough all sentry block instances on this page.
      $.each(drupalSettings.sentry, function (index, value) {

        // Skip popup in case cookie says user already agreed.
        if ($.cookie(index) !== '1') {
          // User did not agreed yet. Show popup.
          $('.block.' + index + ' .sentry__challenge', context).dialog({
            closeOnEscape: false,
            open: function (event, ui) {
              $('.ui-dialog-titlebar-close', ui.dialog | ui).hide();
            },
            resizable: false,
            height: 'auto',
            width: '40%',
            modal: true,
            buttons: {
              'Yes': {
                text: Drupal.t('Yes'),
                click: function () {
                  $(this).dialog('close');
                  var expire = new Date(new Date().getTime() + parseInt(value.max_age) * 1000);
                  $.cookie(index, '1', {expires: expire});
                }
              },
              'No': {
                text: Drupal.t('No'),
                click: function () {
                  $(this).dialog('close');
                  window.location.href = value.redirect;
                }
              }
            }
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
