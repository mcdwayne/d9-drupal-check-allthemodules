/**
 * @file
 * Google crawl errors js.
 */

'use strict';

(function ($) {

  Drupal.behaviors.GoogleCrawlErrorsReportBlock = {
    attach: function (context, settings) {
      $('.toggle-crawl-errors-details').on('click', function (e) {
        $(this).siblings('.crawl-errors-details').toggle();

        // Swap toggle text.
        if ($(this).text() === '+') {
          $(this).text('-');
        }
        else {
          $(this).text('+');
        }
      });
    }
  };

}(jQuery));
