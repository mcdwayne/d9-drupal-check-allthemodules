/**
 * @file
 * Shell popup form behaviors.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.shellPopup = {

    // This is loaded in the popup only.  The following is to ensure that
    // if the popup is resized, the Shell gets resized too.
    attach: function (context, settings) {
      // Begin by setting the history hight to whatever the popup window's
      // height is.
      var windowHeight = $(window).height();
      var commandHeight = $('.form-item-command').height();
      $('#shell-screen-history').height(windowHeight - commandHeight - 42);

      // If the user resizes the window, adjust the history height
      $(window).bind('resize', function (e) {
        var windowHeight = $(window).height();
        var commandHeight = $('.form-item-command').height();
        $('#shell-screen-history').height(windowHeight - commandHeight - 42);
      });
    }

  };

})(jQuery);
