<?php

namespace Drupal\linky\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Linky entities.
 */
class LinkyViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['linky']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Managed Link'),
      'help' => $this->t('The Managed Link ID.'),
    ];

    return $data;
  }

}
