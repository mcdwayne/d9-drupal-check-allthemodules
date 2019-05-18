<?php

namespace Drupal\couchbasedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGetMultipleTests as GetMultipleTests;

class RawBackendGetMultipleTests extends GetMultipleTests {
  use RawBackendGeneralTestCaseTrait;

  /**
   * Test the retrieve with prefixes.
   */
  public function testRetrieveWithPrefix() {
    /** @var \Drupal\couchbasedrupal\Cache\CouchbaseBackend */
    $backend = $this->backend;

    /** @var \Drupal\couchbasedrupal\Cache\CouchbaseBackend */
    $backend2 = $this->backend2;

    $backend->set('en:un:lugar:de:la:mancha', 'value');
    $backend->set('en:un:sitio:de:castilla', 'value');
    $backend->set('en:un:sitio:de:cataluña', 'value');

    $this->assertEquals(2, count($backend->getAll('en:un:sitio')));
    $this->assertEquals(1, count($backend->getAll('en:un:lugar:')));

    $this->assertEquals(2, count($backend->getAllKeys('en:un:sitio')));
    $this->assertEquals(1, count($backend->getAllKeys('en:un:lugar:')));

    // Make sure there is no leakage between binaries.
    $this->assertEquals(0, count($backend2->getAll('en:un:sitio')));
    $this->assertEquals(0, count($backend2->getAll('en:un:lugar:')));

    $this->assertEquals(0, count($backend2->getAllKeys('en:un:sitio')));
    $this->assertEquals(0, count($backend2->getAllKeys('en:un:lugar:')));
  }
}
