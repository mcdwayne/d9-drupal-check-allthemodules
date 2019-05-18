jQuery(function($){
	$(document).ready(function() {
	    var buttonClass = $('#edit-pixelpin-openid-connect-client-enable-login-hide').attr('class');
	    var buttonClass2 = $('#edit-pixelpin-openid-connect-client-enable-connect-hidden').attr('class');
	    var buttonClass3 = $('#edit-pixelpin-openid-connect-client-enable-disconnect-hidden').attr('class');

	    if(typeof(buttonClass) != "undefined" && buttonClass !== null) {
	    	$('#edit-pixelpin-openid-connect-client-enable-login').removeClass('button js-form-submit form-submit').addClass(buttonClass);
	    }
	    else
	    {

	    }

	    if(typeof(buttonClass2) != "undefined" && buttonClass2 !== null) {
	    	$('#edit-pixelpin-openid-connect-client-enable-connect').removeClass('button js-form-submit form-submit').addClass(buttonClass2);
	    	   
	    }
	    else
	    {

	    }

	    if(typeof(buttonClass3) != "undefined" && buttonClass3 !== null) {
	    	$('#edit-pixelpin-openid-connect-client-enable-disconnect').removeClass('button js-form-submit form-submit').addClass(buttonClass3);
	    }
	    else
	    {

	    }
	});
});