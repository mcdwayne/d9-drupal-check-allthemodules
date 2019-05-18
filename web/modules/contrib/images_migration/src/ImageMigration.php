<?php
/**
 * @file
 * Contains \Drupal\image_migration\ImageMigration.
 */

namespace Drupal\image_migration;

use Drupal\Core\Database\Database;

/**
 */
class ImageMigration {
	/**
	 * Constructor.
	 */
	public function __construct() {
		
	}	
	
	/**
	 *  Setup External DB Connection
	 */
	public function setExternalDBConnection() {
		// Switch to external database
		\Drupal\Core\Database\Database::setActiveConnection('external');
		// Get a connection going
		$db = \Drupal\Core\Database\Database::getConnection();
	}
	
	public function setActiveDBConnection() {
		//Get current database connection
		\Drupal\Core\Database\Database::setActiveConnection();
		// Get a connection going
		$db = \Drupal\Core\Database\Database::getConnection();
	}
	
	/**
	 *  Get external source data object
	 */
	public function getExternalSourceDataObject($entity_type, $content_type, $field_type) {
		$sourceArrayBuilder = [];
		$imageFieldNames = $this->getImageFieldNames($entity_type, $content_type, $field_type);
		$fileManagedTableData = \Drupal\image_migration\SourceDBHandler::getFileManagedTableData();
		$fileUsageTableData = \Drupal\image_migration\SourceDBHandler::getFileUsageTableData();
		
		$sourceArrayBuilder[$content_type]['fileManaged'] = $fileManagedTableData; 
		$sourceArrayBuilder[$content_type]['fileUsage'] = $fileUsageTableData;
		
		// Add entries for entity that refered this field images of the content type.
		$fieldDataFieldTableData = [];
		$fieldRevisionFieldTableData = [];
		foreach ($imageFieldNames as $fieldName) {
			$fieldDataField = 'field_data_'.$fieldName;
			$fieldRevisionDataField = 'field_revision_'.$fieldName;
			$fieldDataFieldTableData[$fieldName] = \Drupal\image_migration\SourceDBHandler::getFieldDataFieldTableData($fieldDataField, $fieldName, $content_type);
			$fieldRevisionFieldTableData[$fieldName] = \Drupal\image_migration\SourceDBHandler::getFieldDataFieldTableData($fieldRevisionDataField, $fieldName, $content_type);
		}
		
		$sourceArrayBuilder[$content_type]['fieldDataField'] = $fieldDataFieldTableData;
		$sourceArrayBuilder[$content_type]['fieldRevisionField'] = $fieldRevisionFieldTableData;
		
		return $sourceArrayBuilder;
	}
	
	/**
	 * 
	 */
	public function setInternalDestinationObject($sourceArray, $type) {
		$file_managed_status = \Drupal::service('image_migration.checklist')->setFileTableDone('file_managed');
		$file_usage_status = \Drupal::service('image_migration.checklist')->setFileTableDone('file_usage');
		if($file_managed_status) {
			\Drupal\image_migration\DestinationDBHandler::setFileManagedTableData($sourceArray[$type]['fileManaged']);
		}
		if ($file_usage_status) {
			\Drupal\image_migration\DestinationDBHandler::setFileUsageTableData($sourceArray[$type]['fileUsage']);
		}
		
		$type_status = \Drupal::service('image_migration.checklist')->setDone($type);
		if($type_status) {
			\Drupal\image_migration\DestinationDBHandler::setFieldDataFieldTableData($sourceArray[$type]['fieldDataField']);
			\Drupal\image_migration\DestinationDBHandler::setFieldRevisionFieldTableData($sourceArray[$type]['fieldRevisionField']);
		}
   }
   
   /**
    * get field names
    */
   public function getImageFieldNames($entity_type, $content_type, $field_type) {
   		$imageFieldNames = [];
   		$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $content_type);
   		foreach ($fields as $field) {
   			if($field->getType() == $field_type) {
   				$imageFieldNames[] = $field->getName();
   			}
   		}
   		return $imageFieldNames;
   }
   
   public function checkOneTimeCall() {
   		$flag = $this->config('image_migration.settings')->get('one_time_call');
   		if($flag) {
   			
   		}
   }
}
