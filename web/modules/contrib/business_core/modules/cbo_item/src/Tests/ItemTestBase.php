<?php

namespace Drupal\cbo_item\Tests;

use Drupal\cbo_item\Entity\Item;
use Drupal\cbo_item\Entity\ItemCategory;
use Drupal\cbo_item\Entity\ItemType;
use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class ItemTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cbo_item'];

  /**
   * A item type.
   *
   * @var \Drupal\cbo_item\ItemTypeInterface
   */
  protected $itemType;

  /**
   * A item category.
   *
   * @var \Drupal\cbo_item\ItemCategoryInterface
   */
  protected $itemCategory;

  /**
   * A item.
   *
   * @var \Drupal\cbo_item\ItemInterface
   */
  protected $item;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->itemType = $this->createItemType();
    $this->itemCategory = $this->createItemCategory();
    $this->item = $this->createItem();
  }

  /**
   * Creates a item type based on default settings.
   */
  protected function createItemType(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $entity = ItemType::create($settings);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a item type based on default settings.
   */
  protected function createItemCategory(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $entity = ItemCategory::create($settings);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a item based on default settings.
   */
  protected function createItem(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => $this->itemType->id(),
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
      'category' => $this->itemCategory->id(),
    ];
    $entity = Item::create($settings);
    $entity->save();

    return $entity;
  }

}
