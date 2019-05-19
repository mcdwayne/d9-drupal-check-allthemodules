<?php

namespace Drupal\splashify\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Splashify entity entities.
 */
class SplashifyEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['splashify_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Splashify entity'),
      'help' => $this->t('The Splashify entity ID.'),
    );

    return $data;
  }

}
