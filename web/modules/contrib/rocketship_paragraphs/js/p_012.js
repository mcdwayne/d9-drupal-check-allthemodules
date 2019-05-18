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


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_p012 = {
    attach: function (context, settings) {

      var guidanceBox = $('.paragraph--type-p-012-child', context);
      if (guidanceBox.length) self.guidanceBoxAnimate(guidanceBox);

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /*
   *
   * Open/close guide box text
   * but only if teaser or teaser-image view modes
   */
  self.guidanceBoxAnimate = function(box) {

    box.once('js-once-faq-collapsable').each(function () {

      var box = $(this);

      // check for view mode classes
      // if guidance mode 1 (title visible only)

      if (box.hasClass('p__child--view-mode--title') || box.hasClass('p__child--view-mode--image')) {

        var  overlay = box.find('.p-012-child--text'),
            paddingTop = parseInt(overlay.css('paddingTop')),
            title = box.find('.field--name-field-p-title'),
            titleHeight = title.outerHeight(true);

        // make the overlay stick out by height of title

        self.guidanceBoxPosition(overlay, title);

        // recalculate on window resize
        // check if our helperfunction for an optimized resize exists
        if (typeof self.optimizedResize === "function") {
          self.optimizedResize().add(function() {
            self.guidanceBoxPosition(overlay, title);
          });
        // otherwise fall back on the normal window resize
        } else {
          $(window).on('resize', function() {
            //
            self.guidanceBoxPosition(overlay, title);
          });
        }

        // change happens on hover
        // no need to bother with touch-specific events,
        // because that opens a link so they won't see the overlay effect anyway

        box.on('mouseenter', function(e) {
          overlay.css({top: '0'});
        });

        box.on('mouseleave', function(e) {
          self.guidanceBoxPosition(overlay, title);
        });

      }
    });

  };

  // make the overlay stick out by height of title

  self.guidanceBoxPosition = function(overlay, title) {
    var paddingTop = parseInt(overlay.css('paddingTop')),
        titleHeight = title.outerHeight(true),
        newPosition = titleHeight + paddingTop;

    overlay.css({top: 'calc(100% - ' + newPosition + 'px)'});
  }

})(jQuery, Drupal, window, document);
