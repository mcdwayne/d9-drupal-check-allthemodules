<?php
/**
 * contains \Drupal\bulk_content_operation\PHPExcelGenericOperations
 */
namespace Drupal\bulk_content_operation;

class PHPExcelGenericOperations {
	
	public static function arrayBuilder($entity_type_id ,$contentType) {
		$entity = $entity_type_id;
		$contentName = $contentType;
		$data = array();
		$title = array();
		$bundleFields = array();
		foreach (\Drupal::entityManager()->getFieldDefinitions($entity, $contentName) as $field_name => $field_definition) {
			if (!empty($field_definition->getTargetBundle())) {//print"<pre>";print_r($field_definition->getType());
				if($field_definition->getLabel() != 'Comments') {
					
					$fieldType = $field_definition->getType();
					switch ($fieldType) {
						case 'text_with_summary':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name;
							$bundleFields[$entity_type_id][$field_name.'|summary'] = $field_name.'_summary';
							$bundleFields[$entity_type_id][$field_name.'|format'] = $field_name.'_format';
							break;
						case 'text_long':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name;
							$bundleFields[$entity_type_id][$field_name.'|format'] = $field_name.'_format';
							break;
						case 'image':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name.'_target_id';
							$bundleFields[$entity_type_id][$field_name.'|alt'] = $field_name.'_alt';
							$bundleFields[$entity_type_id][$field_name.'|image_title'] = $field_name.'_title';
							$bundleFields[$entity_type_id][$field_name.'|image_name'] = $field_name.'_name';
							break;
						case 'link':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name.'_uri';
							$bundleFields[$entity_type_id][$field_name.'|link_title'] = $field_name.'_title';
							break;
						case 'text':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name;
							$bundleFields[$entity_type_id][$field_name.'|format'] = $field_name.'_format';
							break;
						case 'entity_reference':
							$bundleFields[$entity_type_id][$field_name] = $field_name.'_target_id';
							break;
						case 'file':
							$bundleFields[$entity_type_id][$field_name.'|'.$field_definition->getType()] = $field_name.'_target_id';
							$bundleFields[$entity_type_id][$field_name.'|display'] = $field_name.'_display';
							$bundleFields[$entity_type_id][$field_name.'|description'] = $field_name.'_description';
							break;
						default:
							$bundleFields[$entity_type_id][$field_name] = $field_name;
					}
				}
			}
		}
		if (!in_array("Title", $bundleFields['node'])){
			$title = array('title' => 'title');
			$bundleFields['node'] = array_merge($title, $bundleFields['node']);
		}
		$commonFieldsArray = array('nid' =>'nid','type'=>'type','uid' => 'uid', 'status'=> 'status','langcode' => 'langcode','promote' => 'promote','sticky' => 'sticky');
		$data = array_merge($commonFieldsArray, $bundleFields['node']);
		return $data;
	}

	public static function prepareSpreadSheet() {
		$spreadsheet = new \PHPExcel();
		
		//Set properties
		$spreadsheet->getProperties()
		->setCreator('Test')
		->setTitle("PHPExcel Demo")
		->setDescription('A demo to show how to use PHPExcel to manipulate an Excel file')
		->setSubject('PHP Excel manipulation');
		
		//Add some data
		$spreadsheet->setActiveSheetIndex(0);
		
		return $spreadsheet;
	}
	
}