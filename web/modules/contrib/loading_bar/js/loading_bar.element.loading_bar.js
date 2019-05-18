/**
 * @file
 * Loading bar element animation functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach the loading bar function to animate.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.loadingBarAnimate = {
    attach: function (context) {
      // Add a random value for each loading bar.
      $('.ldBar.loading-bar-demo', context).each(function () {
        var $loading_bar = $(this).get(0);
        var ld_bar = new ldBar($loading_bar);
        ld_bar.set(0);

        // @TODO Use requestAnimationFrame.
        setInterval(function() {
          ld_bar.set(Math.round(Math.random() * 100));
        }, 1500);
      });
    }
  };

})(jQuery, Drupal);
