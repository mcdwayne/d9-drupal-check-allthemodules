<?php
namespace Drupal\content_profile_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Utility\Error;


class ContentProfileFieldsExport extends ControllerBase {


	public function getFieldsByKeys($value) {
		return strpos($value,"field_") === 0;
	}


	public function generateFields() {


                $configObjetContentProfile = \Drupal::config('content_profile_export.settings');
                $contentTypeName = $configObjetContentProfile->get('content_type_name');
                $profileTypeName = $configObjetContentProfile->get('profile_type_name');


		$bundle_fields_object = \Drupal::getContainer()->get('entity_field.manager');

		$bundle_fields_object->clearCachedFieldDefinitions();

		$bundle_fields = $bundle_fields_object->getFieldDefinitions('node', $contentTypeName);

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


			$field['entity_type'] = 'profile';
			$field['id'] = $value;
			$field['field_name'] = $value;


			$field_definition['id'] = $value;
			$field_definition['entity_type'] = 'profile';
			$field_definition['bundle'] = $profileTypeName;
			$field_definition['field_name'] = $value;

			if(isset($field_definition['settings']['on_label']) && $field_definition['settings']['on_label'] == 'On')
				$field_definition['settings']['on_label'] = $field_definition['label'];




                         //Getting the existing Field If exists
			$getExistingFieldStorage = $getObjectField->load('profile'.'.'. $id);
			if(empty($getExistingFieldStorage)) {
				try {
					FieldStorageConfig::create($field)->save();
				} catch(\Exception $exception) {
                                        \Drupal::logger('content_profile_export_create_error')->notice($exception);
				}
			}
                        else
                          \Drupal::logger('content_profile_export')->notice("Field Storage profile.$id already exists");



                        
                        //Getting the existing Instance of the Field in Bundle
			$getExistingInstanceBundle = $getObjectFieldInstance->load('profile'.'.'.$profileTypeName.'.'.$id);

	                if(empty($getExistingInstanceBundle)) {
                                 try {
					FieldConfig::create($field_definition)->save();
			         } catch(\Exception $exception) {
                                 \Drupal::logger('content_profile_export_instance_error')->notice($exception); 
			         }
                        }
                        else
                           \Drupal::logger('content_profile_export')->notice("Field Instance 'profile.$profileTypeName.$id' already exists");
                       

		}



	}

} 
