(function ($) {
    'use strict';

    Drupal.behaviors.dhis = {
        attach: function (context, settings) {
            $(".metadata-fetch").prop("disabled", true);
        }
    };

}(jQuery));