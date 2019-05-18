/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Attach datepicker fallback on date elements.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior. Accepts in `settings.date` an object listing
     *   elements to process, keyed by the HTML ID of the form element containing
     *   the human-readable value. Each element is an datepicker settings object.
     * @prop {Drupal~behaviorDetach} detach
     *   Detach the behavior destroying datepickers on effected elements.
     */
    Drupal.behaviors.melli_codes = {
        attach: function (context, settings) {
            // iran_card_number, sheba, melli_code, iran_postal_code, iran_phone, iran_mobile
            var $context = $(context);
            if ($context.find('.iran_card_number').length) {
                new Cleave('.iran_card_number', {
                    creditCard: true
                });
            }
            if ($context.find('.iran_mobile').length) {
                new Cleave('.iran_mobile', {
                    phone: true,
                    phoneRegionCode: 'IR'
                });
            }
            if ($context.find('.iran_phone').length) {
                new Cleave('.iran_phone', {
                    phone: true,
                    phoneRegionCode: 'IR'
                });
            }
        },
        detach: function (context, settings, trigger) {
            if (trigger === 'unload') {

            }
        }
    };

})(jQuery, Drupal);