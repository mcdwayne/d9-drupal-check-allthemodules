<?php

namespace Drupal\iots_device\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Device entities.
 */
class IotsDeviceViewsData extends EntityViewsData {

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
