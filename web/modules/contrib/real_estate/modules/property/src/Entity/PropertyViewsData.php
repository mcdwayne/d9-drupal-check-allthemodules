<?php

namespace Drupal\real_estate_property\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Property entities.
 */
class PropertyViewsData extends EntityViewsData {

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
