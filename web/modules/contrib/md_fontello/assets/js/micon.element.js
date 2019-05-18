/**
 * @file
 * Initialize fontIconPicker.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.mdiconElement = {

    attach: function (context) {
      $('select.form-md-icon').once().fontIconPicker();
    }
  };

}(jQuery));
