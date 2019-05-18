(function ($) {

  'use strict';

  Drupal.behaviors.debugBar = {
    attach: function () {
      var $debugBar = $('#debug-bar');
      $debugBar
        .find('#debug-bar-hide-button')
        .once('debug-bar')
        .click(function (event) {
          $debugBar.toggleClass('debug-bar-hidden');
          event.preventDefault();
          // Save toolbar state.
          var date = new Date();
          date.setTime(date.getTime() + (100 * 24 * 60 * 60 * 1000));
          var isHidden = $debugBar.hasClass('debug-bar-hidden') ? 1 : 0;
          document.cookie = 'debug_bar_hidden=' + isHidden + '; expires=' + date.toGMTString() + '; path=/;';
        });
    }
  };

})(jQuery);
