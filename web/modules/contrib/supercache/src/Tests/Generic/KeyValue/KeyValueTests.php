<?php

namespace Drupal\supercache\Tests\Generic\KeyValue;


use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Drupal\Core\Site\Settings;

/**
 * Basic testing for KeyValue storage.
 */
abstract class KeyValueTests extends KernelTestBase {

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $factory;

  /**
   * Test that tag invalidations work.
   */
  public function testKeyValue() {

    $col1 = [
        'array' => ['B' => 23],
        'string' => 'asdgsd',
        'object' => (object) ['property1' => 'value1'],
        'float' => 7E-10,
        'int' => 58
      ];

    $col2 = [
      'array' => ['B' => 65],
      'string' => 'this is a string',
      'object' => (object) ['propertyA' => 'valueA'],
      'float' => 3E-10,
      'int' => 21
    ];

    $storeA = $this->factory->get('col1');
    $storeB = $this->factory->get('col2');

    $storeA->deleteAll();
    $storeB->deleteAll();

    // Set multiple.
    $storeA->setMultiple($col1);
    $this->assertEquals(count($col1), count($storeA->getAll()), 'Number of items match.');

    // Get all.
    $this->assertEquals($col1, $storeA->getAll(), 'Elements are properly stored and retrieved.');

    // Set if not exists.
    $this->assertFalse($storeA->setIfNotExists('array', ['asd']));
    $this->assertEquals($col1['array'], $storeA->get('array'));

    // Default value.
    $this->assertEqual($storeA->get('does not exist', 'default'), 'default', 'Default values working');

    // Delete
    $storeA->delete('array');
    $this->assertEquals(count($col1) - 1, count($storeA->getAll()), 'Item deleted.');
    $this->assertFalse($storeA->get('array'));

    // Set
    $storeA->set('Hi', 'value');
    $this->assertEquals('value', $storeA->get('Hi'), 'Setting a value works.');

    // Delete all.
    $storeA->deleteAll();
    $this->assertEquals(0, count($storeA->getAll()), 'Items removed.');

    // Delete multiple.
    $storeA->setMultiple($col1);
    $this->assertEquals(count($col1), count($storeA->getAll()), 'Number of items match.');
    $storeA->deleteMultiple(array_keys($col1));
    $this->assertEquals(0, count($storeA->getAll()), 'Items removed.');

    // Empty unused.
    $this->assertEquals(0, count($storeB->getAll()), 'Unused collection is empty.');

    // Collection name
    $this->assertEquals('col1', $storeA->getCollectionName(), 'Collection name matches.');
    $this->assertEquals('col2', $storeB->getCollectionName(), 'Collection name matches.');

    // Regenerate the factory
    $storeA->setMultiple($col1);
    $storeB->setMultiple($col2);

    $storeA = $this->factory->get('col1');
    $storeB = $this->factory->get('col2');

    $this->assertEquals($col1, $storeA->getAll(), 'Elements are properly stored and retrieved.');
    $this->assertEquals($col2, $storeB->getAll(), 'Elements are properly stored and retrieved.');

    // Rename
    try {
      $storeA->rename('array', 'string');
      $this->fail("Trying to rename to a key that exists should throw an exception.");
    }
    catch (\Exception $e) {}

    $storeA->delete('string');
    $storeA->rename('array', 'string');

    $this->assertEquals($col1['array'], $storeA->get('string'), 'Rename works.');
    $this->assertFalse($storeA->has('array'), 'Rename removed old key.');

  }
}
