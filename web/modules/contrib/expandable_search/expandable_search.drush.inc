<?php

function expandable_search_drush_command() {
  $items['expandable_search'] = array(
    'description' => 'Demonstrate how Drush commands work.',
    'aliases' => array('exse'),
	'callback' => 'expandable_search_drush_download_libraries'
  );

  return $items;
}
/**
 * Callback for the drush-demo-command command
 */
 function expandable_search_drush_download_libraries(){
	$config = \Drupal::config('expandable_search.config');
	drupal_set_message(t("Started downloading the dependencies..."), "status");
	foreach($config->get("expandable_search_dependencies") as $key => $library){
		expandable_search_download_file($library["repository"],$key); 
	}
 }
function expandable_search_download_file($repository,$folder) {
	// download the css librarz
	$baseFolder = "/animate.css-master";
	$dirpath = (\Drupal::service('file_system')->realpath("libraries")) . "/$folder/";
	
	
	drupal_set_message(t("Trying to download the $repository repository as zip archive into $dirpath"), "status");
	
	file_prepare_directory($dirpath,FILE_CREATE_DIRECTORY);
    $result = drush_download_file($repository, $dirpath. "master.zip", DRUSH_CACHE_LIFETIME_DEFAULT);
	if($result != false){
		drupal_set_message(t("Successfully downloaded"), "status");
		$zip = new ZipArchive;
		$res = $zip->open($dirpath.'master.zip');
		if ($res === TRUE) {
		  $zip->extractTo($dirpath);
		  $zip->close();
		  drupal_set_message(t("Successfully extracted the contents"), "status");
		  if(unlink($dirpath."master.zip")){
			  drupal_set_message(t("Success deleting the repository zip archive"), "status");
		  }else{
			  drupal_set_message(t("Could not delete the repository zip archive"), "warning");
		  }
		 
		} else {
		   drupal_set_message(t("Error on extracting the contents. Aborting..."), "error");
		   return false;
		}
	}else{
		drupal_set_message(t("Error on downloading. Aborting..."), "error");
		return false;
	}
   
}

