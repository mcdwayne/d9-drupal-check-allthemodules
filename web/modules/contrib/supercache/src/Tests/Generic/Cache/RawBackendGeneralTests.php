<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

/**
 * General tests for the specific raw backend
 * capabilities.
 */
class RawBackendGeneralTests extends RawBackendGeneralTestCase {

  /**
   * Test counter().
   */
  function testCounter() {

    $cid = 'test-name';
    $cid2 = 'my other counter';

    $value1 = 125484;
    $value2 = 986532;
    $value3 = 58412569;

    $backend = $this->backend;

    $backend->counter($cid, 2, 0);
    $this->assertEqual($backend->counterGet($cid), 0, 'Counter properly initializes an item.');

    $backend->counter($cid, 2, 0);
    $this->assertEqual($backend->counterGet($cid), 2, 'Increment works.');

    $backend->counter($cid, 2, 0);
    $this->assertEqual($backend->counterGet($cid), 4, 'Increment works.');

    $backend->counterSet($cid, $value3);
    $this->assertEqual($backend->counterGet($cid), $value3, 'Increment works.');

    $backend->delete($cid);
    $backend->counter($cid, 2, 0);
    $this->assertEqual($backend->counterGet($cid), 0, 'Counter properly initializes an item.');

    $backend->counterSetMultiple([$cid => $value1, $cid2 => $value2]);
    $this->assertEqual($backend->counterGet($cid), $value1, 'Counter properly initializes an item.');
    $this->assertEqual($backend->counterGet($cid2), $value2, 'Counter properly initializes an item.');

    $backend->counter($cid, 1, 0);
    $backend->counter($cid2, 1, 0);
    $this->assertEqual($backend->counterGet($cid), $value1 + 1, 'Counter properly initializes an item.');
    $this->assertEqual($backend->counterGet($cid2), $value2 + 1, 'Counter properly initializes an item.');

    $backend->deleteMultiple([$cid, $cid2]);
    $backend->counterMultiple([$cid, $cid2], 5, $value3);
    $this->assertEqual($backend->counterGet($cid), $value3, 'Counter properly initializes an item.');
    $this->assertEqual($backend->counterGet($cid2), $value3, 'Counter properly initializes an item.');

    // If we have something stored that is
    // not numeric the system should replace
    // the old value with the default as if
    // it did not exist.
    $backend->set($cid, 'this cannot be incremented');
    $backend->counter($cid, 1, 8);
    $this->assertEqual($backend->counterGet($cid), 8, 'Counter properly initializes an item.');

    $backend->setMultiple([$cid => ['data' => 'this cannot be incremented'], $cid2 => ['data' => 'neither this one']]);
    $backend->counterMultiple([$cid, $cid2], 1, 5);
    $this->assertEqual($backend->counterGet($cid), 5, 'Counter properly initializes an item.');
    $this->assertEqual($backend->counterGet($cid2), 5, 'Counter properly initializes an item.');

    // Test remove.
    $backend->deleteAll();
    $this->assertRemoved('Counter item is removed.', $cid, $backend);

    // Trying to counterGet() something that is not numeric
    // should throw an exception.
    $backend->set($cid, 'this is not a counter');
    try {
      $backend->counterGet($cid);
      $this->fail('Cannot use counter on non numeric store.');
    }
    catch (\Exception $e) {}
  }
}
