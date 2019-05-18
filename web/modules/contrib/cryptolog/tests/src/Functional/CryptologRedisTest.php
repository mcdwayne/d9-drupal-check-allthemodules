<?php

namespace Drupal\Tests\cryptolog\Functional;

use PHPUnit_Framework_SkippedTestError;

/**
 * Tests Cryptolog with the Redis contributed module.
 *
 * @group cryptolog
 * @requires module redis
 */
class CryptologRedisTest extends CryptologTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'cryptolog', 'redis'];

  /**
   * {@inheritdoc}
   */
  protected function checkRequirements() {
    parent::checkRequirements();
    if (!extension_loaded('redis')) {
      throw new PHPUnit_Framework_SkippedTestError('Required PHP extension: redis');
    }
  }

}
