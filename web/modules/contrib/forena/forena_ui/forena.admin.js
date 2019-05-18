/**
 * @file
 * Legacy forena behaviors.  These are deprecated. 
 */
(function ($) {

  Drupal.behaviors.forenaAdmin = {
    attach: function (context) {
      $('table.dataTable-paged', context).dataTable({
        "sPaginationType": "full_numbers" 
      }); 
     
    }
  };

})(jQuery);
