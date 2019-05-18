/**
 * @file
 * Condition UI behaviors.
 */

(function ($, Drupal, drupalSettings) {
    
    "use strict";

    /**
      * Redirect user to checkout complete when a valid Xem transaction is found. 
      *
      * @type {Drupal~behavior}
      *
      * @prop {Drupal~behaviorAttach} attach
      *   Attaches the behavior for the condition summaries.
      */
    Drupal.behaviors.xem_checkout_redirection = {
        attach: function (context) {
            $(function () {
                var xemPayment = {
                    init: function () {
                        
                    },
                    checkXemTransaction: function () {
                            $.ajax({
                                url: drupalSettings.xem.notifyUrl,
                                type: 'post',
                                data: {
                                    message: drupalSettings.xem.message,
                                    orderId: drupalSettings.xem.orderId
                                }
                            }).done(function (result) {
                                // If a Xem transaction has been found and validated
                                if(result.match === true) {
                                    // Reload the current page
                                    // The user will be redirect to checkout complete
                                    location.reload();
                                }
                                // Check for transactions every 5 seconds
                                setTimeout(function() {
                                    xemPayment.checkXemTransaction();
                                }, 5000);
                            });
                    },
                };
                xemPayment.init();
                setTimeout(function() {
                    xemPayment.checkXemTransaction();
                }, 5000);
            });
        }
    }
})(jQuery, Drupal, drupalSettings);