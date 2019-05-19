<?php

namespace Drupal\trash\Plugin\views\field;

trait FieldTrait {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $entity_ids_by_type = [];
    /** @var \Drupal\views\ResultRow $result_row */
    foreach ($values as $result_row) {
      if (!isset($result_row->_content_moderated_entity)) {
        $entity_ids_by_type[$result_row->_entity->get('content_entity_type_id')->value][$result_row->_entity->get('content_entity_id')->value] = $result_row->_entity->id();
      }
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    $entities = [];
    foreach ($entity_ids_by_type as $entity_type => $entity_ids) {
      $entities[$entity_type] = $entity_type_manager->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    foreach ($values as $result_row) {
      if (isset($entities[$result_row->_entity->get('content_entity_type_id')->value][$result_row->_entity->get('content_entity_id')->value])) {
        $result_row->_content_moderated_entity = $entities[$result_row->_entity->get('content_entity_type_id')->value][$result_row->_entity->get('content_entity_id')->value];
      }
    }
  }

}
