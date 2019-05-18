<?php

	//Betöltjük a WSDLInterpreter.php -t
	require_once 'WSDLInterpreter-v1.0.0/WSDLInterpreter.php';
	
	//Megadjuk a wsdl-t
	$wsdlLocation = 'https://billzone.eu/billgate?wsdl';
	
	$wsdlInterpreter = new WSDLInterpreter($wsdlLocation);
	
	//Elmentjük a legenerált php fájlt
	$wsdlInterpreter->savePHP(DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . '/includes/PHP');
	
?>