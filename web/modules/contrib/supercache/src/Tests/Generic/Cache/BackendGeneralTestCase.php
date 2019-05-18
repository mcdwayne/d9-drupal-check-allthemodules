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
abstract class BackendGeneralTestCase extends KernelTestBase {

  /**
   * A cache backend.
   * 
   * @var \Drupal\supercache\Cache\CacheInterface
   */
  protected $backend = NULL;

  /**
   * Another cache backend.
   *
   * @var \Drupal\supercache\Cache\CacheInterface
   */
  protected $backend2 = NULL;

  protected $defaultcid = 'test_temporary';
  protected $defaultvalue = 'default value';

  protected function randomName($length = 8) {
    $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
    $max = count($values) - 1;
    $str = chr(mt_rand(97, 122));
    for ($i = 1; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }
    return $str;
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check or a variable is stored and restored properly.
   */
  public function checkVariable($var) {
    $this->backend->set('test_var', $var, CacheBackendInterface::CACHE_PERMANENT);
    $cache = $this->backend->get('test_var');
    $this->assertTrue(
      isset($cache->data) && $cache->data === $var,
      (new FormattableMarkup('@type is saved and restored properly.',
      array('@type' => Unicode::ucfirst(gettype($var)))))->__toString()
    );
  }

  /**
   * Assert or a cache entry has been removed.
   *
   * @param string $message
   *   Message to display.
   *
   * @param string $cid
   *   The cache id.
   */
  public function assertRemoved($message, $cid = NULL, $backend = NULL) {
    if (empty($backend)) {
      $backend = $this->backend;
    }
    if ($cid == NULL) {
      $cid = $this->defaultcid;
    }
    $cache = $backend->get($cid);
    $this->assertFalse($cache, $message);
  }

  /**
   * Perform the general wipe.
   */
  protected function generalWipe() {
    $this->backend->removeBin();
  }

  /**
   * Check whether or not a WinCache entry exists.
   *
   * @param string $cid
   *   The WinCache id.
   *
   * @param string $var
   *   The variable the cache should contain.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function checkExists($cid, $var, $backend = NULL) {
    if (empty($backend)) {
      $backend = $this->backend;
    }
    $cache = $backend->get($cid);
    return isset($cache->data) && $cache->data == $var;
  }

  /**
   * Assert or a  entry exists.
   *
   * @param string $message
   *   Message to display.
   *
   * @param string $var
   *   The variable the WinCache should contain.
   *
   * @param string $cid
   *   The cache id.
   */
  protected function assertExists($message, $var = NULL, $cid = NULL, $backend = NULL) {
    $this->assertTrue($this->checkExists($cid, $var, $backend), $message);
  }

  /**
   * Summary of refreshRequestTime
   * 
   * @param mixed $backend 
   */
  protected function refreshRequestTime($backend) {
    if ($backend instanceof \Drupal\supercache\Cache\CacheRawBackendInterface) {
      $backend->refreshRequestTime();
    }
  }

}