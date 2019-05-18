<?php

/**
 * @file
 * Provide views data for Entity split module.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_split\Entity\EntitySplitType;

/**
 * Implements hook_views_data_alter().
 */
function entity_split_views_data_alter(&$data) {
  // Provide relationships for content entities type except entity split.
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type_id == 'entity_split' || !$entity_type->entityClassImplements(ContentEntityInterface::class) || !$entity_type->getBaseTable()) {
      continue;
    }

    /** @var \Drupal\entity_split\Entity\EntitySplitTypeInterface[] $entity_split_types */
    $entity_split_types = EntitySplitType::getEntitySplitTypesForEntityType($entity_type_id);

    if (empty($entity_split_types)) {
      continue;
    }

    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $args = ['@entity_type' => $entity_type_id];

    foreach ($entity_split_types as $entity_split_type_id => $entity_split_type) {
      $data[$base_table]['entity_split_' . $entity_split_type_id] = [
        'title' => t('Entity splits of the "@split_type" type for the @entity_type entity', $args + ['@split_type' => $entity_split_type->label()]),
        'relationship' => [
          'group' => t('Entity split'),
          'label' => t('Entity splits'),
          'base' => 'entity_split_field_data',
          'base field' => 'entity_id',
          'relationship field' => $entity_type->getKey('id'),
          'id' => 'standard',
          'extra' => [
            [
              'field' => 'entity_type',
              'value' => $entity_type_id,
            ],
            [
              'field' => 'type',
              'value' => $entity_split_type_id,
            ],
          ],
        ],
      ];

      if ($entity_type->hasKey('langcode')) {
        // Use OR operator for language conditions.
        $data[$base_table]['entity_split_' . $entity_split_type_id]['relationship']['join_id'] = 'field_or_language_join';

        $data[$base_table]['entity_split_' . $entity_split_type_id]['relationship']['extra'][] = [
          'field' => 'langcode',
          'left_field' => $entity_type->getKey('langcode'),
        ];

        $data[$base_table]['entity_split_' . $entity_split_type_id]['relationship']['extra'][] = [
          'field' => 'langcode',
          'value' => 'und',
        ];
      }
    }
  }
}
