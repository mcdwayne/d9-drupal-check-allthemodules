<?php
/**
 * @file
 * contains \Drupal\image_migration\SourceDBHandler
 */
namespace Drupal\image_migration;

class SourceDBHandler {
	/**
	 * 
	 * @return fileManaged
	 */
	public static function getFileManagedTableData () {
		$fileManaged = [];
		$results = db_select('file_managed', 'fm')->fields('fm')->execute()->fetchAll();
		$array = array();
		foreach ($results as $result) {
			$array['fid'] = $result->fid;
			$array['uid'] = $result->uid;
			$array['filename'] = $result->filename;
			$array['uri'] = $result->uri;
			$array['filemime'] = $result->filemime;
			$array['filesize'] = $result->filesize;
			$array['status'] = $result->status;
			$array['timestamp'] = $result->timestamp;
			
			$fileManaged[] = $array;
		}
		return $fileManaged;
	}
	
	/**
	 * 
	 * @return fileUsage
	 */
	public static function getFileUsageTableData () {
		$fileUsage = [];
		$results = db_select('file_usage', 'fu')->fields('fu')->execute()->fetchAll();
		$array = array();
		foreach ($results as $result) {
			$array['fid'] = $result->fid;
			$array['module'] = $result->module;
			$array['type'] = $result->type;
			$array['id'] = $result->id;
			$array['count'] = $result->count;
			
			$fileUsage[] = $array;
		}
		return $fileUsage;
	}
	
	/**
	 * 
	 * @param unknown $dataTable
	 */
	public static function getFieldDataFieldTableData ($dataTable, $fieldName, $content_type) {
		$fieldDataTable = [];
	    
		$result = db_select($dataTable, 'fd')->fields('fd')->condition('bundle', $content_type)->execute();
		$array = array();
		while ($record = $result->fetchAssoc()) {
			$array['entity_type'] = $record['entity_type'];
			$array['bundle'] = $record['bundle'];
			$array['deleted'] = $record['deleted'];
			$array['entity_id'] = $record['entity_id'];
			$array['revision_id'] = $record['revision_id'];
			$array['language'] = $record['language'];
			$array['delta'] = $record['delta'];
			$array[$fieldName.'_fid'] = $record[$fieldName.'_fid'];
			$array[$fieldName.'_alt'] = $record[$fieldName.'_alt'];
			$array[$fieldName.'_width'] = $record[$fieldName.'_width'];
			$array[$fieldName.'_height'] = $record[$fieldName.'_height'];
			
			$fieldDataTable[] = $array;	
		}
		
		return $fieldDataTable;
	}
}