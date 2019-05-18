(function($, Drupal) {
  Drupal.behaviors.mediaelement = {
    attach(context, settings) {
      $('.mediaelementjs', context)
        .once('mediaelement')
        .each(function() {
          $(this).mediaelementplayer(settings.mediaelement);
        });

      // Attach player to other elements if MediaElement.js is set to global.
      if (settings.mediaelement.attachSitewide !== undefined) {
        $('audio,video', context)
          .once('mediaelement')
          .each(function() {
            $(this).mediaelementplayer(settings.mediaelement);
          });
      }
    },
  };
})(jQuery, Drupal);
