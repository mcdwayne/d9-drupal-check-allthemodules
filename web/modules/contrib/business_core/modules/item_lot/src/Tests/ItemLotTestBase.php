<?php

namespace Drupal\item_lot\Tests;

use Drupal\cbo_inventory\Tests\InventoryTestBase;
use Drupal\item_lot\Entity\ItemLot;

/**
 * Provides helper functions for item_lot module tests.
 */
abstract class ItemLotTestBase extends InventoryTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['item_lot'];

  /**
   * A item_lot.
   *
   * @var \Drupal\item_lot\ItemLotInterface
   */
  protected $itemLot;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->itemLot = $this->createItemLot();
  }

  /**
   * Creates a item_lot based on default settings.
   */
  protected function createItemLot(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'number' => $this->randomMachineName(8),
      'item' => $this->item->id(),
    ];
    $entity = ItemLot::create($settings);
    $entity->save();

    return $entity;
  }

}
