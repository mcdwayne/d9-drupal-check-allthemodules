<?php

namespace Drupal\client_config_care\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Config blocker entity entities.
 */
class ConfigBlockerEntityViewsData extends EntityViewsData {

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
