(function ($, Drupal, drupalSettings) {
  "use strict";
 Drupal.behaviors.caqModule = {                             
    attach: function (context, settings) {                       
      $('.caq', context).click(function () {                 
      $(".panel").hide();
      $(this).next().show();                     
      });                                                        
    }                                                            
  }; 
})(jQuery, Drupal, drupalSettings);