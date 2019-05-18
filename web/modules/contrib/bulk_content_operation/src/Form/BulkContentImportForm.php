<?php
/**
 * Drupal\bulk_content_operation\Form\BulkContentImportForm;
 */
namespace Drupal\bulk_content_operation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bulk_content_operation\PHPExcelOperations;
use Drupal\bulk_content_operation\FileManagerServices;

class BulkContentImportForm extends FormBase {
	/**
	 *  function used to put form unique ID.
	 */
	public function getFormId() {
		return 'bulk_content_import_form';
	}
	
	/**
	 *  buildForm() used for creating a form
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		$form['import_assets'] = array(
				'#title' => $this->t('Choose a Zip File to Upload:'),
				'#description' => $this->t('Choose a file which contains list of contents'),
				'#type' => 'managed_file',
				'#upload_validators' => array('file_validate_extensions' => array('zip')),
				'#upload_location' => 'public://Excels/Type/',
		);
		
		$form['import_file'] = array(
				'#title' => $this->t('Choose a File to Import:'),
				'#description' => $this->t('Choose a file which contains list of contents'),
				'#type' => 'managed_file',
				'#required' => TRUE,
				'#upload_validators' => array('file_validate_extensions' => array('xls xlsx')),
				'#upload_location' => 'public://Excels/Type/',
		);
		
		$form['submit'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Import Contents'),
		);
		
		return $form;
	}
	/**
	 * Submit Function
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$assetfilename = $form_state->getValue('import_assets');
		$fileManager = \Drupal::service('bulk_content_operation.filemanager');
		$importfilename = $fileManager->getFolderName($form_state->getValue('import_file'));
		if(!empty($assetfilename)) {
			$assetfilename = $fileManager->getFolderName($form_state->getValue('import_assets'));
		} else {
			$assetfilename = FALSE;
		}
		$status = PHPExcelOperations::import($assetfilename,$importfilename );
		FileManagerServices::manageRedirection($status);
	}
}