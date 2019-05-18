<?php
/**
 * @filesource
 * contains \Drupal\bulk_content_operation\FieldManagerServices
 */
namespace Drupal\bulk_content_operation;

class FieldManagerServices {
	public static function getEntityTypeFields($contentType) {
		$fieldList = [];
		$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType);
		foreach ($fields as $field) {
			$fieldData = [];
			$fieldData['id'] = $field->getName();
			$fieldData['type'] = $field->getType();
			$fieldList[] = $fieldData;
		}
		
		return $fieldList;     
	}
	
	public static function isCustomField ($machine_name) {
		$flag = substr($machine_name, 0,5);
		if($flag == 'field' || $machine_name == 'body') {
			return TRUE;
		}
		return FALSE;
	}
	
	public static function customFieldValueBuilder($field, array $importValues, $assetfoldername) {
		$fieldValueBuilder = \Drupal::service('bulk_content_operation.fieldvaluebuilder');
		switch ($field['type']) {
			case 'image':
				return $fieldValueBuilder->fileFieldValueBuilder($field['id'], $importValues, $assetfoldername,'image');
			case 'entity_reference' :
				return $fieldValueBuilder->entityReferenceFieldValueBuilder($field['id'], $importValues, $assetfoldername);
			case 'text_with_summary' :
				return $fieldValueBuilder->textWithSummaryFieldValueBuilder($field['id'], $importValues);
			case 'link' :
				return $fieldValueBuilder->linkFieldValueBuilder($field['id'], $importValues);
			case 'file' :
				return $fieldValueBuilder->fileFieldValueBuilder($field['id'], $importValues, $assetfoldername,'file');
			default:
				return $fieldValueBuilder->defaultFieldValueBuilder($field['id'], $importValues);
		}
	}	
}