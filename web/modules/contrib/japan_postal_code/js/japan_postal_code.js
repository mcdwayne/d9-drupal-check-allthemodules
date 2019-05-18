/**
 * @file
 * Provides Japan postal code utilities.
 */

(function($, Drupal) {

  'use strict';

  Drupal.japanPostalCode = {

    /**
     * Helper function to fetch multiple addresses.
     *
     * @param postalcode
     *   Postalcode with 7 digits.
     * @param success
     *   Success callback.
     */
    fetchAll: function (postalcode, success) {
      $.getJSON('/japan-postal-code/addresses/' + postalcode, null, success);
    },

    /**
     * Helper function to fetch single address.
     *
     * @param postalcode
     *   Postalcode with 7 digits.
     * @param success
     *   Success callback.
     */
    fetchOne: function (postalcode, success) {
      $.getJSON('/japan-postal-code/address/' + postalcode, null, success);
    }
  };

})(jQuery, Drupal);
