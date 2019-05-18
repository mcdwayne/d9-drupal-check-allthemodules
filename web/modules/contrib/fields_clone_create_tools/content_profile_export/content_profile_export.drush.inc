<?php

function content_profile_export_drush_command()  {
	$items = array();
	$items['contentprofilefields'] = array(
			'description' => 'This is used to export fields  from Content Type used by Drupal 6 Content Profile Module to User Profile Type',
			'aliases'=> array('contentProfileField')
			);



       $items['fieldSourceToDestination'] = array(
                        'description'=> 'Field From Source To Destination',
                        'aliases'=> array('fieldSourceDestination'),
                        );


	return $items;
}


function drush_content_profile_export_contentprofilefields() {

	$contentProfileFieldObj = \Drupal::service('content_profile_field_service');
	$contentProfileFieldObj->generateFields();
        drush_log('Done SucessFully');
}



function drush_content_profile_export_fieldSourceToDestination() {

         $contentFieldsObject = \Drupal::service('fields_source_destination');
         $contentFieldsObject->generateFields();
         drush_log(" Done Successfully ");
}
