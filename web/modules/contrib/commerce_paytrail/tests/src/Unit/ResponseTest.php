<?php

namespace Drupal\Tests\commerce_paytrail\Unit;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paytrail\Exception\InvalidValueException;
use Drupal\commerce_paytrail\Exception\SecurityHashMismatchException;
use Drupal\commerce_paytrail\Repository\Response;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Response unit tests.
 *
 * @group commerce_paytrail
 * @coversDefaultClass \Drupal\commerce_paytrail\Repository\Response
 */
class ResponseTest extends UnitTestCase {

  /**
   * Tests createFromRequest().
   *
   * @covers ::createFromRequest
   * @covers ::setAuthCode
   * @covers ::setOrderNumber
   * @covers ::setPaymentId
   * @covers ::setPaymentMethod
   * @covers ::setTimestamp
   * @covers ::setPaymentStatus
   */
  public function testCreateFromRequest() {
    $order = $this->getMock(OrderInterface::class);
    $request = Request::createFromGlobals();

    try {
      Response::createFromRequest('1234', $order, $request);
      $this->fail();
    }
    catch (InvalidValueException $e) {
    }

    try {
      $request->query = new ParameterBag([
        'ORDER_NUMBER' => '123',
        'PAYMENT_ID' => '2333',
        'PAYMENT_METHOD' => '1',
        'TIMESTAMP' => time(),
        'STATUS' => 'RANDOM',
        'RETURN_AUTHCODE' => 'dsads',
      ]);
      // Make sure we can't set invalid status.
      Response::createFromRequest('1234', $order, $request);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
    }

    $request->query->set('STATUS', 'PAID');
    $response = Response::createFromRequest('1234', $order, $request);

    $this->assertInstanceOf(Response::class, $response);
  }

  /**
   * Tests isValidResponse().
   *
   * @covers ::isValidResponse
   * @covers ::getTimestamp
   * @covers ::getOrderNumber
   * @covers ::getPaymentId
   * @covers ::getPaymentStatus
   * @covers ::getOrder
   * @covers ::getAuthCode
   * @covers ::generateReturnChecksum
   * @covers \Drupal\commerce_paytrail\Repository\BaseResource
   */
  public function testIsValidResponse() {
    $order = $this->getMock(OrderInterface::class);
    $request = Request::createFromGlobals();

    $request->query = new ParameterBag([
      'ORDER_NUMBER' => '123',
      'PAYMENT_ID' => '2333',
      'PAYMENT_METHOD' => '1',
      'TIMESTAMP' => 1512281966,
      'STATUS' => 'CANCELLED',
      'RETURN_AUTHCODE' => 'dsads',
    ]);
    $response = Response::createFromRequest('1234', $order, $request);

    try {
      $response->isValidResponse();
      $this->fail('Expected SecurityHashMismatchException');
    }
    catch (SecurityHashMismatchException $e) {
      $this->assertEquals('Validation failed (invalid payment state)', $e->getMessage());
    }

    $request->query->set('STATUS', 'PAID');
    $response = Response::createFromRequest('1234', $order, $request);

    try {
      $response->isValidResponse();
      $this->fail('Expected SecurityHashMismatchException');
    }
    catch (SecurityHashMismatchException $e) {
      $this->assertEquals('Validation failed (order number mismatch)', $e->getMessage());
    }

    $order->expects($this->any())
      ->method('id')
      ->will($this->returnValue('123'));

    try {
      $response->isValidResponse();
      $this->fail('Expected SecurityHashMismatchException');
    }
    catch (SecurityHashMismatchException $e) {
      $this->assertEquals('Validation failed (security hash mismatch)', $e->getMessage());
    }

    // Test with correct authcode.
    $request->query->set('RETURN_AUTHCODE', 'A615F71585C0C1E04577E5B5DC79EF380045EA2644EEEA6B147049E94B8A7C49');
    $response = Response::createFromRequest('1234', $order, $request);

    $response->isValidResponse();
  }

}
