(function ($, Drupal, drupalSettings) {

    "use strict";

    Drupal.behaviors.plista = {
        attach: function (context, settings) {

            console.log(settings);
            /*
             PLISTA.items.push(settings.plista);
             PLISTA.partner.init();
             */
        }
    };
})(jQuery, Drupal, drupalSettings);
