<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

use Drupal\Core\Site\Settings;

/**
 * Test saving functions.
 *
 * @group Cache
 */
class BackendExpireTests extends BackendGeneralTestCase {

  public static function getInfo() {
    return [
      'name' => 'Expire tests',
      'description' => 'Ensure that cache items properly expire.',
      'group' => 'Couchbase',
    ];
  }

  /**
   * Test the saving and restoring of a string.
   */
  public function testExpirations() {

    $name = 'test-data';
    $value = 'this is the data';

    // Cascade expiration in different binaries.
    $this->backend->set($name, $value, time() + 3);
    $this->assertExists('Item exists', $value, $name, $this->backend);
    $this->backend2->set($name, $value, time() + 7);
    $this->assertExists('Item exists', $value, $name, $this->backend2);
    sleep(5);
    $this->refreshRequestTime($this->backend);
    $this->assertRemoved('Item expired', $name, $this->backend);
    $this->assertExists('Item exists', $value, $name, $this->backend2);
    sleep(5);
    $this->refreshRequestTime($this->backend2);
    $this->assertRemoved('Item expired', $name, $this->backend2);

    // Test that permanent items do not expire... at some point
    // one of the backends was passing through the -1 to the storage
    // backend or even calculating negative expirations...
    $this->backend->set($name, $value, \Drupal\Core\Cache\Cache::PERMANENT);
    sleep(5);
    $this->assertExists('Item exists', $value, $name, $this->backend);

  }

}