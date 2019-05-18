<?php

namespace Drupal\business_rules\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Schedule entities.
 */
class ScheduleViewsData extends EntityViewsData {

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
