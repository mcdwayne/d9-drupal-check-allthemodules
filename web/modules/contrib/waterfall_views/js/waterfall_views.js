(function ($) {
  /**
   * Attached JS to views Waterfall
   */
  Drupal.behaviors.initWaterFall = {
    attach: function (context, settings) {
      $('#waterfall_views').NewWaterfall();
    }
  };

})(jQuery);