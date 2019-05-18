
document.getElementById('testConfigButton').onclick = function() {
	
	var baseurl = window.location.href;
	var pos = baseurl.indexOf("admin");
	var testUrl = baseurl.replace(baseurl.slice(pos), "testConfig");
	
	var myWindow = window.open(testUrl, "TEST SAML IDP", "scrollbars=1 width=800, height=600");
}
