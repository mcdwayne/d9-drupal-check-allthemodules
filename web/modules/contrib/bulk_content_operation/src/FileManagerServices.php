<?php
/**
 * @filesource
 * contains Drupal\bulk_content_operation\FileManagerServices
 */
namespace Drupal\bulk_content_operation;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\file\Entity\File;
use Drupal\bulk_content_operation\BulkContentOperationData;

class FileManagerServices {
	public function getFolderName(array $fid) {
		$fid = $fid[0];
		$file = File::load($fid);
		return $file->getFilename();
	}
	
	public static function manageRedirection ($status) {
		drupal_set_message($status);
		file_unmanaged_delete_recursive(BulkContentOperationData::DEFAULT_IMPORT_DIRECTORY_PATH);
		$response = new RedirectResponse(\Drupal::url('system.admin_content'));
		$response->send();
	}
	
	public static function fileImportValidator($filepath) {
		if (file_exists ( DRUPAL_ROOT . '/' . $filepath )) {
			if (is_readable ( $filepath )) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
}