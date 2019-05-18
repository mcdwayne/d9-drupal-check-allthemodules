
document.getElementById('showSAMLrequestButton').onclick = function() 
{

	var baseurl = window.location.href;

	var pos = baseurl.indexOf("admin");
	var testUrl = baseurl.replace(baseurl.slice(pos), "showSAMLRequest");
	
	var myWindow = window.open(testUrl, "TEST SAML IDP", "scrollbars=1 width=800, height=600");
}

document.getElementById('showSAMLresponseButton').onclick = function() 
{

	var baseurl = window.location.href;

	var pos = baseurl.indexOf("admin");
	var testUrl = baseurl.replace(baseurl.slice(pos), "showSAMLResponse");
	
	var myWindow = window.open(testUrl, "TEST SAML IDP", "scrollbars=1 width=800, height=600");
}
