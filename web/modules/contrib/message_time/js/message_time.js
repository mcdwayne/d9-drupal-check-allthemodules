(function ($, Drupal) {
	Drupal.behaviors.message_time = {
		attach: function (context, settings) {

			var enable = drupalSettings.message_time.message_enable;
			//	Check active message time. 
			if (enable) {
				jQuery('.messages').delay(drupalSettings.message_time.message_fadeOut).slideUp('slow');
			}
			
		}
	}
})(jQuery, Drupal);