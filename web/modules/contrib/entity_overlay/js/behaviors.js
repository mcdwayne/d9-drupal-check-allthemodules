(function ($, Drupal) {

  Drupal.behaviors.entityOverlayBehavior = {
    attach: function (context, settings) {

      // Replace paths to the entity by the entity overlay url.
      $(context).find($('.entity-overlay__list-item')).once('entityOverlayBehavior').each(function (key, value) {
        var entityId = $(this).data('entity-overlay-id');
        var entityTypeId = $(this).data('entity-overlay-type-id');
        if(settings.entity_overlay.hasOwnProperty(entityTypeId + '_' + entityId)) {
          var paths = settings.entity_overlay[entityTypeId + '_' + entityId];
          var pathMatchLength = paths['path_match'].length;
          // Search and replace all path matches by the entity overlay url.
          for (var i = 0; i < pathMatchLength; i++) {
            $(this).find('a[href$="'+paths['path_match'][i]+'"]').addClass('use-ajax').attr('href', paths['overlay_url']);
          }
        }
      });

    }

  };

})(jQuery, Drupal);
