/**
 * @file shariff.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Register behaviours related to Shariff integration.
   */
  Drupal.behaviors.shariffIntegration = {
    attach: function (context) {
      // Stop click event propagation outside Shariff block.
      $(context).find('.shariff').once('shariff-stop-event-propagation').click(
        function (event) {
          event.stopPropagation();
        }
      );
    }
  };

}(jQuery, Drupal));
