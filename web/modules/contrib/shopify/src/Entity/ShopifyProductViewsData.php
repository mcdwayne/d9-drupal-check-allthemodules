<?php

namespace Drupal\shopify\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Shopify product entities.
 */
class ShopifyProductViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['shopify_product']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Shopify product'),
      'help' => $this->t('The Shopify product ID.'),
    ];

    return $data;
  }

}
