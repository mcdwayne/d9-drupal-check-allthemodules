/**
 * @file
 * Initialize fontIconPicker.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.miconElement = {

    attach: function (context) {
      $('select.form-micon').once().fontIconPicker();
    }
  };

}(jQuery));
