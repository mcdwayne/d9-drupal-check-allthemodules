<?php

namespace Drupal\advertising_products\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Advertising Product entities.
 */
class AdvertisingProductViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['advertising_product']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Advertising Product'),
      'help' => $this->t('The Advertising Product ID.'),
    );

    return $data;
  }

}
