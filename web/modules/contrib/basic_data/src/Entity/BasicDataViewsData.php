<?php

namespace Drupal\basic_data\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Basic Data entities.
 */
class BasicDataViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
