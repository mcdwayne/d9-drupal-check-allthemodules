<?php

namespace Drupal\commerce_inventory\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Inventory Adjustment entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryAdjustmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['item'] = $this->t('Item');
    $header['type'] = $this->t('Type');
    $header['adjustment'] = $this->t('Adjustment');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryAdjustment */
    $row['item'] = $entity->getItem()->label();
    $row['type'] = $entity->getType()->getLabel();
    $row['adjustment'] = $entity->getQuantity();
    return $row + parent::buildRow($entity);
  }

}
