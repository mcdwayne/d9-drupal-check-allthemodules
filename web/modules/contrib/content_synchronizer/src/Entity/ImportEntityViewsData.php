<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Import entities.
 */
class ImportEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    return $data;
  }

}
