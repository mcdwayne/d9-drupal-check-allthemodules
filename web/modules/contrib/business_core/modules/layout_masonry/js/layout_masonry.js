(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.layoutMasonry = {
    attach: function (context) {
      $(context).find('.layout-masonry > div').once('layout-masonry').each(function () {
        $(this).masonry({
        });
      });
    }
  };

}(jQuery, Drupal));
