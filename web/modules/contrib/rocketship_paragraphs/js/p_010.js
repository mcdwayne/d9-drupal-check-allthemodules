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

  Drupal.behaviors.rocketshipUI_p010 = {

    attach: function (context, settings) {

      $(document).ready(function(){
        $(document).find('.field--name-field-p-010-children .field__items').once().each(function() {
          var slider = $(this);

          // Init slick
          slider.slick({
            slide: '.field__item',
            infinite: true,
            speed: 300,
            slidesToShow: 5,
            slidesToScroll: 1,
            adaptiveHeight: true,
            prevArrow: '<span class="slick-prev">Previous</span>',
            nextArrow: '<span class="slick-next">Previous</button>',
            responsive: [
              {
                breakpoint: 1200,
                settings: {
                  slidesToShow: 5,
                }
              },
              {
                breakpoint: 940,
                settings: {
                  slidesToShow: 4,
                }
              },
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 3,
                }
              },
              {
                breakpoint: 600,
                settings: {
                  slidesToShow: 2,
                }
              },
              {
                breakpoint: 480,
                settings: {
                  slidesToShow: 1,
                }
              },
            ]
          });

          // Enable autoplay if needed
          if (slider.parent('.field--name-field-p-010-children').hasClass('autoplay')) {
            setTimeout(function () {
              slider.slick('slickPlay');
            }, 5000);
          }
        });
      });

    }
  };

})(jQuery, Drupal, window, document);
