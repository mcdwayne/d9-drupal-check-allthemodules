<?php

namespace Drupal\edstep;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the node entity type.
 */
class EdstepCourseViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // https://api.drupal.org/api/drupal/core!modules!views!views.api.php/function/hook_views_data/8.2.x

    $data['edstep_course']['title'] = [
      'title' => $this->t('Title'),
      'field' => [
        'id' => 'edstep_course_title',
      ],
    ];

    return $data;
  }

}
