<?php

/**
 * @file
 * Contains \Drupal\wishlist\WishlistPurchasedListBuilder.
 */

namespace Drupal\wishlist;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Wishlist purchased entities.
 */
class WishlistPurchasedListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'buyer_uid' => $this->t('Purchaser'),
      'date' => $this->t('On'),
      'quantity' => $this->t('Quantity'),
      'title' => $this->t('Item'),
      'wishlist_uid' => $this->t('For'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // @todo
    return $row + parent::buildRow($entity);
  }

}
