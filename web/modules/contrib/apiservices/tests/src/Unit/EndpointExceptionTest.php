<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\EndpointExceptionTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\Exception\EndpointException;
use Drupal\Tests\UnitTestCase;

/**
 * @group apiservices
 */
class EndpointExceptionTest extends UnitTestCase {

  /**
   * Tests that the exception can be created with a response.
   */
  public function testWithResponse() {
    $response = $this->prophesize('Drupal\apiservices\ApiResponseInterface');
    $response->getStatusCode()->willReturn(404);
    $e = new EndpointException('test', $response->reveal());
    $this->assertTrue($e->hasResponse());
    $this->assertEquals(404, $e->getResponse()->getStatusCode());
  }

  /**
   * Tests that the exception can be created without a response.
   */
  public function testWithoutResponse() {
    $e = new EndpointException('test', NULL);
    $this->assertFalse($e->hasResponse());
    $this->assertNull($e->getResponse());
  }

}
