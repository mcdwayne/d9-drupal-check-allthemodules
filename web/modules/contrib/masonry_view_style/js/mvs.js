(function($) {
    'use strict';

    Drupal.behaviors.masonry_view_style = {
        attach: function (context, settings) {
            var $grid = $('.mvs-grid').imagesLoaded( function() {
                // init Masonry after all images have loaded
                $grid.masonry({
                    itemSelector: '.mvs-grid-item',
                });

                 // reload on ajax update
                if (context !== document) {
                    $grid.masonry('reloadItems');
                    $grid.masonry('layout');
                }
            });
        }
    }
})(jQuery);
