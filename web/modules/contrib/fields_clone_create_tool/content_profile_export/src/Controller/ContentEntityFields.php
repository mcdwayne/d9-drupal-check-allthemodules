<?php
namespace Drupal\content_profile_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Utility\Error;


class ContentEntityFields extends ControllerBase {


	public function getFieldsByKeys($value) {
		return strpos($value,"field_") === 0;
	}


	public function generateFields() {


                $configObjetContentProfile = \Drupal::config('content_type_fields.settings');
                $sourceContentTypeName = $configObjetContentProfile->get('content_type_name_source');
                $destinationContentType  = $configObjetContentProfile->get('content_type_name_destination');


		$bundle_fields_object = \Drupal::getContainer()->get('entity_field.manager');

		$bundle_fields_object->clearCachedFieldDefinitions();

		$bundle_fields = $bundle_fields_object->getFieldDefinitions('node', $sourceContentTypeName);

		$bundle_fieldsKeys = array_keys($bundle_fields);

		$fieldNamesToExport = array_values(array_filter($bundle_fieldsKeys, array($this,"getFieldsByKeys")));



		$getObjectField = \Drupal::entityTypeManager()->getStorage('field_storage_config');
		$getObjectFieldInstance =  \Drupal::entityTypeManager()->getStorage('field_config');



		foreach($fieldNamesToExport as $value) {

			$field_definition = $bundle_fields[$value]->toArray();
			$field =  $getObjectField->load("node.$value")->toArray();


			foreach(array('uuid','id','dependencies') as $internalKeys) {
				unset($field[$internalKeys]);
				unset($field_definition[$internalKeys]);
			}


			$field['entity_type'] = 'node';
			$field['id'] = $value;
			$field['field_name'] = $value;


			$field_definition['id'] = $value;
			$field_definition['entity_type'] = 'node';
			$field_definition['bundle'] = $destinationContentType;
			$field_definition['field_name'] = $value;

			if(isset($field_definition['settings']['on_label']) && $field_definition['settings']['on_label'] == 'On') {
				$field_definition['settings']['on_label'] = $field_definition['label'];
}




                         //Getting the existing Field in custom content entity 
			$getExistingFieldStorage = $getObjectField->load($destinationContentType.'.'. $value);

			if(empty($getExistingFieldStorage)) {
				try {
					FieldStorageConfig::create($field)->save();
				} catch(\Exception $exception) {

                                        \Drupal::logger('content_profile_export_create_error')->notice($exception->getMessage());
				}
			}
                        else  {

                          \Drupal::logger('content_profile_export')->notice("Field Storage.$value already exists");
                         }


                        
                        //Getting the existing Instance of the Field in Destination Custom Entity 
			$getExistingInstanceBundle = $getObjectFieldInstance->load($destinationContentType.'.'.$destinationContentType.'.'.$value);


	                if(empty($getExistingInstanceBundle)) {
                                 try {
					FieldConfig::create($field_definition)->save();
			         } catch(\Exception $exception) {
                                 \Drupal::logger('content_profile_export_instance_error')->notice($exception->getMessage()); 
			         }
                        }
                        else {
                           \Drupal::logger('content_profile_export')->notice("Field Instance $destinationContentType.$destinationContentType.$value' already exists");      

             }	
	}
	}



} 
