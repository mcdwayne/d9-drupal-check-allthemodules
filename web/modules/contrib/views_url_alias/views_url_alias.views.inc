<?php

/**
 * @file
 * Provide views data and handler information for Views URL alias module.
 */

use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Implements hook_views_data().
 */
function views_url_alias_views_data() {
  $real_table_name = 'views_url_alias';


  // Define table group
  $data[$real_table_name]['table']['group'] = 'Alias';
  $data[$real_table_name]['table']['provider'] = 'views_url_alias';

  $data[$real_table_name]['table']['base'] = [
    'field' => 'entity_id',
    'title' => t('URL alias'),
    'help' => t('Mapping table contains entity_ids and their associated path aliases.'),
    'weight' => -10,
  ];

  $data[$real_table_name]['entity_id'] = [
    'title' => t('id of the entity'),
    'help' => t('ID of the entity this alias is for.'),
  ];
  $data[$real_table_name]['alias'] = [
    'title' => t('URL alias'),
    'help' => t('The alias for a Entity.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];


  /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manage */
  $entity_type_manage = \Drupal::service('entity_type.manager');

  /** @var ConfigEntityType[] $entity_definitions */
  $entity_definitions = $entity_type_manage->getDefinitions();

  foreach ($entity_definitions as $entity_definition) {
    $reflectedClass = new ReflectionClass($entity_definition->getClass());
    if ($reflectedClass->isSubclassOf('\Drupal\Core\Entity\ContentEntityBase')) {
      $type = $entity_definition->id();
      $data_table = $entity_definition->getDataTable();
      $entity_id = $entity_definition->getKey('id');
      $langcode = $entity_definition->getKey('langcode');
      $data[$data_table]['views_url_alias']['relationship'] = [
        'title' => t($entity_definition->getLabel() . ' URL Alias'),
        'label' => t($entity_definition->getLabel() . ' URL Alias'),
        'help' => t('Relate '.  $entity_definition->getLabel() .  ' with it\'s URL Alias '),
        'base' => $real_table_name,
        'base field' => 'entity_id',
        'real field' => $entity_id,
        'id' => 'standard',
        'extra' => [
          [
            'field' => 'entity_type',
            'value' => $type,
          ],
          [
            'left_field' => $langcode,
            'field' => 'langcode',
          ],
        ],
      ];
    }
  }

  return $data;
}
