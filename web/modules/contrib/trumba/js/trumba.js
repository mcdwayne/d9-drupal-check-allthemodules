/**
 * @file
 * Javascript for creating Trumba Calendar Spuds.
 */
(function ($, Drupal) {
    "use strict";

    // Create a Trumba Spud.
    Drupal.behaviors.TrumbaAddSpud = {
        attach: function (context, settings) {
            // Find each Trumba spud and init once.
            $(context).find('.trumba-spud').once('trumba-init').each(function() {

                $(this).each(function () {
                    var spudId = $(this).data('trumba-spud');

                    // Clone the object so we don't wreck the original settings.
                    var spud = $.extend(true, {}, settings.trumba[spudId]);

                    // Create the calendar.
                    $Trumba.addSpud(spud);
                });
            });

        }
    };

})(jQuery, Drupal);
