/**
 * @file
 * Styles popups inside a google map.
 *
 * By default the popup of a google map only has 1 class ()
 * that you can target. This class is inside the popup and is only a wrapper
 * around the text, making it impossible to style the popup and the close button.
 *
 * This code adds extra classes for the popupcontainer, the popup content and
 * the close button and also adds a default styling based on those classes.
 *
 * See the README.md file for more instructions.
 *
 * Created by: joery.lemmens@intracto.com
 * Created on: 29/05/2018
 */

(function ($) {

    /**
     * Target selectors.
     */
    var settings = {
        mapSelector: '.js-gmap',
        loadedClass: 'is-loaded'
    };

    Drupal.behaviors.gmapPopupStyler = {

        attach: function () {
            /*
             * Google Maps objects are async loaded. So we must wait until there is an actual loaded Google Map.
             * We need the div that loads the Google Map to have the class 'js-gmap' and when the Google map is fully
             * loaded, the div needs to have the 'is-loaded' class. The data_attribute_gmap module does this by default.
             */
            var maps = $(settings.mapSelector);
            if (maps.length) {
                maps.each(function () {
                    Drupal.behaviors.gmapPopupStyler.waitUntilMapIsLoaded($(this), settings.loadedClass);
                });
            }
        },

        /*
         * Keep waiting until the Google Map is loaded. When it is loaded: add a click handler.
         * The Google Map popups are created on the fly when clicking on a marker, so we cannot style them before this.
         * That is why all the styling need to happen on the click event.
         */
        waitUntilMapIsLoaded: function (mapElement, loadedClass) {
            if (!mapElement.hasClass(loadedClass)) {
                setTimeout(function () {
                    Drupal.behaviors.gmapPopupStyler.waitUntilMapIsLoaded(mapElement, loadedClass);
                }, 100)
            }
            if (mapElement.hasClass(loadedClass)) {
                mapElement.on('click', function () {
                    Drupal.behaviors.gmapPopupStyler.addPopupClasses();
                });
            }
        },

        /*
         * Function that adds the classes we need. By default the popupcontent has
         * a class .gm-style-iw. The background and close buttons are sibling div's
         * of that, and the container is the parent of that. We use this logic to
         * add our desired classes.
         */
        addPopupClasses: function (extraclass) {
            var popupcontent = $('.gm-style-iw');
            if (popupcontent.length) {
                popupcontent.parent().addClass('gmap-popup');
                if (extraclass) {
                    popupcontent.parent().addClass(extraclass);
                }
                popupcontent.prev().addClass('gmap-popup__original-bg');
                popupcontent.addClass('gmap-popup__content js-gmap-popup');
                popupcontent.next().addClass('gmap-popup__close-button');

                // Create callback for third party plugins to hook onto the infoWindow.
                var event = new CustomEvent('gmap-popup-open');
                document.dispatchEvent(event);
            }
        }
    };

})(jQuery);
