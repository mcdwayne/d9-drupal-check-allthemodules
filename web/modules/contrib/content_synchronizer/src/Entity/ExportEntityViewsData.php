<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Export entity entities.
 */
class ExportEntityViewsData extends EntityViewsData {

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
