(function ($, Drupal) {
  Drupal.behaviors.VisualNFileImageFormatterHandler = {
    attach: function (context, settings) {
      // @todo: leave field label wrapper to make quick edit avilable
      var ids = settings.visualnFile.imageFormatterItemsWrapperId;
      $.each(ids, function(index, imageItemsWrapperId){
        // @todo: maybe use .html('') or .hide() instead of .remove()
        // Hide the contents of the original image formatter output which is used for fall-back bahaviour,
        // e.g. if js is disabled original images will be shown.
        // Note: The final gallery (or other type of drawing) is created by drawer plugin
        // and doesn't relate to the current script by any means.
        $('#' + imageItemsWrapperId).once('visualn-formatter-drawer').remove();
      });

    }
  };
})(jQuery, Drupal);
