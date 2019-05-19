<?php

namespace Drupal\webpay\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Webpay transaction entities.
 */
class WebpayTransactionViewsData extends EntityViewsData {

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
