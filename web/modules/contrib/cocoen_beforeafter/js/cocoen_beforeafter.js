(function($, Drupal){

  Drupal.behaviors.beforeafter = {
    attach: function(context, settings){
      $('.cocoen-beforeafter-container').once().each(function(){
        $(this).cocoen();
      });
    }
  }

})(jQuery, Drupal);
