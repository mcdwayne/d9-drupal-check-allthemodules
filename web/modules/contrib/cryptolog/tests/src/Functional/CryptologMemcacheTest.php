<?php

namespace Drupal\Tests\cryptolog\Functional;

use PHPUnit_Framework_SkippedTestError;

/**
 * Tests Cryptolog with the Memcache contributed module.
 *
 * @group cryptolog
 * @requires module memcache
 */
class CryptologMemcacheTest extends CryptologTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'cryptolog', 'memcache'];

  /**
   * {@inheritdoc}
   */
  protected function checkRequirements() {
    parent::checkRequirements();
    if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
      throw new PHPUnit_Framework_SkippedTestError('Required PHP extension: memcache or memcached');
    }
  }

}
