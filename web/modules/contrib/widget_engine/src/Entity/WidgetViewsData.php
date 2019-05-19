<?php

namespace Drupal\widget_engine\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Widget entities.
 */
class WidgetViewsData extends EntityViewsData {

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
