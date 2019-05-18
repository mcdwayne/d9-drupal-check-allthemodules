<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\Payment\AuthorizationRequest;
use Drupal\Tests\UnitTestCase;

/**
 * Authorization request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Payment\AuthorizationRequest
 */
class AuthorizationRequestTest extends UnitTestCase {

  /**
   * Tests toArray() method.
   *
   * @covers ::toArray
   * @covers ::setAllowAutoCapture
   */
  public function testToArray() {
    $request = new AuthorizationRequest();
    $request->setAllowAutoCapture(TRUE);

    $this->assertEquals(['auto_capture' => TRUE], $request->toArray());
  }

}
