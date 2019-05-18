<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the price list item storage.
 */
class PriceListItemStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    if (!isset($values['price_list_id'])) {
      throw new EntityStorageException('Missing "price_list_id" property when creating a commerce_pricelist_item entity.');
    }

    return parent::doCreate($values);
  }

}
