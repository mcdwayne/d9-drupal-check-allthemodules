<?php

namespace Drupal\simple_megamenu\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Simple mega menu entities.
 */
class SimpleMegaMenuViewsData extends EntityViewsData {

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
