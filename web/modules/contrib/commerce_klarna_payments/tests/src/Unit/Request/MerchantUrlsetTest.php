<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\MerchantUrlset;
use Drupal\Tests\UnitTestCase;

/**
 * Merchant url set request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\MerchantUrlset
 */
class MerchantUrlsetTest extends UnitTestCase {

  /**
   * Tests toArray() method.
   *
   * @covers ::setConfirmation
   * @covers ::setNotification
   * @covers ::setPush
   * @covers ::__construct
   * @covers ::toArray
   */
  public function testToArray() {
    $expected = [
      'confirmation' => 'http://localhost/confirmation',
      'notification' => 'http://localhost/notification',
      'push' => 'http://localhost/push',
    ];
    $urls = new MerchantUrlset();
    $urls->setPush($expected['push'])
      ->setConfirmation($expected['confirmation'])
      ->setNotification($expected['notification']);

    $this->assertEquals($expected, $urls->toArray());
    $this->assertEquals($urls, new MerchantUrlset($expected));
  }

}
