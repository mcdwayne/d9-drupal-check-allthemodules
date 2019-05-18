<?php

namespace Drupal\Tests\new_relic_rpm\Unit\RouteEnhancer;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\new_relic_rpm\RouteEnhancer\TransactionNameEnhancer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\new_relic_rpm\RouteEnhancer\TransactionNameEnhancer
 * @group new_relic_rpm
 */
class TransactionNameEnhancerTest extends UnitTestCase {

  /**
   * Test callback for _translation_name_callback with known response.
   *
   * @return string
   *   'foo_resolved'
   */
  public static function transactionNameCallback() {
    return 'foo_resolved';
  }

  /**
   * @covers ::enhance
   */
  public function testEnhance() {
    $request = new Request();
    $callback = [self::class, 'transactionNameCallback'];
    $resolver = $this->prophesize(ControllerResolverInterface::class);
    $resolver->getControllerFromDefinition($callback)->willReturn($callback);
    $resolver->getArguments(Argument::type(Request::class), $callback)
      ->willReturn([]);

    $enhancer = new TransactionNameEnhancer($resolver->reveal());

    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => new Route('/foo', ['_transaction_name_callback' => $callback]),
    ];

    $defaults = $enhancer->enhance($defaults, $request);
    $this->assertEquals('foo_resolved', $defaults['_transaction_name']);
  }

}
