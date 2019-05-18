<?php

namespace Drupal\content_export_csv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_export_csv\Controller\ContentExportController;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;

class ContentExportForm extends FormBase{
	/**
	* {@inheritdoc}
	*/
	public function getFormId(){
		return 'content_export_csv_form';
	}

	/**
   	* {@inheritdoc}
   	*/
	public function buildForm(array $form,FormStateInterface $form_state){
		$export_object = new ContentExportController;
		$form['content_type_list'] = [
			'#title'=> $this->t('Content Type'),
			'#type'=> 'select',
			'#options'=> $export_object->getContentType()
		];

		$form['export'] = [
			'#value'=> 'Export',
			'#type'=> 'submit' 
		]; 

		return $form;
	}

	/**
   	* {@inheritdoc}
   	*/
	public function submitForm(array &$form,FormStateInterface $form_state){
		global $base_url;
		$export_object = new ContentExportController;		
		$nodeType = $form_state->getValue('content_type_list');
		$csvData = $export_object->getNodeCsvData($nodeType);
		$private_path = PrivateStream::basepath();
		$public_path = PublicStream::basepath();
		$file_base = ($private_path) ? $private_path : $public_path;
		$filename = 'content_export'. time(). '.csv';
		$filepath = $file_base . '/' . $filename;			
		$csvFile = fopen($filepath, "w");		
		$fieldNames = implode(',',$export_object->getValidFieldList($nodeType));
		fwrite($csvFile,$fieldNames . "\n");
		foreach($csvData as $csvDataRow){
			fwrite($csvFile,$csvDataRow . "\n");
		}		
		fclose($csvFile);		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'. basename($filepath) . '";');
		header('Content-Length: ' . filesize($filepath));
		readfile($filepath);
		unlink($filepath);	
		exit;		
	}
}