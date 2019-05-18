<?php

/**
 * @file
 * Provide views data for content_entity_builder.module.
 */

/**
 * Implements hook_views_data().
 */
function content_entity_builder_views_data_alter(array &$data) {
  $content_types = \Drupal::entityTypeManager()->getStorage('content_type')->loadMultiple();
  foreach ($content_types as $content_type) {
    $entity_type = $content_type->id();

    foreach ($content_type->getBaseFields() as $base_field) {
      $field_type = $base_field->getFieldType();
      $list_types = ['list_integer', 'list_float', 'list_string'];
      if(in_array($field_type, $list_types)){
        $field_name =  $base_field->getFieldName();
	    $data[$entity_type][$field_name]['filter']['id'] = 'list_base_field';
	  }
    }
  }	

}
