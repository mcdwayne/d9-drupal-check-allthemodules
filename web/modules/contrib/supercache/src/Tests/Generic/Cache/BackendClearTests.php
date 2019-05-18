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
abstract class BackendClearTests extends BackendGeneralTestCase {

  /**
   * Test clearing using a cid.
   */
  public function testClearCid() {
    $this->backend->set('test_cid_clear', $this->defaultvalue, CacheBackendInterface::CACHE_PERMANENT);
    $this->assertExists('Cache was set for clearing cid.', $this->defaultvalue, 'test_cid_clear');

    $this->backend->delete('test_cid_clear');
    $this->assertRemoved('Item was removed after clearing cid.', 'test_cid_clear');

    $this->backend->set('test_cid_clear1', $this->defaultvalue, CacheBackendInterface::CACHE_PERMANENT);
    $this->backend->set('test_cid_clear2', $this->defaultvalue, CacheBackendInterface::CACHE_PERMANENT);
    $this->assertTrue($this->checkExists('test_cid_clear1', $this->defaultvalue)
                      && $this->checkExists('test_cid_clear2', $this->defaultvalue),
                      'Two caches were created for checking cid "*" with wildcard false.');
  }

  /**
   * Test clearing using an array.
   */
  public function testClearArray() {
    // Create three cache entries.
    $this->backend->set('test_cid_clear1', $this->defaultvalue);
    $this->backend->set('test_cid_clear2', $this->defaultvalue);
    $this->backend->set('test_cid_clear3', $this->defaultvalue);

    $this->assertTrue($this->checkExists('test_cid_clear1', $this->defaultvalue)
                      && $this->checkExists('test_cid_clear2', $this->defaultvalue)
                      && $this->checkExists('test_cid_clear3', $this->defaultvalue),
                      'Three cache entries were created.');

    // Clear two entries using an array.
    $this->backend->deleteMultiple(array('test_cid_clear1', 'test_cid_clear2'));
    $this->assertFalse($this->checkExists('test_cid_clear1', $this->defaultvalue)
                       || $this->checkExists('test_cid_clear2', $this->defaultvalue),
                       'Two cache entries removed after clearing with an array.');

    $this->assertTrue($this->checkExists('test_cid_clear3', $this->defaultvalue),
                      'Entry was not cleared from the cache');

    $this->backend->set('test_cid_clear1', $this->defaultvalue);
    $this->backend->set('test_cid_clear2', $this->defaultvalue);
    $this->assertTrue($this->checkExists('test_cid_clear1', $this->defaultvalue)
                      && $this->checkExists('test_cid_clear2', $this->defaultvalue),
                      'Two cache entries were created.');

    $this->backend->deleteMultiple(
      array('test_cid_clear1', 'test_cid_clear2', 'test_cid_clear3')
    );

    $this->assertFalse($this->checkExists('test_cid_clear1', $this->defaultvalue)
                       || $this->checkExists('test_cid_clear2', $this->defaultvalue)
                       || $this->checkExists('test_cid_clear3', $this->defaultvalue),
                       'All cache entries removed when the array exceeded the cache clear threshold.');
  }

  /**
   * Test clears and that there is no leakage
   * between binaries.
   */
  public function testClearAll() {

    $this->backend->set('item1', $this->defaultvalue);
    $this->backend->set('item2', $this->defaultvalue);

    $this->assertExists('ItemExists', $this->defaultvalue, 'item1');
    $this->assertExists('ItemExists', $this->defaultvalue, 'item2');

    $this->assertRemoved('Does not exist', 'item1', $this->backend2);
    $this->assertRemoved('Does not exist', 'item2', $this->backend2);

    $this->backend2->set('item1', $this->defaultvalue);
    $this->backend2->set('item2', $this->defaultvalue);

    $this->assertExists('ItemExists', $this->defaultvalue, 'item1', $this->backend2);
    $this->assertExists('ItemExists', $this->defaultvalue, 'item2', $this->backend2);

    $this->backend->removeBin();

    $this->assertRemoved('Does not exist', 'item1', $this->backend);
    $this->assertRemoved('Does not exist', 'item2', $this->backend);

    $this->assertExists('ItemExists', $this->defaultvalue, 'item1', $this->backend2);
    $this->assertExists('ItemExists', $this->defaultvalue, 'item2', $this->backend2);

    $this->backend2->removeBin();

    $this->assertRemoved('Does not exist', 'item1', $this->backend2);
    $this->assertRemoved('Does not exist', 'item2', $this->backend2);

    $this->backend2->set('item1', $this->defaultvalue);
    $this->backend2->set('item2', $this->defaultvalue);

    $this->assertExists('ItemExists', $this->defaultvalue, 'item1', $this->backend2);
    $this->assertExists('ItemExists', $this->defaultvalue, 'item2', $this->backend2);

    $this->backend2->deleteAll();

    $this->assertRemoved('Does not exist', 'item1', $this->backend2);
    $this->assertRemoved('Does not exist', 'item2', $this->backend2);
  }
}