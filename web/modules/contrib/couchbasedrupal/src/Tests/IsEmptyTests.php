<?php

namespace Drupal\couchbasedrupal\Tests;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

use Drupal\Core\Site\Settings;


/**
 * Testea funciones basicas.
 *
 * @group Cache
 */
class IsEmptyTests extends GeneralTestCase {
  /**
   * Gets the test info.
   */
  public static function getInfo() {
    return array(
      'name' => 'emptiness test',
      'description' => 'Check if a binary is empty after performing clear operations.',
      'group' => 'Couchbase',
    );
  }

  /**
   * Set up the test.
   */
  public function setUp() {

    $this->defaultbin = 'cache_page';
    $this->defaultvalue = $this->randomName(10);

    parent::setUp();

    $this->backend->set($this->defaultcid, $this->defaultvalue, CacheBackendInterface::CACHE_PERMANENT);
  }

  /**
   * Test clearing using a cid.
   */
  public function testIsEmpty() {
    // Clear the cache bin.
    $this->backend->deleteAll();
    $this->assertRemoved('Cache was removed.', $this->defaultcid);

    // Add some data to the cache bin.
    $this->backend->set($this->defaultcid, $this->defaultvalue, CacheBackendInterface::CACHE_PERMANENT);
    $this->assertExists('Cache was set.', $this->defaultvalue, $this->defaultcid);
    $this->assertTrue($this->checkExists($this->defaultcid, $this->defaultvalue), 'The cache bin is not empty');

    // Remove the cached data.
    $this->backend->delete($this->defaultcid);
    $this->assertRemoved('Cache was removed.', $this->defaultcid);
    $this->assertFalse($this->checkExists($this->defaultcid, $this->defaultvalue), 'The cache bin is empty');
  }

}