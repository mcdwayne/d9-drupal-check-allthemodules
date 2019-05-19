Drupal.behaviors.touchtouch = {
  attach: function (context, settings) {
    jQuery(function ($) {
      if ($('.placeholder').length) {
        // Reset when after AJAX-call - https://github.com/martinaglv/touchTouch/issues/12#issuecomment-19823916
        $('.placeholder').remove();
      }

      // Call the plugin
      $('.touchtouch a, a.touchtouch').touchTouch();
    });
  }
};