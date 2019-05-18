<?php

namespace Drupal\shopify\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Shopify product variant entities.
 */
class ShopifyProductVariantViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['shopify_product_variant']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Shopify product variant'),
      'help' => $this->t('The Shopify product variant ID.'),
    ];

    return $data;
  }

}
