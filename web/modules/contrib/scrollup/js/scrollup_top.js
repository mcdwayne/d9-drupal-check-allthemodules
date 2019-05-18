(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.scrollup = {

    attach: function (context, settings) {      
      $(document).ready(function(){
		  if(drupalSettings.scrollup_title == '' || drupalSettings.scrollup_title == null){
			var scroll_title = '';
		  } else {
			var scroll_title = drupalSettings.scrollup_title;
		  }
		  
          $('body').append('<a href="#" title="scroll_title" class="scrollup">Scroll<div class="scroll-title">'+scroll_title+'</div></a>');
          var position = drupalSettings.scrollup_position;
          var button_bg_color = drupalSettings.scrollup_button_bg_color;
          var hover_button_bg_color = drupalSettings.scrollup_button_hover_bg_color;
		  var scroll_window_position = parseInt(drupalSettings.scrollup_window_position);
		  var scroll_speed = parseInt(drupalSettings.scrollup_speed);
		  
          if (position == 1) {
            $('.scrollup').css({"right":"100px","background-color":button_bg_color});
          } else {
            $('.scrollup').css({"left":"100px","background-color":button_bg_color});
          }
          
          $(".scrollup").hover(function(){
            $(this).css("background-color", hover_button_bg_color);
          }, function(){
            $(this).css("background-color", button_bg_color);
          });
          
          $(window).scroll(function () {
            if ($(this).scrollTop() > scroll_window_position) {
              $('.scrollup').fadeIn();
            } else {
              $('.scrollup').fadeOut();
            }
          });
          
          $(".scrollup").click(function(){
            $("html, body").animate({
              scrollTop: 0
            }, scroll_speed);
            return false;
          });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
