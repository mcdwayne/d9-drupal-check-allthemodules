<?php

namespace Drupal\iots_measure\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Iots Measure entities.
 */
class IotsMeasureViewsData extends EntityViewsData {

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
