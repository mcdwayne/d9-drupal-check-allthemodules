<?php

namespace Drupal\formazing\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Result formazing entity entities.
 */
class ResultFormazingEntityViewsData extends EntityViewsData {

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
