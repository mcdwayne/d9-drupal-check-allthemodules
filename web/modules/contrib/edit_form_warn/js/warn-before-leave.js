(function($) {
	var warn = false;
	var notsub = true;

	Drupal.behaviors.warn_before_leave = {

		attach: function(context, settings){

			$('form').submit(function(){
				warn = false;
				notsub = false;
			});

			$('input, textarea, select', context).once("warn_before_leave").each(function(){
				$(this).on('change input', function(){
					warn = true;
				});
			});
		}
	}

	window.addEventListener("beforeunload", function (e) {
	    var confirmationMessage = 'You may have unsaved changes. Are you sure you wish to leave?';

	    if (notsub && CKEDITOR){
		    for(var instanceName in CKEDITOR.instances) {
			   if(CKEDITOR.instances[instanceName].checkDirty()){
			   	warn = true;
			   }
			}
		}

	    if(warn){
		    (e || window.event).returnValue = confirmationMessage; 
		    return confirmationMessage;
		}
	});
})(jQuery);