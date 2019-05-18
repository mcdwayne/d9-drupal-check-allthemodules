<?php

/**
 * @file
 * Drush commands for migrating from Ad Integration to Advertising Entity.
 */

/**
 * Implements hook_drush_command().
 */
function ad_entity_ad_integration_drush_command() {
  $commands = [];

  // Check all translations for available updates.
  $commands['shovel-to-targeting-context'] = [
    'description' => 'Shovels field values from the ad_integration field to the ad_entity context field. Converts the Ad Integration values to key-value pairs for targeting. Configures field config to have default ad integration values as default field values.',
    'arguments' => [
      'entity_type' => "The affected entity type, either 'node' or 'taxonomy_term'",
      'bundle' => "The bundle, e.g. article or channel.",
      'ad_integration_field' => "Machine name of the entity's ad_integration field.",
      'ad_entity_field' => "Machine name of the entity's ad_entity field. Make sure you have attached this field to the entity type before.",
    ],
  ];

  return $commands;
}

/**
 * Shovels field values from the ad_integration field to the ad_entity field.
 *
 * Converts the Ad Integration values to
 * key-value pairs of targeting.
 * Configures field config to have default
 * ad integration values as default field values.
 *
 * @param string $entity_type
 *   The entity type, e.g. node or taxonomy_term.
 * @param string $bundle
 *   The bundle, e.g. article or channel.
 * @param string $ad_integration_field
 *   Machine name of the entity's ad_integration field.
 * @param string $ad_entity_field
 *   Machine name of the entity's ad_entity field.
 */
function drush_ad_entity_ad_integration_shovel_to_targeting_context($entity_type, $bundle, $ad_integration_field, $ad_entity_field) {
  $db = \Drupal::database();
  if (!isset($entity_type, $bundle, $ad_integration_field, $ad_entity_field)) {
    drush_log('Invalid arguments supplied, aborting.', 'error');
    return;
  }

  $field_config = \Drupal::configFactory()
    ->getEditable('field.field.' . $entity_type . '.' . $bundle . '.' . $ad_entity_field);
  if (!$field_config) {
    drush_log('No Advertising context field definition for ' . $entity_type . ' of type ' . $bundle . ' found, aborting.', 'error');
    return;
  }
  $default_value = $field_config->get('default_value');
  $ad_integration_settings = \Drupal::config('ad_integration.settings');
  if (empty($default_value)) {
    $default_value = [
      [
        'context' => [
          'context_plugin_id' => 'targeting',
          'apply_on' => [],
          'context_settings' => [
            'targeting' => [
              'targeting' => [
                'unit1' => $ad_integration_settings->get('adsc_unit1_default'),
                'unit2' => $ad_integration_settings->get('adsc_unit2_default'),
                'unit3' => $ad_integration_settings->get('adsc_unit3_default'),
              ],
            ],
          ],
        ],
      ],
    ];
    $field_config->set('default_value', $default_value);
    $field_config->save();
    drush_print('Updated default values for field config.');
  }
  else {
    drush_print('Default values for Advertising context field are already set, thus skipping the update of default values.');
  }

  $records = ['not_empty'];
  $last_id = '';
  while (!empty($records)) {
    $records = $db->query("SELECT * FROM {" . $entity_type . "__" . $ad_integration_field . "} AS ad_integration 
    WHERE ad_integration.bundle = :bundle 
    AND NOT EXISTS (SELECT context.entity_id FROM {" . $entity_type . "__" . $ad_entity_field . "} AS context WHERE ad_integration.entity_id = context.entity_id) 
    LIMIT 10", [':bundle' => $bundle]);
    foreach ($records as $record) {
      $context_value = [
        'context_plugin_id' => 'targeting',
        'apply_on' => [],
        'context_settings' => [
          'targeting' => [
            'targeting' => [
              'unit1' => !empty($record->{$ad_integration_field . '_adsc_unit1'}) ? $record->{$ad_integration_field . '_adsc_unit1'} : $ad_integration_settings->get('adsc_unit1_default'),
              'unit2' => !empty($record->{$ad_integration_field . '_adsc_unit2'}) ? $record->{$ad_integration_field . '_adsc_unit2'} : $ad_integration_settings->get('adsc_unit2_default'),
              'unit3' => !empty($record->{$ad_integration_field . '_adsc_unit3'}) ? $record->{$ad_integration_field . '_adsc_unit3'} : $ad_integration_settings->get('adsc_unit3_default'),
            ],
          ],
        ],
      ];
      if (!empty($record->{$ad_integration_field . '_adsc_keyword'})) {
        $keywords = explode(',', $record->{$ad_integration_field . '_adsc_keyword'});
        foreach ($keywords as $keyword) {
          $context_value['context_settings']['ad_tech_targeting']['targeting']['category'][] = trim($keyword);
        }
      }
      $fields = [
        'entity_id' => $record->entity_id,
        'bundle' => $record->bundle,
        'deleted' => $record->deleted,
        'revision_id' => $record->revision_id,
        'langcode' => $record->langcode,
        'delta' => $record->delta,
        $ad_entity_field . '_context' => serialize($context_value),
      ];
      $db->insert($entity_type . '__' . $ad_entity_field)
        ->fields($fields)->execute();
      if ($entity_type == 'node') {
        $db->insert('node_revision__' . $ad_entity_field)
          ->fields($fields)->execute();
      }
      $last_id = $record->entity_id;
    }
    drush_print('Shoveled field values for another 10 items (last entity id: ' . $last_id . ')...');
    usleep(500000);
  }
  drush_print('Finished shoveling. Last entity id: ' . $last_id);
}
