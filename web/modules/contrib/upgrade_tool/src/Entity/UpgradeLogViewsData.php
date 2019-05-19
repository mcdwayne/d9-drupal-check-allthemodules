<?php

namespace Drupal\upgrade_tool\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Upgrade log entities.
 */
class UpgradeLogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
