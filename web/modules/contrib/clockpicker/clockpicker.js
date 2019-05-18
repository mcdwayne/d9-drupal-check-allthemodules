/**
 * @file
 * Javascript for Clockpicker.
 */

(function ($) {
  Drupal.behaviors.clockpicker = {
    attach: function(context, settings) {
      $('.clockpicker').clockpicker({
        donetext: 'Done',
        autoclose: 1
      });
    }
  };
})(jQuery);
