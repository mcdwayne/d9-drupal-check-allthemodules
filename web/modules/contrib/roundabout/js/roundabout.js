/**
 * @file
 * Loads the Roundabout library.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.roundabout = {
    attach: function (context, settings) {
      context = context || document;
      settings = settings || Drupal.settings;
      // Build the view-selector from the view-settings.
      if (settings.roundabout.optionsets) {
        (function ($) {
          $.each(settings.roundabout.optionsets, function (id) {
            var config = this;

            // Prepare JQuery objects for later use.
            var $viewContent = $(id).find('.view-content');

            // Add prev next controls.
            // Todo: add settings form.
            $viewContent.append('<div class="roundabout-controls"><a id="roundabout-prev">' + Drupal.t('prev') + '</a><a id="roundabout-next">' + Drupal.t('next') + '</a></div>');

            $viewContent.roundabout(config);
          });
        })(jQuery);
      }
    }
  };

}(jQuery));
