(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.algolia_places = {
    attach: function (context, settings) {

      var placesAutocomplete = places({
        container: document.querySelector(drupalSettings.algolia_places.querySelector)
      });

    }
  };

})(jQuery, Drupal, drupalSettings);