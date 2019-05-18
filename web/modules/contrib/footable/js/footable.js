/**
 * @file
 * Javascript file for the FooTable module.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.footable = {
    attach: function (context) {
      $('.footable', context).footable();
    }
  };
}(jQuery));
