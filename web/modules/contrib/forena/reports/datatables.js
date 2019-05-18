/**
 * @file
 * Set behaviors related to dataTables Features.
 */

(function ($) {
  Drupal.behaviors.ForenaDatatablesFeatures = {
    attach: function (context) {
      //See http://www.datatables.net for documentation
      $('.FrxTable table', context).dataTable({
        "sPaginationType": "full_numbers",
        "stateSave": true
      });
    }
  };
  
})(jQuery);

