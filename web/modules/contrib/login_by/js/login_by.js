(function ($, Drupal) {
	Drupal.behaviors.login_by = {
		attach: function (context, settings) {

			var enable = drupalSettings.login_by.view_password;
			//	Check active show password 
			if (enable) {
				jQuery('#edit-pass').after('<span class="show-password" onclick="showPassword()"></span>');
			}
			
		}
	}
})(jQuery, Drupal);

function showPassword() {
	var x = document.getElementById("edit-pass");
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}