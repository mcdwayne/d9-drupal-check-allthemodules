<?php

namespace Drupal\task_note\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Task Note entities.
 */
class TaskNoteViewsData extends EntityViewsData {

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
