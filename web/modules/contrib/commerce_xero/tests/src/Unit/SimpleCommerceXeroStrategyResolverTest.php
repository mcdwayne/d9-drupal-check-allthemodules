<?php

namespace Drupal\Tests\commerce_xero\Unit;

use Drupal\commerce_xero\SimpleCommerceXeroStrategyResolver;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the simple commerce xero strategy resolver.
 *
 * @group commerce_xero
 */
class SimpleCommerceXeroStrategyResolverTest extends UnitTestCase {

  /**
   * Asserts the resolve method returning a strategy entity.
   *
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface[] $strategies
   *   A set of strategies.
   * @param string $payment_gateway
   *   The payment gateway to set on the payment entity.
   *
   * @dataProvider provideStrategies
   */
  public function testResolve(array $strategies, $payment_gateway = '') {
    $expected = empty($strategies) ? FALSE : reset($strategies);

    $storageProphet = $this->prophesize('\Drupal\Core\Entity\EntityStorageInterface');
    $storageProphet
      ->loadByProperties(['payment_gateway' => $payment_gateway])
      ->willReturn($strategies);

    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet
      ->getStorage('commerce_xero_strategy')
      ->willReturn($storageProphet->reveal());

    $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $paymentProphet->getPaymentGatewayId()->willReturn($payment_gateway);

    $resolver = new SimpleCommerceXeroStrategyResolver($entityManagerProphet->reveal());

    $this->assertEquals($expected, $resolver->resolve($paymentProphet->reveal()));
  }

  /**
   * Provide commerce xero strategy expected and test parameters.
   *
   * @return array
   *   An array of test parameters.
   */
  public function provideStrategies() {
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');

    return [
      [[], 'none'],
      [[1 => $strategyProphet->reveal()], 'cash'],
    ];
  }

}
