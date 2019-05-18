<?php

/**
 * @file
 * Provide views data for entity_usage.module.
 */

/**
 * Implements hook_views_data().
 */
function entity_usage_views_data() {

  $data['entity_usage']['table']['group'] = t('Entity Usage');

  $data['entity_usage']['count'] = [
    'title' => t('Usage count'),
    'help' => t('How many times the target entity is referenced by the referencing entity.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function entity_usage_views_data_alter(array &$data) {

  $entity_types = \Drupal::entityTypeManager()->getDefinitions();

  // Provide a relationship for each entity type that has a base table.
  foreach ($entity_types as $type => $entity_type) {

    if (empty($data[$entity_type->getBaseTable()])) {
      continue;
    }

    if ($data_table = $entity_type->getBaseTable()) {
      $data[$data_table][$type . '_to_usage_entity'] = [
        'title' => t('Information about the usage of this @entity_type', ['@entity_type' => $entity_type->getLabel()]),
        'help' => t('Creates a relationship about this <em>@entity_type</em> and the entity_usage information that relates to it.', ['@entity_type' => $entity_type->getLabel()]),
        'relationship' => [
          'base' => 'entity_usage',
          'base field' => 't_id',
          'field' => $entity_type->getKey('id'),
          'id' => 'standard',
          'label' => t('Usage information (@entity_type)', ['@entity_type' => $entity_type->getLabel()]),
          'extra' => [
            [
              'field' => 't_type',
              'value' => $type,
            ],
          ],
        ],
      ];
    }
    elseif ($base_table = $entity_type->getDataTable()) {
      $data[$base_table][$type . '_to_usage_entity'] = [
        'title' => t('Information about the usage of this @entity_type', ['@entity_type' => $entity_type->getLabel()]),
        'help' => t('Creates a relationship about this <em>@entity_type</em> and the entity_usage information that relates to it.', ['@entity_type' => $entity_type->getLabel()]),
        'relationship' => [
          'base' => 'entity_usage',
          'base field' => 't_id',
          'field' => $entity_type->getKey('id'),
          'id' => 'standard',
          'label' => t('Usage information (@entity_type)', ['@entity_type' => $entity_type->getLabel()]),
          'extra' => [
            [
              'field' => 't_type',
              'value' => $type,
            ],
          ],
        ],
      ];
    }

  }

}
