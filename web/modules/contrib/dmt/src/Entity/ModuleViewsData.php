<?php

namespace Drupal\dmt\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Module entities.
 */
class ModuleViewsData extends EntityViewsData {

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
