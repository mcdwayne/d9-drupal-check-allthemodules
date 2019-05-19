<?php

namespace Drupal\swish_payment\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Swish transaction entities.
 */
class SwishTransactionViewsData extends EntityViewsData {

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
