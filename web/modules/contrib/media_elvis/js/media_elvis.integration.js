/**
 * @file media_elvis.results.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to  widget.
   */
  Drupal.behaviors.MediaElvisResults = {
    attach: function (context) {

      $('.elvis-search-results').prepend('<div class="elvis-grid-sizer"></div><div class="elvis-gutter-sizer"></div>').once();
      $('.elvis-search-results').imagesLoaded(function () {
        $('.elvis-search-results').masonry({
          columnWidth: '.elvis-grid-sizer',
          gutter: '.elvis-gutter-sizer',
          itemSelector: '.elvis-grid-item',
          percentPosition: true,
          isFitWidth:true
        });
      });

      $('.elvis-grid-item .form-type-checkbox input:checked').closest('.elvis-grid-item').addClass('checked');

      $('.elvis-grid-item').once('bind-click-event').click(function () {
        var input = $(this).find('.form-type-checkbox input');
        input.prop('checked', !input.prop('checked'));
        if (input.prop('checked')) {
          $(this).addClass('checked');
        }
        else {
          $(this).removeClass('checked');
        }
      });
    }
  };

}(jQuery, Drupal));
