<?php

namespace Drupal\drupal_inquicker\Tests\Source;

use Drupal\drupal_inquicker\Source\DummySource;
use PHPUnit\Framework\TestCase;

/**
 * Test DummySource.
 *
 * @group myproject
 */
class DummySourceTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(DummySource::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
