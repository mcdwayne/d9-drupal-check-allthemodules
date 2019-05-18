/**
 * @file
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Registers behaviours related to Bynder upload widget.
     */
    Drupal.behaviors.bynderUpload = {
        attach: function () {
            $('#edit-submit').once('bynder-asset-upload').click();
        }
    };

}(jQuery, Drupal));
