/**
 * @file
 * Javascript file for the easymeta module.
 */

(function ($, Drupal, window) {
  $(document).ready(function(){
    $("#easymeta-form .easymeta-open").click(function(){
      if ($(this).hasClass("open")) {
        $("#easymeta-form").animate({
          left: "-300px"
        }, function(){
          $("#easymeta-form .easymeta-open").removeClass("open");
        });
      }
      else {
        $("#easymeta-form").animate({
          left: "0"
        }, function(){
          $("#easymeta-form .easymeta-open").addClass("open");
        });
      }
    });
  });
})(jQuery, Drupal, window);
