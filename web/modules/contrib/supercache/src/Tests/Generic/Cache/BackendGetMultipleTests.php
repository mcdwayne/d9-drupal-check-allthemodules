<?php

namespace Drupal\supercache\Tests\Generic\Cache;

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
class BackendGetMultipleTests extends BackendGeneralTestCase {


  /**
   * Test cache_get_multiple().
   */
  public function testCacheMultiple() {
    $item1 = $this->randomName(10);
    $item2 = $this->randomName(10);
    $this->backend->set('item1', $item1);
    $this->backend->set('item2', $item2);
    $this->assertTrue($this->checkExists('item1', $item1), 'Item 1 is cached.');
    $this->assertTrue($this->checkExists('item2', $item2), 'Item 2 is cached.');

    // Fetch both records from the database with cache_get_multiple().
    $item_ids = array('item1', 'item2');
    $items = $this->backend->getMultiple($item_ids);
    $this->assertEqual($items['item1']->data, $item1, 'Item was returned from cache successfully.');
    $this->assertEqual($items['item2']->data, $item2, 'Item was returned from cache successfully.');

    // Remove one item from the cache.
    $this->backend->delete('item2');

    // Confirm that only one item is returned by cache_get_multiple().
    $item_ids = array('item1', 'item2');
    $items = $this->backend->getMultiple($item_ids);
    $this->assertEqual($items['item1']->data, $item1, 'Item was returned from cache successfully.');
    $this->assertFalse(isset($items['item2']), 'Item was not returned from the cache.');
    $this->assertTrue(count($items) == 1, 'Only valid cache entries returned.');
  }

}