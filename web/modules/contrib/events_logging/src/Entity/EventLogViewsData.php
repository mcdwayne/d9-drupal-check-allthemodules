<?php

namespace Drupal\events_logging\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event log entities.
 */
class EventLogViewsData extends EntityViewsData {

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
