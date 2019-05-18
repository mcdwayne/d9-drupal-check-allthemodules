<?php

namespace Drupal\assembly\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Assembly entities.
 */
class AssemblyViewsData extends EntityViewsData {

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
