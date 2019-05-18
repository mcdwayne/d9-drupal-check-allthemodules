<?php
/**
 * contains Drupal\bulk_content_operation\BulkContentOperationController
 */
namespace Drupal\bulk_content_operation\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\bulk_content_operation\PHPExcelOperations;

class BulkContentOperationController extends ControllerBase {
	public function export() {
		$message = PHPExcelOperations::export();
		return array (
				'#markup' => t($message),
		);
	}
	
	/**
	 * Download Template
	 */
	public function downloadTemplate() {
		$message = PHPExcelOperations::downloadTemplate();
		return array (
				'#markup' => t($message),
		);
	}
}
