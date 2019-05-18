/**
 * @file
 * Attaches popup and show/hide functionality to disclaimer_email.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.disclaimer_email = {
    attach: function (context, settings) {

      // Show/Hide based on Javascript availability.
      $('.disclaimer_email__noscript').hide();

      // Go trough all disclaimer_email block instances on this page.
      $.each(drupalSettings.disclaimer_email, function (index, value) {
        // Skip popup in case cookie says user already agreed.
        if ($.cookie(index) !== '1') {
          // User did not agreed yet. Show popup.
          $('.block.' + index + ' .disclaimer_email__challenge', context).dialog({
            closeOnEscape: false,
            open: function (event, ui) {
              $('.ui-dialog-titlebar-close', ui.dialog | ui).hide();
            },
            resizable: false,
            height: 'auto',
            width: '40%',
            modal: true,
          }).removeClass('hidden');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
