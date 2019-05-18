/**
 * @file
 * Shell popup link behaviors.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.shellPopupLink = {

    popupCount: 0,

    // This is loaded in all pages, so that when the link to the popup shell
    // is clicked, then the form is opened in a separate browser window.
    attach: function (context, settings) {
      $('a[href$="shell/popup"]').on('click', function (event, ui) {
        var index = Drupal.behaviors.shellPopupLink.popupCount++;
        window.open(Drupal.url('shell/popup'), 'shellPopupWindow' + index, 'status=0,toolbar=0,scrollbars=0,resizable=1,width=800,height=500');
        return false;
      });
    }

  };

})(jQuery);
