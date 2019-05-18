/**
 * @file
 * Percent Bar Field Formatter drawing.
 */

(function ($) {
  Drupal.behaviors.PercentBarFieldFormatter = {
    attach: function (context) {
      // Run progress bar animations.
      $('.PercentBarFieldFormatter').once('PercentBarFieldFormatter').each(function (i, item) {
        var $bar = $(this);
        var $amount = $bar.find('.PercentBarFieldFormatter_amount');
        var $container = $bar.find('.PercentBarFieldFormatter_container');
        var conf = {
          width: $amount.attr('data-width'),
          transition: "width " + drupalSettings.charting[$bar.attr('id')].speed + "s " + drupalSettings.charting[$bar.attr('id')].transition,
          opacity: "1",
          'background-color': drupalSettings.charting[$bar.attr('id')].barcolor,
          'color': drupalSettings.charting[$bar.attr('id')].textcolor
        };
        $amount.css(conf);
        $container.css({
          'background-color': drupalSettings.charting[$bar.attr('id')].backgroundcolor
        });
      });
    }
  };
})(jQuery);
