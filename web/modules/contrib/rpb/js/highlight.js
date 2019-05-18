(function ($, Drupal) {
  Drupal.behaviors.rpb = {
    attach: function (context, settings) {
      $(document, context).once('rpbAjaxStopBehavior').each(function () {
        // Wait for all ajax requests to finish
        $(this).ajaxStop(function() {
          // Get the code container.
          var $code = $('#views-live-preview pre', context);
          // Get the format.
          var format = settings.rpb.format;

          // Beautify code.
          if (format === 'xml') {
            $code.text(vkbeautify.xml($code.text(),2));
          }
          else {
            $code.text(vkbeautify.json($code.text(),2));
          }
          // Apply a color schema.
          $code.each(function(i, e) {
            hljs.highlightBlock(e);
          });
        });
      });
    }
  }
})(jQuery, Drupal);
