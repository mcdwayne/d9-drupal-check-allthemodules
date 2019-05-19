<?php

/**
 * @file
 * Helper class for Item unit test base classes.
 */

namespace Drupal\wow_item\Tests;

use Drupal\wow\Mocks\ServiceStub;

use WoW\Item\Entity\Item;

/**
 * Defines UnitTestBase class test.
 */
class UnitTestBase extends \Drupal\wow\Tests\UnitTestBase {

  protected function setUp() {
    parent::setUp();

    $this->registerNamespace('WoW\Item', 'wow_item');
    $this->registerNamespace('Drupal\wow_item', 'wow_item');

    $entity_info = &drupal_static('entity_get_info');
    $entity_info['wow_realm'] = array(
      'label' => t('Item'),
      'entity class' => 'WoW\Item\Entity\Item',
      'controller class' => 'WoW\Item\Entity\ItemStorageController',
      'service controller class' => 'WoW\Item\Entity\ItemServiceController',
      'base table' => 'wow_item',
      'load hook' => 'wow_item',
      'uri callback' => 'wow_item_uri',
      'fieldable' => TRUE,
      'translation' => array(
        'locale' => TRUE,
      ),
      'entity keys' => array(
        'id' => 'id',
        'language' => 'language',
      ),
      'bundles' => array(
        'wow_item' => array(
          'label' => t('Item'),
        ),
      ),
      'view modes' => array(
        'full' => array(
          'label' => t('Item'),
          'custom settings' => FALSE,
        ),
        'teaser' => array(
          'label' => t('Tooltip'),
          'custom settings' => TRUE,
        ),
      ),
    );
  }

  /**
   * Creates a new Item entity.
   *
   * @param array $values
   *   The entity values.
   *
   * @return Item
   *   A new instance of wow_item entity.
   */
  protected function createItem(array $values) {
    return new Item($values, 'wow_item');
  }

}
