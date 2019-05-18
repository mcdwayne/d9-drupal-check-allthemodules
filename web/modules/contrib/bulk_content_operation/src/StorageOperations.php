<?php

/**
 * contains \Drupal\bulk_content_operation\StorageOperations
 * 
 */
namespace Drupal\bulk_content_operation;

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Connection;

class StorageOperations {
	protected $connection;
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}
	public static function contentOperations($contentType, $fields) {
		$data = array ();
		$contentTypeName = $contentType;
		
		$nodeFieldNames = $fields;
		$nodes = \Drupal::entityTypeManager ()->getStorage ( 'node' )->loadByProperties ( [
				'type' => $contentTypeName
		] );
		
		$i = 0;
		
		foreach ( $nodes as $key => $fieldData ) {
			foreach ( $nodeFieldNames as $fieldName ) {
				$imageName = '';
				if (strpos ( $fieldName, '|' ) !== false) {
					$seprator = explode ( "|", $fieldName );
					if (isset ( $seprator [1] )) {
						$val = $seprator [1];
						
						switch ($val) {
							case "text_with_summary":
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['value'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['summary'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['format'];
								break;
							case "text_long":
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['value'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['format'];
								break;
							case "text":
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['value'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['format'];
								break;
							case "image":
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								$fid = $arrayData [0] ['target_id'];
								if (! empty ( $fid )) {
									$file = \Drupal\file\Entity\File::load ( $fid );
									$path = $file->getFileUri ();
									$imageName = substr ( strrchr ( $path, '/' ), 1 );
								}
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['target_id'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['alt'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['title'];
								$data ['Worksheet1'] [$i] [] = $imageName;
								break;
							case "file":
								$fid ='';
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								$fid = $arrayData [0] ['target_id'];
								$data ['Worksheet1'] [$i] [] = $fid;
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['display'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['description'];
								break;
							case "link":
								$arrayData = $fieldData->get ( $seprator [0] )->getValue ();
								
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['uri'];
								$data ['Worksheet1'] [$i] [] = $arrayData [0] ['title'];
								break;
						}
					}
				} else {
					$data ['Worksheet1'] [$i] [] = $fieldData->get ( $fieldName )->getString ();
				}
			}
			$i ++;
		}
		return $data;
	}
	
	public static function operateNode(array $values, $assetfoldername) {
		$importValues = StorageOperations::setImportValuesInFormat($values, $assetfoldername);
		if(!empty($importValues)) {
			$node = Node::load ( $values ['nid'] );
			if (isset ( $node )) {
				StorageOperations::updateNode ( $node, $importValues );
			} else {
				StorageOperations::createNode ( $importValues );
			}
		}
	}
	
	protected static function createNode(array $importValues) {	
		$node = Node::create ( $importValues );
		$node->save ();
	}
	
	
	protected static function updateNode(Node $node, array $importValues) {
		foreach($importValues as $key => $value) {
			$node->set($key, $value);
		}
		$node->save ();
	}
	
	protected static function setImportValuesInFormat(array $values, $assetfoldername) {
		$importValues = [];
		$fieldList = \Drupal::service ( 'bulk_content_operation.fieldmanager' )->getEntityTypeFields ( $values ['type'], 'node' );
		if (! empty ( $fieldList )) {
			foreach ( $fieldList as $field ) {
				if (StorageOperations::importValueChecker ( $field ['id'] )) {
					$importValues [$field ['id']] = StorageOperations::importValueParser ( $field, $values, $assetfoldername );
				}
			}
		}
		return $importValues;
	}
	
	
	protected static function importValueParser($key, array $value, $assetfoldername) {
		if (! \Drupal::service ( 'bulk_content_operation.fieldmanager' )->isCustomField ( $key ['id'] )) {
			return $value [$key ['id']];
		} else {
			// return value build for custom fields.
			return \Drupal::service ( 'bulk_content_operation.fieldmanager' )->customFieldValueBuilder ( $key, $value, $assetfoldername );
		}
	}
	
	
	protected static function importValueChecker($key) {
		$ignoreKeys = array (
				'nid',
				'vid',
				'uuid',
				'created',
				'changed',
				'revision_timestamp',
				'revision_uid',
				'revision_log',
				'revision_translation_affected',
				'default_langcode',
				'path',
				'comment',
				'comment_node_article',
		);
		if (in_array ( $key, $ignoreKeys )) {
			return FALSE;
		}
		return TRUE;
	}
}