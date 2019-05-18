<?php
/**
 * @file
 * Contains \Drupal\image_migration\CheckListServices.
 */

namespace Drupal\image_migration;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Defines a service for managing Database operations.
 */
class CheckListServices {
	/**
	 * Constructor.
	 */
	public function __construct() {
		
	}
	
	
	/**
	 *
	 * @param \Drupal\node\Entity\Node $node
	 */
	public function setFileTableDone($file_table) {
		if (!$this->isFileTableDone($file_table)) {
			$insert = Database::getConnection()->insert('image_migration_file_table_status');
			$insert->fields(array('filetable'), array($file_table));
			$insert->execute();
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 *
	 * @param \Drupal\node\Entity\Node $node
	 *
	 * @return bool
	 */
	public function isFileTableDone($file_table) {
		$select = Database::getConnection()->select('image_migration_file_table_status', 'imfts');
		$select->fields('imfts', array('filetable'));
		$select->condition('filetable', $file_table);
		$results = $select->execute();
		return !empty($results->fetchCol());
	}
	
	
	
	/**
	 *
	 * @param \Drupal\node\Entity\Node $node
	 */
	public function setDone($content_type) {
		if (!$this->isDone($content_type)) {
			$insert = Database::getConnection()->insert('image_migration_checklist');
			$insert->fields(array('entity_type', 'bundle', 'status'), array('node', $content_type, 1));
			$insert->execute();
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * 
	 *
	 * @param \Drupal\node\Entity\Node $node
	 *
	 * @return bool
	 * 
	 */
	public function isDone($content_type) {
		$select = Database::getConnection()->select('image_migration_checklist', 'imc');
		$select->fields('imc', array('bundle'));
		$select->condition('bundle', $content_type);
		$results = $select->execute();
		return !empty($results->fetchCol());
	}
	
	/**
	 * 
	 *
	 * @param \Drupal\node\Entity\Node $node
	 */
	public function delEnabled($content_type) {
		$delete = Database::getConnection()->delete('image_migration_checklist');
		$delete->condition('bundle', $content_type);
		$delete->execute();
	}
}
