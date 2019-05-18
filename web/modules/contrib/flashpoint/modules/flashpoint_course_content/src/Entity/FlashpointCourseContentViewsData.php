<?php

namespace Drupal\flashpoint_course_content\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Flashpoint course content entities.
 */
class FlashpointCourseContentViewsData extends EntityViewsData {

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
