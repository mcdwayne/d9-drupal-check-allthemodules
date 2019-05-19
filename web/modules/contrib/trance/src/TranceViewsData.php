<?php

namespace Drupal\trance;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for trance entities.
 */
class TranceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $entity_type_id = $this->entityType->id();
    $entity_type_label = $this->entityType->getLabel();

    if (isset($data[$entity_type_id . '_field_data']['table']['base']['title'])) {
      $data[$entity_type_id . '_field_data']['table']['base']['title'] = $entity_type_label;
    }
    if (isset($data[$entity_type_id . '_field_revision']['table']['base']['title'])) {
      $data[$entity_type_id . '_field_revision']['table']['base']['title'] = $this->t('@label revision', [
        '@label' => $entity_type_label,
      ]);
    }

    return $data;
  }

}
