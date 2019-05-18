/**
 * @file
 * Js apply all animation to pages
 */

(function ($, Drupal) {
    'use strict';
    // animation goes here
    Drupal.behaviors.animate_any = {
        attach: function (context, settings) {
            $(document).ready(function () {
                // get all animation json data here
                var animations = $.parseJSON(settings.animate.animation_data);
                // on scroll we apply animation here
                $(window).scroll(function () {
                    $.each(animations, function (i, element) {
                        // first main identifier
                        var animate_parent = element.parent;
                        var animate_ident = $.parseJSON(element.identifier);
                        // second below identifier
                        if ($(animate_parent).length !== 0) {
                            $.each(animate_ident, function (k, item) {
                                var section = $(item.section_identity);
                                if ($(item.section_identity).length !== 0) {
                                    // add animation to child section only when it is visible on viewport
                                    if (section.visible()) {
                                        $(animate_parent).find(item.section_identity).addClass(item.section_animation + ' animated');
                                    }
                                }
                            });
                        }
                    });
                });
            });
        }
    };

    /**
     *function use to identify the dom element visible or not
     */
    $.fn.visible = function () {

        var win = $(window);
        var viewport = {
            top: win.scrollTop(),
            left: win.scrollLeft()
        };
        viewport.right = viewport.left + win.width() - 100;
        viewport.bottom = viewport.top + win.height() - 100;

        var bounds = this.offset();
        bounds.right = bounds.left + this.outerWidth();
        bounds.bottom = bounds.top + this.outerHeight();

        return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
    };
})(jQuery, Drupal);