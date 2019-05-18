/**
 * @file
 * Initialize history.back logic.
 */

(function ($, Drupal, window) {

  'use strict';

  Drupal.behaviors.navigationBlocksBackButton = {

    attach: function (context) {
      $('a.js-history-back').once('navigationBlockHistoryBack').each(function () {
        $(this).on('click', function (event) {
          window.history.back(-1);
          event.preventDefault();
        });
      });
    }
  };

}(jQuery, Drupal, window));
