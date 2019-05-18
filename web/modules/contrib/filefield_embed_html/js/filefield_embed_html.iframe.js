(function($, Drupal) {
  Drupal.behaviors.iFrameResize = {
    attach: function(context, settings) {
      iFrameResize({ autoResize: false }, 'iframe');
    },
  };
})(jQuery, Drupal);
