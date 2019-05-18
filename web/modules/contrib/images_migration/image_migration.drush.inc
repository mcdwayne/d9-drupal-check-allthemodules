<?php
/**
* @file Contains the code to generate the custom drush commands.
*/

/**
* Implements hook_drush_command().
*/
function image_migration_drush_command() {
 $items = array();
 $items['image-migrate'] = [
   'description' => 'Migrate Images from Drupal 7 to Drupal 8 instance',
   'arguments' => [
     'source_path' => 'The name for saying hello',
   	 'destination_path' => 'The name for saying hello',
   	 'content_type_list' => 'Content Type Listing'
   ],
   'drupal dependencies' => ['image_migration'],
   'aliases' => ['say:hello'],
 ];
 return $items;
}

/**
* Call back function drush_custom_drush_command_say_hello()
* The call back function name in the  following format
*   drush_{module_name}_{item_id_for_command}()
*/
function drush_image_migration_image_migrate($source_path = '', $destination_path = '', $content_type_list = '') {
	$source_path = drush_prompt(dt('Please Enter Source Path: '));
	$destination_path = drush_prompt(dt('Please Enter Destination Path: '));
	
	$contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
	$contentTypesList = [];
	foreach ($contentTypes as $contentType) {
		$contentTypesList[$contentType->id()] = $contentType->label();
	}
	$choice = drush_choice($contentTypesList, dt('Select Content type:'));
	
	if (drush_confirm('Are you sure you want to migrate images from source to destination path?')) {
		error_reporting(0);
		$batch = [];
		$batch = array(
				'title' => t('Image Migration is in Progress...'),
				'operations' => array(
						array(
								Drupal\image_migration\Form\AdminSettingsConfigForm::copyAssets($source_path, $destination_path),
								Drupal\image_migration\Form\AdminSettingsConfigForm::imageMigration($contentTypesList[$choice])
						),
				),
				'finished' => drupal_flush_all_caches()
		);
		batch_set($batch);
		drush_print('Images Migrated Successfully');
	}
	else {
		drush_user_abort();
	}
}