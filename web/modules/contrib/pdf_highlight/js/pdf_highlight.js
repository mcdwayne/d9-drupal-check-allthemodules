(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.pdfHighlight = {
    attach: function (context, settings) {
      // Highlight full text search in the pdf
      $(context).find('.card__file_viewer').once('pdfHighlight').each(function () {
        // @todo process extra js work here, like custom callback
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
