<?php

namespace Drupal\google_calendar\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Google Calendar entities.
 */
class GoogleCalendarViewsData extends EntityViewsData {

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
