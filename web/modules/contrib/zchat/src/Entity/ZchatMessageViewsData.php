<?php

namespace Drupal\zchat\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Zchat Message entities.
 */
class ZchatMessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
