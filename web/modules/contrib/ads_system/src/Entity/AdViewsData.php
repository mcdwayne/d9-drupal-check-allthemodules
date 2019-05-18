<?php

namespace Drupal\ads_system\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Ad entities.
 */
class AdViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ad']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Ad'),
      'help' => $this->t('The Ad ID.'),
    ];

    return $data;
  }

}
