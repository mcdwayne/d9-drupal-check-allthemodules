<?php

namespace Drupal\strava_athletes\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Athlete entities.
 */
class AthleteViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['athlete']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Athlete'),
      'help' => $this->t('The Athlete ID.'),
    ];

    return $data;
  }

}
