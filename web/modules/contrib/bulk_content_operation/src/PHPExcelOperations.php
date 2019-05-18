<?php

/**
 * contains \Drupal\bulk_content_operation\PHPExcelOperations
 */
namespace Drupal\bulk_content_operation;
use Drupal\Core\Archiver\Zip;
use Drupal\bulk_content_operation\BulkContentOperationData;

class PHPExcelOperations {
	public static function downloadTemplate() {
		$contentType = isset ($_REQUEST['type']) ? $_REQUEST['type'] : '';
		$entityName = isset ($_REQUEST['entity']) ? $_REQUEST['entity'] : '';
		$fileName = 'template_'.$contentType.'_'.time();
		
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$fileName.xlsx");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		flush();
		
		module_load_include('inc', 'phpexcel');
		require(libraries_get_path('PHPExcel') . '/Classes/PHPExcel.php');
		require(libraries_get_path('PHPExcel') . '/Classes/PHPExcel/Writer/Excel2007.php');
		
		$user = \Drupal::currentUser();
		$userName = $user->getUsername();
		$bundle = $contentType;
		$entity_type_id = $entityName;
		/*
		 *  Build Node type fields array.
		 */
		$fieldsData = PHPExcelGenericOperations::arrayBuilder($entity_type_id ,$bundle);
		$header = array_values($fieldsData);
		$spreadsheet = new \PHPExcel();
		$spreadsheet->getProperties()
		->setCreator($userName)
		->setLastModifiedBy($userName)
		->setTitle("Content Data")
		->setDescription('Download content type template')
		->setSubject('Content Template')
		->setKeywords('content fields data')
		->setCategory('Content Data');
		
		//Add some data
		$spreadsheet->setActiveSheetIndex(0);
		$worksheet = $spreadsheet->getActiveSheet();
		//Rename sheet
		$worksheet->setTitle('WorkSheet 1');
		$row = 1;
		$col = 0;
		foreach($header as $key=>$value) {
			$worksheet->setCellValueByColumnAndRow($col, $row, $value);
			$col++;
		}
		$writer = new \PHPExcel_Writer_Excel2007($spreadsheet);
		
		ob_end_clean();
		$writer->save('php://output');
		exit();
	}
	
	public static function export() {
		require(libraries_get_path('PHPExcel') . '/Classes/PHPExcel.php');
		require(libraries_get_path('PHPExcel') . '/Classes/PHPExcel/Writer/Excel2007.php');
		
		$contentType = isset ($_REQUEST['type']) ? $_REQUEST['type'] : '';
		$entityName = isset ($_REQUEST['entity']) ? $_REQUEST['entity'] : '';
		$fileName = $contentType.'_'.time();
		
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$fileName.xlsx");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		flush();
		
		module_load_include('inc', 'content_export_import', 'content_export_import.data');
		$spreadsheet = PHPExcelGenericOperations::prepareSpreadSheet();
		$worksheet = $spreadsheet->getActiveSheet();
		//Rename sheet
		$worksheet->setTitle('Worksheet 1');
		
		$headers = array();
		
		/*
		 *  Build Node type fields array.
		 */
		$fieldsData = PHPExcelGenericOperations::arrayBuilder($entityName ,$contentType);
		
		$fields = array_keys($fieldsData);
		$headers = array_values($fieldsData);
		/*
		 *  Get content type node data.
		 */
		$nodeData = StorageOperations::contentOperations($contentType,$fields);
		/*
		 *  Get Exported file path.
		 */
		
		//$path  = PHPExcelGenericOperations::getPath();
		
		$row = 1;
		$col = 0;
		foreach($headers as $key=>$value) {
			$worksheet->setCellValueByColumnAndRow($col, $row, $value);
			$col++;
		}
		
		foreach($nodeData as $key=>$values) {
			$rows = 2;
			foreach($values as $keyData=>$valuess) {
				$cols = 0;
				foreach($valuess as $contentData){
					$worksheet->setCellValueByColumnAndRow($cols, $rows, $contentData);
					$cols++;
				}
				$rows++;
			}
		}
		$writer = new \PHPExcel_Writer_Excel2007($spreadsheet);
		
		ob_end_clean();
		$writer->save('php://output');
		exit();
	}
	
	
	/**
	 * Import Operation
	 */
	public static function import ($assetfilename,$importfilename) {
		if($assetfilename) {
			PHPExcelOperations::importWithAssets($assetfilename,$importfilename);
			return BulkContentOperationData::STATUS_WITH_ASSETS;
		}
		PHPExcelOperations::importWithoutAssets($importfilename);
		return BulkContentOperationData::STATUS_WITHOUT_ASSETS;
	}
	
	protected static function importWithAssets($assetfilename,$importfilename) {
		$assetfolder = explode( '.', $assetfilename );
		$assetfoldername =  $assetfolder[0];
		PHPExcelOperations::unzipOperation ($assetfilename);
		PHPExcelOperations::importOperation ($importfilename, $assetfoldername);
		drupal_flush_all_caches();
	}
	
	protected static function importWithoutAssets($importfilename) {
		PHPExcelOperations::importOperation ($importfilename);
		drupal_flush_all_caches();
	}
	
	protected static function importOperation ($importfilename, $assetfoldername) {
		$path = BulkContentOperationData::DEFAULT_IMPORT_DIRECTORY_PATH.$importfilename;
		if(FileManagerServices::fileImportValidator($path)) {
			$nodes = PHPExcelOperations::importExcelParser($path);
			if(!empty($nodes)) {
				foreach($nodes as $node) {
					StorageOperations::operateNode($node, $assetfoldername);
				}
			}
		}
	}
	
	
	protected static function unzipOperation($assetfilename) {
		$sourcefile = BulkContentOperationData::DEFAULT_IMPORT_DIRECTORY_PATH.$assetfilename;
		$despath = BulkContentOperationData::DEFAULT_IMPORT_DIRECTORY_PATH;
		$zip = 	new Zip($sourcefile);
		$zip->extract($despath);
	}
	
	protected static function importExcelParser($path) {
		module_load_include ( 'inc', 'phpexcel' );
		$result = phpexcel_import ( $path );
		if (is_array($result)) {
			return $result[0];
		} else {
			return [];
		}
	}
}