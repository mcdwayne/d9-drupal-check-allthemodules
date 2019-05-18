<?php

namespace Drupal\maestro\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for the Maestro Queue Entity.
 */
class MaestroQueueViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['maestro_queue']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Maestro Queue'),
      'help' => $this->t('The Maestro Queue entity ID.'),
    );

    return $data;
  }

}
