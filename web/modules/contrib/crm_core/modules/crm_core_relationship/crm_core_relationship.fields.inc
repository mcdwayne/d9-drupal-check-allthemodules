<?php

/**
 * @file
 * CRM Core Relationships fields.
 *
 * @TODO: Unported.
 */

/**
 * Relationship field default values.
 *
 * @return array
 *   Return default fields definition that have to be attached to newly created
 *   relation type.
 *
 * @see field_create_field()
 */
function _crm_core_relationship_field_default_fields() {
  $fields = [];

  $fields[] = [
    'active' => '1',
    'cardinality' => '1',
    'deleted' => '0',
    'entity_types' => [],
    'field_name' => 'crm_core_relationship_status',
    'foreign keys' => [],
    'indexes' => [
      'value' => [
        0 => 'value',
      ],
    ],
    'locked' => '1',
    'module' => 'list',
    'settings' => [
      'allowed_values' => [
        0 => t('Inactive'),
        1 => t('Active'),
      ],
      'allowed_values_function' => '',
    ],
    'translatable' => '0',
    'type' => 'list_boolean',
  ];

  return $fields;
}

/**
 * Relationship field status instance.
 *
 * @param string $type
 *   Bundle type.
 *
 * @return array
 *   Array of field instances config.
 */
function _crm_core_relationship_field_status_instance($type) {
  $fields = [];
  $fields[] = [
    'bundle' => $type,
    'default_value' => [
      0 => [
        'value' => 1,
      ],
    ],
    'deleted' => '0',
    'description' => '',
    'display' => [
      'default' => [
        'label' => 'above',
        'module' => 'list',
        'settings' => [],
        'type' => 'list_default',
        'weight' => 1,
      ],
    ],
    'entity_type' => 'relation',
    'field_name' => 'crm_core_relationship_status',
    'label' => t('Status'),
    'required' => 0,
    'settings' => [
      'user_register_form' => FALSE,
    ],
    'widget' => [
      'active' => 1,
      'module' => 'options',
      'settings' => [
        'display_label' => 0,
      ],
      'type' => 'options_onoff',
      'weight' => '1',
    ],
  ];
  return $fields;
}
