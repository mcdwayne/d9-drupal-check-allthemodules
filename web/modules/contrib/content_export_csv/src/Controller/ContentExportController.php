<?php
namespace Drupal\content_export_csv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Drupal\node\Entity\Node;

class ContentExportController extends ControllerBase{
	/**
	* Get Content Type List
	*/
	public function getContentType(){
		$contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
		$contentTypesList = [];
		foreach ($contentTypes as $contentType) {
    		$contentTypesList[$contentType->id()] = $contentType->label();
		}
		return $contentTypesList;
	}

	/**
	* Gets NodesIds based on Node Type
	*/
	function getNodeIds($nodeType){		
		$entityQuery = \Drupal::entityQuery('node');
		$entityQuery->condition('status',1);
		$entityQuery->condition('type',$nodeType);
		$entityIds = $entityQuery->execute();
		return $entityIds;
	}

	/**
	* Collects Node Data
	*/
	function getNodeDataList($entityIds,$nodeType){
		$nodeData = Node::loadMultiple($entityIds);
 		foreach($nodeData as $nodeDataEach){
 		$nodeCsvData[] = implode(',',self::getNodeData($nodeDataEach,$nodeType)); 		
 		}
 		return $nodeCsvData;
	}

	/**
	* Gets Valid Field List
	*/
	function getValidFieldList($nodeType){
		$nodeArticleFields = \Drupal::entityManager()->getFieldDefinitions('node',$nodeType);
		$nodeFields = array_keys($nodeArticleFields);
		$unwantedFields = array('comment','sticky','revision_default','revision_translation_affected','revision_timestamp','revision_uid','revision_log','vid','uuid','promote');
		
		foreach($unwantedFields as $unwantedField){
			$unwantedFieldKey = array_search($unwantedField,$nodeFields);
			unset($nodeFields[$unwantedFieldKey]);	
		}
		return $nodeFields;		
	}

	/**
	* Gets Manipulated Node Data
	*/
	function getNodeData($nodeObject,$nodeType){
		$nodeData = array();		
		$nodeFields = self::getValidFieldList($nodeType);
		foreach($nodeFields as $nodeField){
			$nodeData[] = (isset($nodeObject->{$nodeField}->value)) ? '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->value)) . '"': ((isset($nodeObject->{$nodeField}->target_id)) ? '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->target_id)) . '"' : '"' . htmlspecialchars(strip_tags($nodeObject->{$nodeField}->langcode)) . '"');			

		}
		return $nodeData;				
	}

	/**
	* Get Node Data in CSV Format
	*/
	function getNodeCsvData($nodeType){
		$entityIds = self::getNodeIds($nodeType);
		$nodeCsvData = self::getNodeDataList($entityIds,$nodeType);
		return $nodeCsvData;
	}
}