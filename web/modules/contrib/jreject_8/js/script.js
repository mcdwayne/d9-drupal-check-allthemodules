(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.jreject = {
        attach: function (context, settings) {
            var params = {
                display: drupalSettings.jreject.browserAlternatives,
                reject: drupalSettings.jreject.rejects,
                imagePath: '/libraries/jReject/images/',
            };

            for(var elt in drupalSettings.jreject.opts){
                params[elt] = drupalSettings.jreject.opts[elt];
            }

            setTimeout(function() {
                $.reject(params);
            },1000)
        }
    };
})(jQuery, Drupal, drupalSettings);