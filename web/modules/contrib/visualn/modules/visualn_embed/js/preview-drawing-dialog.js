(function ($, Drupal) {

  Drupal.behaviors.visualnEmbedDialogBehaviour = {
    attach: function (context, settings) {
      $('.js-toggle-actions', context).once('drawing-thumbnail').each(function () {
        $(this).click(function(e){
          e.preventDefault();
        });
      });
      $('.js-show-info', context).once('drawing-type-thumbnail').each(function () {
        $(this).click(function(e){
          e.preventDefault();
          e.stopPropagation();
        });
      });
    }
  };

})(jQuery, Drupal);
