/**
 * @file
 * Popup Onload js file.
 */

(function ($, window, Drupal) {
  'use strict';

  /**
   * Attaches the Popup Onload behaviour.
   */
  Drupal.behaviors.popupOnload = {
    attach: function (context, settings) {
      jQuery.ajax({url: "/popup_onload/get_popup", success: function(result) {
        var popupSettings = JSON.parse(result);
        if (popupSettings) {
          setTimeout(function () {
            var $previewDialog = $('<div />').html(popupSettings.html).appendTo('body');
            Drupal.dialog($previewDialog, popupSettings).showModal();
          }, popupSettings.delay);
        }
      }});
    }
  };

})(jQuery, window, Drupal);
