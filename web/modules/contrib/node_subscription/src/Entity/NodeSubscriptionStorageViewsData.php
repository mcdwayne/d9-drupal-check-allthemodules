<?php

namespace Drupal\node_subscription\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Node subscription entities.
 */
class NodeSubscriptionStorageViewsData extends EntityViewsData {

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
