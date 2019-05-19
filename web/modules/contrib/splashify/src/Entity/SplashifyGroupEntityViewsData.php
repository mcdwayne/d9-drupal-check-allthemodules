<?php

namespace Drupal\splashify\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Splashify group entity entities.
 */
class SplashifyGroupEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['splashify_group_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Splashify group entity'),
      'help' => $this->t('The Splashify group entity ID.'),
    );

    return $data;
  }

}
