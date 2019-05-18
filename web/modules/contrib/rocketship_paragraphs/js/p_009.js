/**
 * Rocketship UI JS
 *
 * contains: triggers for functions
 * Functions themselves are split off and grouped below each behavior
 *
 * Drupal behaviors:
 *
 * Means the JS is loaded when page is first loaded
 * + during AJAX requests (for newly added content)
 * use jQuery's "once" to avoid processing the same element multiple times
 * http: *api.jquery.com/one/
 * use the "context" param to limit scope, by default this will return document
 * use the "settings" param to get stuff set via the theme hooks and such.
 *
 *
 * Avoid multiple triggers by using jQuery Once
 *
 * EXAMPLE 1:
 *
 * $('.some-link', context).once('js-once-my-behavior').click(function () {
 *   // Code here will only be applied once
 * });
 *
 * EXAMPLE 2:
 *
 * $('.some-element', context).once('js-once-my-behavior').each(function () {
 *   // The following click-binding will only be applied once
 * * });
 */

(function ($, Drupal, window, document) {

  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') { window.rocketshipUI = {}; }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////

  // set up an array to save listeners for masonry grid for each instance
  self.magicGrids = [];

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_p009 = {
    attach: function (context, settings) {

      var paragraph = $('.paragraph--type-p-009'),
        loadMoreParagraph = $('.paragraph.has--load-more'),
        paragraphMasonry = $('.paragraph--view-mode-p-009-masonry');

      // check for masonry layout
      if (paragraph.length) self.masonry(paragraph, context);

      // add load more functionality
      if (paragraph.length) self.loadMoreFieldItems(paragraph, context);

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * Make a horizontal Masonry layout
   *
   * Uses a library 'Magic Grid', accessed via a CDN
   * https://github.com/e-oj/Magic-Grid
   *
   * @param paragraph
   * @param loadMore
   * @param context
   */
  self.masonry = function(paragraph, context) {

    var index = 0;

    function makeMasonry() {
      self.magicGrids[index] = new MagicGrid({
        container: '.field--name-field-p-images-unlimited .field__items',
        animate: true,
        gutter: 0,
        static: true
      });

      self.magicGrids[index].listen();
    }

    // check all photogallery paragraphs to see if they are in masonry view mode
    paragraph.once('js-p009-masonryCheck').each(function (i) {

      var paragraph = $(this);

      index = i;

      // set and save the listener in an array
      if (paragraph.hasClass('paragraph--view-mode-p-009-masonry')) {

        // wait for images to load

        self.imgLoaded(paragraph, makeMasonry);

      } else {
        self.magicGrids[index] = null;
      }

    });

  };


  /**
   * Hide everything but the limited amount of field items
   * show/hide on click of a 'load more' field
   *
   * NOTE: we reload the masonry items when showing more items
   *
   */
  self.loadMoreFieldItems = function(paragraph, context) {

    // by default, all field items > the limit should by hidden using CSS
    // when there is a load-more button

    paragraph.once('js-p009-loadMoreCheck').each(function (i) {

      var paragraph = $(this);

      var loadMoreButton = paragraph.find('.field--name-field-p-load-more', context);

      if (loadMoreButton.length && paragraph.hasClass('has--load-more')) {

        loadMoreButton.once('js-load-more').on('click', function (e) {

          var itemLimit = paragraph.data('limit');

          // loop the field items
          //
          // remove visibility class if they have one
          // add class if they don't

          if (paragraph.hasClass('has--visible-items')) {
            paragraph.removeClass('has--visible-items');
          } else {
            paragraph.addClass('has--visible-items');
          }

          $('.field__item', paragraph).each(function (index) {

            var item = $(this);

            // remove the classes
            if (index > parseInt(itemLimit - 1)) {

              if (item.hasClass('is--visible')) {
                $(this).removeClass('is--visible');
              } else {
                $(this).addClass('is--visible');
              }
            }
          });

          // if in Masonry mode, retrigger the magic so the images reflow with the newly visible images in it
          if (paragraph.hasClass('paragraph--view-mode-p-009-masonry')) {

            // reposition items
            self.magicGrids[i].positionItems();
          }

          e.preventDefault();

        });
      }

    });

  };

})(jQuery, Drupal, window, document);
