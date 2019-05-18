/**
 * @file
 * Tooltips builder for node view.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tooltipsAutocomplete = {
    attach: function (context, settings) {
      $('.paragraph--type--image-tooltips').find('.field--name-field-tooltips-data').not('.tooltips-processed').each(function() {
        var $this = $(this);
        var tooltisValue = $this.find('input').val();
        if (tooltisValue) {
          var tooltipsData = JSON.parse(tooltisValue);
          var $baseImageContainer = $this.siblings('.field--name-field-tooltip-base-image');
          var $baseImage = $baseImageContainer.find('img');
          var tooltip;
          $.each(tooltipsData, function(index, icon) {
            icon.top = Math.round((icon.top * $baseImage.height()) / $baseImage.attr('height'));
            icon.left = Math.round((icon.left * $baseImage.width()) / $baseImage.attr('width'));
            tooltip = Drupal.theme('imageTooltipIconView', icon);
            $(tooltip).appendTo($baseImageContainer);
          });
        }
        $this.addClass('tooltips-processed');
        Drupal.attachBehaviors($this.get(0));
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
