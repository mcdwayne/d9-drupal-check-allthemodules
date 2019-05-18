(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.entityCollectionParticpantBehavior = {
    attach: function (context, settings) {
      $('body').once('entityCollectionParticipantTriggers')
          .on('entityCollectionParticipantRemoval', Drupal.behaviors.entityCollectionParticpantBehavior.removeFromList);
    },
    removeFromList: function (event, entityCollectionBundle, entityCollectionId) {
      $('.js-entity-collection').each(function () {
        var $collection = $(this);

        if ($collection.attr('data-entity-collection-type') !== entityCollectionBundle || $collection.attr('data-entity-collection-id') !== entityCollectionId) {
          return;
        }

        $collection.remove();
      });
    }
  };
})(jQuery, Drupal);