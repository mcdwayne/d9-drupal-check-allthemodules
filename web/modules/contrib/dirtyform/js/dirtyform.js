(function($) {
  Drupal.behaviors.dirtyform = {
    attach: function(context, settings) {

      settings.dirtyform = settings.dirtyform || drupalSettings.dirtyform;

      $.each(settings.dirtyform, function(id, conf) {
        $('form#'+id).once('dirtyform').each(function() {
            $(this).areYouSure(conf);
        });
      });

    }
  };
})(jQuery);
