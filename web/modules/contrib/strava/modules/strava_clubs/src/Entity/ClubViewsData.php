<?php

namespace Drupal\strava_clubs\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Club entities.
 */
class ClubViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['club']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Club'),
      'help' => $this->t('The Club ID.'),
    ];

    return $data;
  }

}
