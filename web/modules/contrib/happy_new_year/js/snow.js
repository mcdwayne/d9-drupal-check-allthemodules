(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.snow = {
    attach: function (context, settings) {

      snowStorm.snowColor = drupalSettings.happy_new_year.snowcolor;

    }
  };

})(jQuery, Drupal);
