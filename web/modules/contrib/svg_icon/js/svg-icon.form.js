(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.svg_icon_form = {
    attach: function(context) {
      // Bind the selection handlers individually to each field.
      $('.svg-icon-preview').once('svg-icon').each(function(i, el) {
        var $wrapper = $(el);

        $wrapper.find('.svg-wrapper').click(function(e) {
          self.reset($wrapper);
          self.select_icon($(this));
        });
      });

      // If we have a default then mark it as selected in the UI.
      var $default = $('.svg-default'),
          self = this;
      if ($default.length) {
        $default.each(function(i, el) {
          self.select_icon($(el));
        });
      }
    },

    select_icon: function($icon) {
      $icon.addClass('checked');

      // Copy the selected value into the hidden svg id field.
      $('[name="' + $icon.data('svg-id-selector') + '"]').val($icon.data('svg-id'));
    },

    reset: function($wrapper) {
      $wrapper.find('.checked').removeClass('checked');
    }
  };

})(jQuery, Drupal);
