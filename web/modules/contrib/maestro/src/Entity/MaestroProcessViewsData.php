<?php

namespace Drupal\maestro\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for the Maestro Process Entity.
 */
class MaestroProcessViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['maestro_process']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Maestro Process'),
      'help' => $this->t('The Maestro Process entity ID.'),
    );

    return $data;
  }

}
