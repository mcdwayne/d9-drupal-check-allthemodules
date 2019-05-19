<?php

namespace Drupal\Tests\staged_content\Unit\Storage;

use Drupal\staged_content\Storage\JsonFileStorage;
use Drupal\Tests\UnitTestCase;

/**
 * Class DividedJsonFileStorageTest.
 *
 * @group staged_content
 */
class JsonFileStorageTest extends UnitTestCase {

  /**
   * @covers Drupal\staged_content\Storage\JsonFileStorage::__construct
   */
  public function testConstruct() {
    $storage = new JsonFileStorage('/output/folder/location');
    $this->assertInstanceOf('Drupal\staged_content\Storage\StorageHandlerInterface', $storage);
  }

  /**
   * Tests the detection of files in a simple set of files.
   *
   * This means that no extra stages have been defined and everything lives
   * in the root.
   *
   * @covers Drupal\staged_content\Storage\JsonFileStorage::listDataItems
   */
  public function testListDataItemsFlat() {
    $storage = new JsonFileStorage($this->provideFixtureDir() . '/flat-set');
    $detectedItems = $storage->listDataItems();

    // Should have only detected the items in the "prod" set.
    $this->assertEquals(5, count($detectedItems));
    // Data should have been keyed by uuid.
    $this->assertArrayHasKey('uuid-1', $detectedItems);
    // The data should be an item with the correct interface.
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $detectedItems['uuid-1']);
  }

  /**
   * Tests the detection of files in a complex set of files.
   *
   * This means that various stages have data split over several "marker" dirs.
   *
   * @covers Drupal\staged_content\Storage\JsonFileStorage::listDataItems
   */
  public function testListDataItemsNestedOnlyProd() {
    $storage = new JsonFileStorage($this->provideFixtureDir() . '/nested-set/MARKER_NAME');
    $detectedItems = $storage->listDataItems();

    // Should have only detected the items in the "prod" set.
    $this->assertEquals(1, count($detectedItems));
    // Data should have been keyed by uuid.
    $this->assertArrayHasKey('uuid-4', $detectedItems);
    // The data should be an item with the correct interface.
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $detectedItems['uuid-4']);
  }

  /**
   * Tests the detection of files in a complex set of files.
   *
   * This means that various stages have data split over several "marker" dirs.
   *
   * @covers Drupal\staged_content\Storage\JsonFileStorage::listDataItems
   */
  public function testListDataItemsNestedMultipleStages() {
    $storage = new JsonFileStorage($this->provideFixtureDir() . '/nested-set/MARKER_NAME', ['prod', 'acc']);
    $detectedItems = $storage->listDataItems();

    // Should have only detected the items in the "prod" and "acc" set.
    $this->assertEquals(4, count($detectedItems));
    // Data should have been keyed by uuid.
    $this->assertArrayHasKey('uuid-1', $detectedItems);
    $this->assertArrayHasKey('uuid-2', $detectedItems);
    $this->assertArrayHasKey('uuid-3', $detectedItems);
    $this->assertArrayHasKey('uuid-4', $detectedItems);
    // The data should be an item with the correct interface.
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $detectedItems['uuid-1']);
  }

  /**
   * Tests the getting of data in a complex set of files.
   *
   * This means that various stages have data split over several "marker" dirs.
   *
   * @covers Drupal\staged_content\Storage\JsonFileStorage::getDataItem
   */
  public function testGetDataItemNestedSet() {
    $storage = new JsonFileStorage($this->provideFixtureDir() . '/nested-set/MARKER_NAME');
    $item = $storage->getDataItem('node', 'uuid-4', 'prod');
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $item);
  }

  /**
   * Tests the getting of data in a simple set of files.
   *
   * This means that various stages have data split over several "marker" dirs.
   *
   * @covers Drupal\staged_content\Storage\JsonFileStorage::getDataItem
   */
  public function testGetDataItemSimpleSet() {
    $storage = new JsonFileStorage($this->provideFixtureDir() . '/simple-set');
    $item = $storage->getDataItem('node', 'uuid-4');
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $item);
  }

  /**
   * Get the location for the fixture files.
   *
   * @TODO Move this to a trait.
   *
   * @return string
   *   Location for the fixture files root.
   */
  protected function provideFixtureDir() {
    return dirname(dirname(__DIR__)) . '/fixtures/';
  }

}
