<?php

namespace Drupal\entity_collector\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\entity_collector\Entity\EntityCollectionType;

/**
 * Provides field plugin definitions for custom menus.
 */
class EntityCollectionActionsDerivative extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (EntityCollectionType::loadMultiple() as $entityCollectionType => $entity) {
      $this->derivatives[$entityCollectionType] = $base_plugin_definition;
      $this->derivatives[$entityCollectionType]['id'] = $base_plugin_definition['id'] . ':' . $entityCollectionType;
      $this->derivatives[$entityCollectionType]['bundles'][] = $entity->getSource() . '.*';
      $this->derivatives[$entityCollectionType]['entity_collection_type'] = $entityCollectionType;
    }
    return $this->derivatives;
  }
}
