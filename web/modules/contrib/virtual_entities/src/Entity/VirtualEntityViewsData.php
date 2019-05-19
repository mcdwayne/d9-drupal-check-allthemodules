<?php

namespace Drupal\virtual_entities\Entity;

use Drupal\virtual_entities\VirtualEntityViewsDataBase;

/**
 * Provides Views data for Virtual entity entities.
 */
class VirtualEntityViewsData extends VirtualEntityViewsDataBase {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['virtual_entity']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Virtual entity'),
      'help' => $this->t('The Virtual entity ID.'),
    ];

    return $data;
  }

}
