/**
 * @file
 */

(function ($) {
  'use strict';

  function do_slide(autoplaySelector, autoplayInterval) {
    setInterval(
    function () {
      jQuery(autoplaySelector).click();
    }, autoplayInterval
   );
  }

  Drupal.behaviors.superSlides = {
    attach: function (context, settings) {
            // jQuery once ensures that code does not run after an AJAX or other function that calls Drupal.attachBehaviors().
      $('body').once('superSlides').each(
                function () {

                  var superslidesView = settings.views.superslidesViews;

                  for (var i in superslidesView) {

                    if (!superslidesView.hasOwnProperty(i)) {
                      continue;
                    }

                    var obj = superslidesView[i];

                    for (var prop in obj) {
                      if (obj.hasOwnProperty(prop)) {

                        var selector = '#slider-dom-id-' + obj.view_dom_id.trim();
                        var autoplay = obj.autoplay;
                        var autoplayInterval = parseInt(obj.autoplayinterval);
                        var slideshow_animation = obj.slideshow_animation;

                        if (autoplay === 1) {
                          var autoplaySelector = selector + ' .slides-navigation .next';
                          do_slide(autoplaySelector, autoplayInterval);
                        }

                        if (slideshow_animation !== null) {
                          $(selector).superslides(
                            {
                              hashchange: false,
                              animation: slideshow_animation
                            }
                                );
                        }
			else {
			  $(selector).superslides(
                            {
                              hashchange: false
                            }
                                );
			}
                      }
                    }
                  }
                }
            );
    }
  };
})(jQuery);
