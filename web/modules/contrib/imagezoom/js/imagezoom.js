(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Initialize image zoom functionality.
   */
  Drupal.behaviors.imagezoom = {
    attach: function (context, drupalSettings) {
      $('.imagezoom-image', context).once('imagezoom').each(function () {
        $(this).ezPlus(drupalSettings.imagezoom);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
