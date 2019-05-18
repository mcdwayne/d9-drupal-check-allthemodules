<?php

/**
 * @file
 * Contains \Drupal\entityblock\Plugin\Derivative\EntityBlockContent.
 */

namespace Drupal\entityblock\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Retrieves block plugin definitions for all EntityBlocks.
 */
class EntityBlockContent extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Find all entityblock fields.
    $field_ids = \Drupal::entityQuery('field_storage_config')
      ->condition('type', 'entityblock')
      ->execute();

    if ($field_ids) {
      // Load all entityblock fields.
      $fields = \Drupal::entityTypeManager()
        ->getStorage('field_storage_config')
        ->loadMultiple($field_ids);

      foreach ($fields as $field) {
        // Load all field data.
        $entity_query = \Drupal::entityQuery($field->getTargetEntityTypeId());
        $entity_query->condition($field->getName() . '.enabled', 1);
        $entity_ids = $entity_query->execute();

        // There are entities of this type.
        if ($entity_ids) {
          $entities = \Drupal::entityTypeManager()
            ->getStorage($field->getTargetEntityTypeId())
            ->loadMultiple($entity_ids);
          foreach ($entities as $entity) {
            foreach ($entity->{$field->getName()} as $delta => $item) {
              $key = $field->uuid() . '|' . $entity->uuid() . '|' . $delta;

              $this->derivatives[$key] = $base_plugin_definition;
              $this->derivatives[$key]['admin_label'] = $entity->label() . ' (' . $item->title . ')';
              $this->derivatives[$key]['config_dependencies']['content'] = [$entity->getConfigDependencyName()];
              $this->derivatives[$key]['config_dependencies'][$field->getConfigDependencyKey()] = [$field->getConfigDependencyName()];
            }
          }
        }
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
