<?php

namespace Drupal\crm_core_activity;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the activity entity type.
 */
class ActivityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['crm_core_activity']['activity_preview'] = [
      'title' => t('Activity preview field'),
      'field' => [
        'title' => t('Activity preview'),
        'help' => t('Provide preview of activity'),
        'id' => 'activity_preview',
        'field' => 'activity_id',
      ],
    ];

    return $data;
  }

}
