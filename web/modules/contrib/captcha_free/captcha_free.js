/**
 * @file
 * jQuery to call CaptchaFreeController, control the "no JavaScript enabled"
 * warning, and add a hidden input type.
 */
(function ($) {
  $(document).ready( function(){
    $('.warning').remove();
    $.get("/give-cookie",function(txt){
    $("#" + drupalSettings.captchaFree.selector + "").append('<input type="hidden" name="ts" value="'+txt+'" />');
    });
  });
})(jQuery);