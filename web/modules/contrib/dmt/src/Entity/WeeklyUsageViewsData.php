<?php

namespace Drupal\dmt\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Weekly usage entities.
 */
class WeeklyUsageViewsData extends EntityViewsData {

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
