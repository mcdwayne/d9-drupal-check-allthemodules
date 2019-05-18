(function ($) {
  'use strict';
  Drupal.behaviors.high_tax_terms = {
    attach: function (context, settings) {
      $('.ttf_replace').hover(function () {
        $(this).find('.ttf_description').show('fast');
      }, function () {
        $(this).find('.ttf_description').hide('fast');
      });
      $('.ttf_description').hover(function () {
        $(this).hide();
      });
    }
  };
}(jQuery));
