<?php

namespace Drupal\twitter_entity\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Twitter entity entities.
 */
class TwitterEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['twitter_entity']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Twitter entity'),
      'help' => $this->t('The Twitter entity ID.'),
    ];

    return $data;
  }

}
