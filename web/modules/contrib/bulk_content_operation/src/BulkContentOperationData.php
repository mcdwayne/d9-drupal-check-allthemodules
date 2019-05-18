<?php
/**
 * @filesource
 * contains \Drupal\bulk_content_operation\bulkContentOperationData
 */
namespace Drupal\bulk_content_operation;

class BulkContentOperationData {
	/**
	 * The Node Entity.
	 */
	const NODE_ENTITY = 'node';
	
	/**
	 * Status message with assets import.
	 */
	const STATUS_WITH_ASSETS = 'Import Operation Successfully Completed With Assets';
	
	/**
	 * Status message without assets imports.
	 */
	const STATUS_WITHOUT_ASSETS = 'Import Operations Successfully Completed';
	
	/**
	 * Default import directory path.
	 */
	const DEFAULT_IMPORT_DIRECTORY_PATH = 'sites/default/files/Excels/Type/';
}