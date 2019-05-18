<?php

namespace Drupal\custom_messages\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Custom Message entities.
 */
class CustomMessageViewsData extends EntityViewsData {

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
