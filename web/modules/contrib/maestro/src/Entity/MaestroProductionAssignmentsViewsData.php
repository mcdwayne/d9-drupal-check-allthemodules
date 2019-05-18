<?php

namespace Drupal\maestro\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for the Maestro Production Assignments Entity.
 */
class MaestroProductionAssignmentsViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['maestro_production_assignments']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Maestro Producion Assignments'),
      'help' => $this->t('The Maestro Production Assignments entity ID.'),
    );

    return $data;
  }

}
