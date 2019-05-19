(function ($) {

  Drupal.behaviors.webformCounter = {
    attach: function (context) {
      $('span[data-webform-counter]', context).each(function() {
        Drupal.ajax($(this).data('webform-counter')).execute();
      });
    }
  };

})(jQuery);
