/**
 * @file
 * Attaches popup and show/hide functionality to disclaimer.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.disclaimer = {
    attach: function (context, settings) {

      // Show/Hide based on Javascript availability.
      $('.disclaimer__noscript').hide();

      // Go trough all disclaimer block instances on this page.
      $.each(drupalSettings.disclaimer, function (index, value) {

        // Skip popup in case cookie says user already agreed.
        if ($.cookie(index) !== '1') {
          // User did not agreed yet. Show popup.
          $('.block.' + index + ' .disclaimer__challenge', context).dialog({
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
                text: value.agree,
                click: function () {
                  $(this).dialog('close');
                  var expire = new Date(new Date().getTime() + parseInt(value.max_age) * 1000);
                  $.cookie(index, '1', {expires: expire});
                }
              },
              'No': {
                text: value.disagree,
                click: function () {
                  $(this).dialog('close');
                  window.location.href = value.redirect;
                }
              }
            }
          }).removeClass('hidden');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
