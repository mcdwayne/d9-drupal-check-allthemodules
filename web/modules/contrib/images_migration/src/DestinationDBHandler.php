<?php
/**
 * @file
 * contains \Drupal\image_migration\SourceDBHandler
 */
namespace Drupal\image_migration;

class DestinationDBHandler {
	/**
	 *
	 * @return fileManaged
	 */
	public static function setFileManagedTableData ($fileManaged) {
		//Get Current Language Code of website
		$language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
		foreach ($fileManaged as $data) {
			$uuid_service = \Drupal::service('uuid');
			$uuid = $uuid_service->generate();
			db_insert('file_managed')->fields(array(
					'fid' => $data['fid'],
					'uuid' => $uuid,
					'langcode' => $language,
					'uid' => $data['uid'],
					'filename' => $data['filename'],
					'uri' => $data['uri'],
					'filemime' => $data['filemime'],
					'filesize' => $data['filesize'],
					'status' => $data['status'],
					'changed' => $data['timestamp'],
					'created' => $data['timestamp'],
			))->execute();
		}
	}
	
	/**
	 *
	 * @return fileUsage
	 */
	public static function setFileUsageTableData($fileUsage) {
		foreach ($fileUsage as $data) {
			db_insert('file_usage')
			->fields(array(
					'fid' => $data['fid'],
					'module' => $data['module'],
					'type' => $data['type'],
					'id' => $data['id'],
					'count' => $data['count'],
			))->execute();
		}
	}
	
	/**
	 *
	 * @param unknown $dataTable
	 */
	public static function setFieldDataFieldTableData ($fieldDataField) {
		foreach ($fieldDataField as $key => $value) {
			\Drupal\image_migration\DestinationDBHandler::setFieldDataField($value, $key);
		}
	}
	
	public static function setFieldDataField($fieldData, $fieldName) {
		//Get Current Language Code of website
		$language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
		$tableName = 'node__'.$fieldName;
		foreach ($fieldData as $data) {
			db_insert($tableName)
			->fields(array(
					'bundle' => $data['bundle'],
					'deleted' => $data['deleted'],
					'entity_id' => $data['entity_id'],
					'revision_id' => $data['revision_id'],
					'langcode' => $language,
					'delta' => $data['delta'],
					$fieldName.'_target_id' => $data[$fieldName.'_fid'],
					$fieldName.'_alt' => $data[$fieldName.'_alt'],
					$fieldName.'_title' => '',
					$fieldName.'_width' => $data[$fieldName.'_width'],
					$fieldName.'_height' => $data[$fieldName.'_height'],
			))->execute();
		}
	}
	
	/**
	 *
	 * @param unknown $dataTable
	 */
	public static function setFieldRevisionFieldTableData ($fieldDataField) {
		foreach ($fieldDataField as $key => $value) {
			\Drupal\image_migration\DestinationDBHandler::setFieldRevisionField($value, $key);
		}
	}
	
	public static function setFieldRevisionField($fieldData, $fieldName) {
		//Get Current Language Code of website
		$language =  \Drupal::languageManager()->getCurrentLanguage()->getId();
		$tableName = 'node_revision__'.$fieldName;
		foreach ($fieldData as $data) {
			db_insert($tableName)
			->fields(array(
					'bundle' => $data['bundle'],
					'deleted' => $data['deleted'],
					'entity_id' => $data['entity_id'],
					'revision_id' => $data['revision_id'],
					'langcode' => $language,
					'delta' => $data['delta'],
					$fieldName.'_target_id' => $data[$fieldName.'_fid'],
					$fieldName.'_alt' => $data[$fieldName.'_alt'],
					$fieldName.'_title' => '',
					$fieldName.'_width' => $data[$fieldName.'_width'],
					$fieldName.'_height' => $data[$fieldName.'_height'],
			))->execute();
		}
	}
}