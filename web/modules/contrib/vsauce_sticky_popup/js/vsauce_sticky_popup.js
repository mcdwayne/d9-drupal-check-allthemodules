(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.vsauceStickyPopup = {
    attach: function (context, settings) {
      if($('.vsp-wrapper').length > 0) {
        $.each($('.vsp-wrapper'), function(){
          $(this, context).once('js-vsp-listen').append(function(){
            $('.vsp-button.js-button',this).click(function(){
              Drupal.behaviors.vsauceStickyPopup.triggerVSP($(this));
            })
          });
        });
      }
    },
    triggerVSP: function(element, trigger = 'toggle'){

      let vspWrapper = $(element).closest('.vsp-wrapper');
      switch (trigger) {
        case 'toggle':
          $(vspWrapper).toggleClass('vsp-collapse');
          break;
        case 'open':
          $(vspWrapper).removeClass('vsp-collapse');
          break;
        case 'close':
          $(vspWrapper).addClass('vsp-collapse');
          break;
      }
    }
  };
})(jQuery, Drupal);