/**
 * @file
 * The main javascript file for the splash_screen module
 *
 * @ingroup splash_screen
 * @{
 */

(function ($) {  
  /**
  * @} Start of "loading modal".
  */
	$(window).bind('load', function() {		
		var cokk = readCookie(drupalSettings.splash_screen.cookies.nam);	
		if(unescape(cokk) == drupalSettings.splash_screen.page.id){
			jQuery('.ui-widget-content').hide();
			jQuery('.ui-widget-overlay').hide();
		} else {			
			if(drupalSettings.splash_screen.cuddlySlider.foo == 'yes') {
				$('.use-ajax.button--small').click();
			}
		}
		
	});

})(jQuery);

/*
* Start function for reading cookies values.
*/
function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
