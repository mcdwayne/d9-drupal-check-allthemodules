<?php

namespace Drupal\Tests\cleverreach\Unit;

use CleverReach\Infrastructure\ServiceRegister;
use Drupal\Tests\UnitTestCase;

/**
 * @group cleverreach
 */
class CoreInitilizer extends UnitTestCase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'cleverreach',
  ];

  /**
   *
   */
  public function testCoreAutoloader() {
    (new \CoreAutoloader())->load();
    $this->assertInstanceOf(ServiceRegister::class, ServiceRegister::getInstance());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    require_once '../../../lib/CoreAutoloader.php';
  }

}
