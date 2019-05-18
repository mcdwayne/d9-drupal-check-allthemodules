/**
 * @file
 * IP Ranges form javascript.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Adds users IP address to whitelist.
   *
   * Populates Add IP Range form fields and submits the form.
   */
  Drupal.behaviors.ipRangesAddOwnIp = {
    attach: function (context) {
      $('form#ip-ranges-add-form', context).once('ipRangesAddOwnIp', function() {
        var $form = $(this);

        // Bind click event.
        $form.find('a#add-my-own-ip').on('click', function(event) {
          event.preventDefault();

          // Get users IP from link data-attribute.
          var myIp = $(this).data('my-ip');

          // Change lower and higher IPs to users IP.
          $form.find('input#edit-ip-lower').val(myIp);
          $form.find('input#edit-ip-higher').val(myIp);
          // Change type to whitelist.
          $form.find('select#edit-type').val(1);

          // Submit form.
          $form.submit();
        });
      });
    }
  };

})(jQuery, Drupal);
