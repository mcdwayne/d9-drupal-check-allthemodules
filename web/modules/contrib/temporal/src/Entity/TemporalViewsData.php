<?php

/**
 * @file
 * Contains \Drupal\temporal\Entity\Temporal.
 */

namespace Drupal\temporal\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Temporal entities.
 */
class TemporalViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['temporal']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Temporal'),
      'help' => $this->t('The Temporal ID.'),
    );

    return $data;
  }

}
