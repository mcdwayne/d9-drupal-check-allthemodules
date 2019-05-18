<?php

namespace Drupal\points;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the point_movement entity type.
 */
class PointMovementViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {

    $data = parent::getViewsData();

    // Add the relationship to User.
    $data['point_movement']['uid']['relationship']['id'] = 'standard';
    $data['point_movement']['uid']['relationship']['base'] = 'users_field_data';
    $data['point_movement']['uid']['relationship']['base field'] = 'uid';
    $data['point_movement']['uid']['relationship']['title'] = $this->t('Users');
    $data['point_movement']['uid']['relationship']['label'] = $this->t('Users');

    // Add the relationship to point.
    $data['point_movement']['point_id']['relationship']['id'] = 'standard';
    $data['point_movement']['point_id']['relationship']['base'] = 'point';
    $data['point_movement']['point_id']['relationship']['base field'] = 'id';
    $data['point_movement']['point_id']['relationship']['title'] = $this->t('Point');
    $data['point_movement']['point_id']['relationship']['label'] = $this->t('Point entity');

    return $data;
  }

}
