/**
 * @file
 * Attaches the behaviors for the Scroll to element module.
 */
(function ($) {
    'use strict';

    Drupal.behaviors.scrollToElement = {
        attach: function (context, drupalSettings) {
            var settings = drupalSettings.scroll_to_element;
            $(getSelectors(settings)).click(function (event) {
                var identifier = $(event.target).attr('href').split('#')[1];
                var elementSettings = $.grep(settings.elements, function (element) {
                    return identifier === element.selector;
                });
                var element = getTabElement(identifier);
                if (element.length) {
                    element.click();
                } else {
                    element = getElementById(identifier);
                }

                if (elementSettings[0].duration > 0) {
                    scrollToElement(element, elementSettings[0]);
                }
                event.preventDefault();
            });
        }
    };

    function getElementById(identifier) {
        return $('#' + identifier);
    }

    function getTabElement(identifier) {
        return $('a[href="#' + identifier + '"][data-toggle="tab"]');
    }

    function scrollToElement(element, settings) {
        $('html, body').animate({
            scrollTop: element.offset().top + settings.offset
        }, settings.duration);
    }

    function getSelectors(settings) {
        var result = [];
        settings.elements.forEach(function (element) {
            result.push('a[href="' + document.location.pathname + '#' + element.selector + '"]');
            result.push('a[href="#' + element.selector + '"]:not([data-toggle="tab"])');
        });
        return result.join(',');
    }

})(jQuery);