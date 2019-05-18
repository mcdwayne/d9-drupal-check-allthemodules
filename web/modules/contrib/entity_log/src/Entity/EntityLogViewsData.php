<?php

namespace Drupal\entity_log\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Entity log entities.
 */
class EntityLogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
