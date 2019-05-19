<?php

namespace Drupal\strava_activities\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Activity entities.
 */
class ActivityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['activity']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Activity'),
      'help' => $this->t('The Activity ID.'),
    ];

    return $data;
  }

}
