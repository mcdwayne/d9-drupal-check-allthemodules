(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.icn = {

        attach: function (context, settings) {

            $(document).ready(function () {

                if (Drupal.ICN_STATUS != true) {

                    var icn_block = "#icn-nav ul.nav";

                    $('.icn-title').each(function (index) {
                        var html = '<a href="#' + $(this).attr('id') + '">' + $(this).text() + '</a>';
                        $(icn_block).append('<li>' + html + '</li>');
                    })

                    var default_links = settings.icn.default;
                    for (var id in default_links) {

                        var link = default_links[id];
                        var html = '<a href="' + link.url + '">' + link.title + '</a>';
                        $(icn_block).append('<li>' + html + '</li>');
                    }

                    /*
                     * Bind Navigation effect
                     */
                    $(icn_block + ' a').click(function (event) {
                        var page = $(this).attr('href');
                        if (page.includes('#')) {
                            var speed = 750;
                            var offset_target = $(page).offset().top - $(page).height() - $(icn_block).height() - 20;
                            $('html, body').animate({scrollTop: offset_target}, speed);
                            event.preventDefault();
                            return false;
                        } else {
                            if (page.includes('http')) {
                                // external
                                event.target.setAttribute("target", "_blank");
                            }
                        }
                    });

                    var sticky = settings.icn.sticky;
                    var logged_in = $('body.user-logged-in').length;
                    /*
                     * Back-to-top
                     */
                    $('body').prepend('<a href="" class="icn-back-to-top">Back to Top</a>');

                    /**
                     * On scroll
                     */

                    // Keep nativ postion of block
                    var target = $(icn_block).offset().top - $(icn_block).height();

                    $(window).scroll(function () {
                        var current = $(document).scrollTop();

                        // If sticky
                        if (sticky && !logged_in && current >= target) {
                            $(icn_block).addClass('fixed');
                        } else {
                            $(icn_block).removeClass('fixed');
                        }

                        // Return to top
                        if (current > 300) {
                            $('a.icn-back-to-top').fadeIn('slow');
                        } else {
                            $('a.icn-back-to-top').fadeOut('slow');
                        }
                    });

                    $('a.icn-back-to-top').click(function (event) {
                        $('html, body').animate({
                            scrollTop: 0
                        }, 700);
                        event.preventDefault();
                        return false;
                    });

                    Drupal.ICN_STATUS = true;
                }

            })

        }

    };

})(jQuery, Drupal, drupalSettings);