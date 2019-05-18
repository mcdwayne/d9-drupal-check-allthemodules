/**
 * @file
 * 
 */

(function ($, Drupal, window, document, undefined) {


// To understand behaviors, see https://drupal.org/node/756722#behaviors
Drupal.behaviors.booking_views = {
  attach: function(context, settings) {
    var rows = $('.view.view-admin-bookings .view-content tbody tr', context);
    console.log('rows: ', rows);

    rows.each(function() {
      var status = $(this).find('.views-field-field-booking-status').html().trim();
      // console.log('status: ', status);

      // Provisional
      // Expired
      // Confirmed
      // Cancelled
      // Completed
      
      $(this).addClass('status-' + status);
    });
  }
};


})(jQuery, Drupal, this, this.document);