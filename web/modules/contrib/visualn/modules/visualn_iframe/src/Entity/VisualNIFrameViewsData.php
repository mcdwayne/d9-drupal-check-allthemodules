<?php

namespace Drupal\visualn_iframe\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for VisualN IFrame entities.
 */
class VisualNIFrameViewsData extends EntityViewsData {

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
