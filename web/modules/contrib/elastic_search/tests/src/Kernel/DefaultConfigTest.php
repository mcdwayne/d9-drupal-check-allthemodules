<?php

namespace Drupal\Tests\elastic_search\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * DefaultConfigTest
 *
 * @group elastic_search
 */
class DefaultConfigTest extends KernelTestBase {

  protected static $modules = ['elastic_search'];

  protected function setUp() {
    parent::setUp();
  }

  public function testDefaultConfig() {
    $this->installConfig(static::$modules);
  }

}
