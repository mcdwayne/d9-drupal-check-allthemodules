<?php

/**
 * @file
 * This file describes hooks provided by entityconnect.
 */

/**
 * Allow modules to alter the list of exclude forms.
 *
 * If you don't want specific forms to be processed by Entityconnect.
 *
 * @param array $exclude_forms
 *   Array of forms that should not be processed.
 *
 * @see entityconnect_child_form_alter()
 */
function hook_entityconnect_exclude_forms_alter(array &$exclude_forms) {
  $exclude_forms = array(
    'search_block_form',
    'page_node_form',
  );
}

/**
 * Allow modules to add fields for entitytconnect to target.
 *
 * That will display the "add" and "edit" buttons.
 *
 * Only entity_reference fields are supported at this time.
 *
 * @param array $ref_fields
 *   The fields that will be processed by entityconnect.
 */
function hook_entityconnect_ref_fields_alter(array &$ref_fields) {
}

/**
 * Allow modules to specify values returned by the widget.
 *
 * Only when the field type cannot be determined.
 *
 * @param array $data
 *   The data used for altering the input when returned to the parent.
 *
 *   $data = array(
 *     'data' => $cache_data,
 *     'widget_containter' => $widget_container,
 *     'widget_container_type' => $widget_container_type,
 *     'field_info' => $field_info,
 *     'element_value' => NULL
 *   );.
 *
 * @return mixed
 *    $data['element_value'] need to be set.
 */
function hook_entityconnect_return_form_alter(array &$data) {
  /*
   * $data['data'] : The cached data.
   * $data['element_value'] : Defaut value to apply on field.
   */
}

/**
 * Allow modules to alter the child(target entity) form.
 *
 * @param array $data
 *   The data to alter.
 *     $data = array(
 *      'form' => &$form,
 *      'form_state' => &$form_state,
 *      'form_id' => $form_id
 *      );.
 */
function hook_entityconnect_child_form_alter(array &$data) {
}

/**
 * Allow modules to alter the child form submit handler.
 *
 * @param array $data
 *   The data to alter.
 *     $data = array(
 *      'form' => &$form,
 *      'form_state' => &$form_state,
 *      'entity_type' => $entity_type,
 *      'data' => &$cache_data,
 *      );.
 */
function hook_entityconnect_child_form_submit_alter(array &$data) {
}

/**
 * Allow modules to create the render array for the add action.
 *
 * @param string $cache_id
 *   The id of the parent form cache.
 * @param string $entity_type
 *   The target entity_type.
 * @param array $acceptable_types
 *   The entity types that can be added/edited.
 */
function hook_entityconnect_add_info($cache_id, $entity_type, array $acceptable_types) {
}

/**
 * Allow modules to alter the render array for the add action.
 *
 * @param array $info
 *   Content and theme information to return.
 * @param array $context
 *   The context in which the information is added.
 *     $context = array(
 *      'cache_id' => $cache_id,
 *      'entity_type' => $entity_type, // Target entity type
 *      'acceptable_tpes' => $acceptable_types
 *     );.
 */
function hook_entityconnect_add_info_alter(array &$info, array $context) {
}

/**
 * Allow modules to create the render array for the edit action.
 *
 * @param string $cache_id
 *   The id of the parent form cache.
 * @param string $entity_type
 *   The target entity_type.
 * @param int $target_id
 *   The id of the entity to edit.
 */
function hook_entityconnect_edit_info($cache_id, $entity_type, $target_id) {
}

/**
 * Allow modules to alter the render array for the edit action.
 *
 * @param array $info
 *   Content and theme information to return.
 * @param array $context
 *   The context in which the information is edited.
 *     $context = array(
 *      'cache_id' => $cache_id,
 *      'entity_type' => $entity_type, // Target entity type
 *      'target_id' => $target_id
 *     );.
 */
function hook_entityconnect_edit_info_alter(array &$info, array $context) {
}

/**
 * Allows modules to change the target of the entityconnect action.
 *
 * @param array $data
 *   The data to alter.
 *     $data = array(
 *      'entity_type' => $entity_type,
 *      'acceptable_types' => $acceptable_types,
 *      'field_definition' => $fieldDefinition
 *      );.
 */
function hook_entityconnect_field_attach_form_alter(array &$data) {
}

/**
 * Allows module to change the parent form data that will be cached.
 *
 * @param array $data
 *   An array of data that will be cached.
 *     $data = array(
 *       'form'       => $form,
 *       'form_state' => $form_state,
 *       'dest'       => \Drupal::routeMatch(),
 *       'params'     => \Drupal::request()->query->all(),
 *       'field'      => $field,
 *       'field_info' => $fieldInfo,
 *       'key'        => $key,
 *       'add_child'  => $triggeringElement['#add_child'],
 *       'target_id'  => $target_id,
 *       'target_entity_type' => $entityType,
 *       'acceptable_types' => $acceptableTypes,
 *       'field_container' => $fieldContainer,
 *       'field_container_key_exists' => $keyExists,
 *    );.
 */
function hook_entityconnect_add_edit_button_submit_alter(array &$data) {
}
