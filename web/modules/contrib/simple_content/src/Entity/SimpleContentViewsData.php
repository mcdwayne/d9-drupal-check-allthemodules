<?php

namespace Drupal\simple_content\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Simple content entities.
 */
class SimpleContentViewsData extends EntityViewsData {

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
