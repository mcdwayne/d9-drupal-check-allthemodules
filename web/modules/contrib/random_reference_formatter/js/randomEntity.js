(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.RandomEntity = {
    attach: function (context, settings) {

      // Replace entity placeholder with a random entity.
      $(".random-entity-placeholder").once('randomEntity').each(function (index) {

        var self = $(this),
          viewMode = self.data("entity-random-viewmode"),
          quantity = self.data("entity-random-quantity"),
          candidates = self.data("entity-random-candidates");

        $.ajax({
          async: true,
          type: 'POST',
          url: drupalSettings.randomEntityCallbackURL,
          data: {
            ids: candidates,
            view_mode: viewMode,
            quantity: quantity
          },
          success: function (data, textStatus, jQxhr) {
            if (data.randomEntities !== undefined && data.randomEntities !== '') {

              var isItemsContainer = !!(self.find('.field__items').length);
              self.removeClass('random-entity-placeholder--hidden');

              if (isItemsContainer) {
                self.find('.field__items').html(data.randomEntities);
              }
              else {
                self.html(data.randomEntities);
              }

            }
          },
          error: function (jqXhr, textStatus, errorThrown) {
          },
          complete: function (data) {
          }
        });

      });

    }
  };

})(jQuery, Drupal, drupalSettings);
