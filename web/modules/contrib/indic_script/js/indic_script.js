(function ($) {

  Drupal.behaviors.indic_script = {
    attach: function(context, settings) {
    enabledLanguages = settings.enabledLanguages;
    kanniConfig = settings.kanniConfig;
      $('.kanni-enabled').once(function(){
        $(this).each(function(){
          var node = $(this)[0]; // first element
          Kanni.enableNode(node);
        });
      });
    }
  };

})(jQuery);