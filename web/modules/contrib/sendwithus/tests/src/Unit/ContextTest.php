<?php

namespace Drupal\Tests\sendwithus\Unit;

use Drupal\sendwithus\Context;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Context unit tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\Context
 */
class ContextTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::getModule
   * @covers ::getKey
   * @covers ::getData
   */
  public function testDefault() {
    $sut = new Context('test_module', 'test_id', new ParameterBag([]));
    $this->assertEquals('test_module', $sut->getModule());
    $this->assertEquals('test_id', $sut->getKey());
    $this->assertInstanceOf(ParameterBag::class, $sut->getData());
  }

}
