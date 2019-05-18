(function ($, Drupal, drupalSettings) {
  'use strict';
  
  Drupal.behaviors.reasonsbounceForm = {
    attach: function (context, settings) {
      window.onbeforeunload = function(e) {
        e = e || window.event;
        var text = drupalSettings.reasonsbounce.form.message;
        if (e) {
          e.returnValue = text;
        }
        setTimeout(function() {
          var ajaxObject = Drupal.ajax({url: drupalSettings.reasonsbounce.form.path});
          ajaxObject.execute();
        }, 1000);
      }
      
      $('a, form, select, div').each(function(index, value){
        $(this).click(function(e) {
          window.onbeforeunload = function(e) {};
        });
      })
    }
  };

})(jQuery, Drupal, drupalSettings);