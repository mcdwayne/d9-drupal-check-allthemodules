/**
 * @file
 */

(function ($) {

    Drupal.behaviors.coolMessage = {
        attach: function (context, settings) {

            $('.messages', context).hide();
            $('.messages', context).fadeIn('slow');
            // Status color background-color.
            if (drupalSettings.coll_message.status_color) {
                $('div.cool-messages.status').css('background-color', drupalSettings.coll_message.status_color);
            }
            // Info color background-color.
            if (drupalSettings.coll_message.info_color) {
                $('div.cool-messages.info').css('background-color', drupalSettings.coll_message.info_color);
            }
            // Warning color background-color.
            if (drupalSettings.coll_message.warning_color) {
                $('div.cool-messages.warning').css('background-color', drupalSettings.coll_message.warning_color);
            }
            // Error color background-color.
            if (drupalSettings.coll_message.error_color) {
                $('div.cool-messages.error').css('background-color', drupalSettings.coll_message.error_color);
            }
            // Hide a message when clicked on it.
            $('.messages').click(function(){
					if(drupalSettings.coll_message.simple_coolmessage_enable == 1){  
                      $(this).fadeOut('slow');
                    }	
				}                
            );

        }
    };

})(jQuery);
