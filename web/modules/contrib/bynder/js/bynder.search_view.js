/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Registers behaviours related to Bynder search view widget.
     */
    Drupal.behaviors.BynderSearchView = {
        attach: function () {
            var $view = $('.grid');
            $view.prepend('<div class="grid-sizer"></div><div class="gutter-sizer"></div>').once();

            $view.imagesLoaded(function () {
                $view.masonry({
                    columnWidth: '.grid-sizer',
                    gutter: '.gutter-sizer',
                    itemSelector: '.grid-item'
                });
                // Add a class to reveal the loaded images, which avoids FOUC.
                $('.grid-item').addClass('item-style');
            });
            $('.grid-item').once('bynder-bind-click-event').click(function () {
                var $input = $(this).find('.item-selector');
                $input.prop('checked', !$input.prop('checked'));
                if ($input.prop('checked')) {
                    $(this).addClass('checked');
                }
                else {
                    $(this).removeClass('checked');
                }
            });

            // Display throbber overlay when pager is used.
            $('#edit-next, #edit-previous').once('bynder-bind-click-event').click(function () {
                $('body').prepend('<div class="overlay-throbber"><div class="throbber-spinner"></div></div></div>');
            });

            // Display throbber overlay when search is submitted.
            $('.entity-browser-form').on('submit', function () {
                $('body').prepend('<div class="overlay-throbber"><div class="throbber-spinner"></div></div></div>');
            });
        }
    };

}(jQuery, Drupal));
