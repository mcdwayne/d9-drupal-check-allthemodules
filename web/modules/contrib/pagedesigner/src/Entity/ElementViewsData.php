<?php

namespace Drupal\pagedesigner\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Pagedesigner Element entities.
 */
class ElementViewsData extends EntityViewsData {

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
