<?php

namespace Drupal\chatbot\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Message entities.
 */
class MessageViewsData extends EntityViewsData {

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
