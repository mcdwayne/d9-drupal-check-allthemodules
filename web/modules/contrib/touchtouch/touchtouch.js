(function ($) {
  Drupal.behaviors.touchTouch = {
    attach: function (context, settings) {
      $('.touchtouch').touchTouch();
    }
  }
})(jQuery);