(function ($, Drupal, window) {

  // Unveil behavior code
  Drupal.behaviors.Unveil = {
    attach: function(context, drupalSettings) {
      // Load Unveil, and set the distance to unveil at
      $('img').unveil(drupalSettings.unveil.unveil_preprocess_image_unveiled);
    }
  }

}(jQuery, Drupal, window));
