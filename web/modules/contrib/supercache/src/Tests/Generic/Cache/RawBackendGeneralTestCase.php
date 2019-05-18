<?php

namespace Drupal\supercache\Tests\Generic\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

use Drupal\couchbasedrupal\Cache\CouchbaseBackend;
use Drupal\couchbasedrupal\Cache\CouchbaseBackendFactory;
use Drupal\couchbasedrupal\Cache\DummyTagChecksum;

use Drupal\Core\Site\Settings;

/**
 * Testea funciones basicas.
 *
 * @group Cache
 */
abstract class RawBackendGeneralTestCase extends BackendGeneralTestCase {

  /**
   * A cache backend.
   * 
   * @var \Drupal\supercache\Cache\CacheRawBackendInterface
   */
  protected $backend = NULL;

  /**
   * Another cache backend.
   *
   * @var \Drupal\supercache\Cache\CacheRawBackendInterface
   */
  protected $backend2 = NULL;
}