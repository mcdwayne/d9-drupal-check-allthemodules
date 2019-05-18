<?php

namespace Drupal\flashpoint_course_module\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Course module entities.
 */
class FlashpointCourseModuleViewsData extends EntityViewsData {

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
