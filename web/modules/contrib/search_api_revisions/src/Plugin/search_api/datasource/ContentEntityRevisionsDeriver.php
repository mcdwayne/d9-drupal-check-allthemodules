<?php

namespace Drupal\search_api_revisions\Plugin\search_api\datasource;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\search_api\plugin\search_api\datasource\ContentEntityDeriver;

/**
 * Derives a datasource plugin definition for every content entity type.
 *
 * @see \Drupal\search_api\Plugin\search_api\datasource\ContentEntityDatasource
 */
class ContentEntityRevisionsDeriver extends ContentEntityDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $plugin_derivatives = [];
      foreach ($this->getEntityTypeManager()->getDefinitions() as $entity_type => $entity_type_definition) {
        // We only support content entity types at the moment, since config
        // entities don't implement \Drupal\Core\TypedData\ComplexDataInterface.
        if ($entity_type_definition instanceof ContentEntityType && $entity_type_definition->isRevisionable()) {
          $plugin_derivatives[$entity_type] = [
            'entity_type' => $entity_type,
            'label' => 'Revisions: ' . $entity_type_definition->getLabel(),
            'description' => $this->t('Provides %entity_type revisions for indexing and searching.', ['%entity_type' => $entity_type_definition->getLabel()]),
          ] + $base_plugin_definition;
        }
      }

      $this->derivatives = $plugin_derivatives;
    }

    return $this->derivatives;
  }

}
