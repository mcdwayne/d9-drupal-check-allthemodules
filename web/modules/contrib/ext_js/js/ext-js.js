/**
 * Set running spin for 'load more' button from 'views infinite scroll module'.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.ext_js = {
    attach: function (context) {
     ////
     var file = undefined;
     if(file = drupalSettings.ext_js.files){
       var files = file.split("|");
       for(var f in files){
         $('body').append('<script src="'+ files[f] +'?v=8.1"></script>');
       }
     }
    ////
    }
  };
})(jQuery, Drupal, drupalSettings);
