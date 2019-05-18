document.getElementById('premiumButton').onclick = function() {
	window.location ="https://auth.miniorange.com/moas/login?redirectUrl=https://auth.miniorange.com/moas/initializepayment&requestOrigin=drupal8_miniorange_saml_premium_plan";
}

document.getElementById('doItYourselfButton').onclick = function() {
	window.location="https://auth.miniorange.com/moas/login?redirectUrl=https://auth.miniorange.com/moas/initializepayment&requestOrigin=drupal8_miniorange_saml_basic_plan";
}