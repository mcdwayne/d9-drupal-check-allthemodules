<?php

namespace Drupal\Tests\commerce_xero\Unit\Plugin\Action;

use Drupal\commerce_xero\Plugin\Action\ProcessPayment;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the process payment action.
 *
 * @group commerce_xero
 */
class ProcessPaymentTest extends UnitTestCase {

  /**
   * Asserts the action execution errors correctly.
   *
   * @param string $failState
   *   The execution state.
   *
   * @dataProvider dependencyDataProvider
   */
  public function testExecuteError($failState) {
    $configuration = [];
    $definition = [
      'id' => 'commerce_xero_process_payment_action',
      'title' => 'Process Payment to Xero',
      'type' => 'commerce_payment',
    ];

    $dataProphet = $this->prophesize('\Drupal\Core\TypedData\ComplexDataInterface');
    $data = $dataProphet->reveal();

    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->id()->willReturn('test_strategy');
    $strategy = $strategyProphet->reveal();

    $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $paymentProphet->id()->willReturn(1);
    $payment = $paymentProphet->reveal();

    $resolverProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroStrategyResolverInterface');
    $resolverProphet->resolve($payment)->willReturn($strategy);

    $dataManagerProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroDataTypeManager');
    $dataManagerProphet->createData($payment, $strategy)->willReturn($data);

    $processManagerProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroProcessorManager');
    $processManagerProphet
      ->process(Argument::any(), Argument::any(), Argument::any(), Argument::any())
      ->will(function ($args) use ($failState) {
        return $args[3] !== $failState;
      });

    $loggerProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelInterface');
    $loggerProphet->error(
      'Failed to run @state plugins for payment @payment, strategy @strategy',
      [
        '@state' => $failState,
        '@payment' => 1,
        '@strategy' => 'test_strategy',
      ]
    )->shouldBeCalled();

    $loggerFactoryProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerFactoryProphet->get('commerce_xero')->willReturn($loggerProphet->reveal());

    $container = new ContainerBuilder();
    $container->set('logger.factory', $loggerFactoryProphet->reveal());
    $container->set('commerce_xero_strategy_simple_resolver', $resolverProphet->reveal());
    $container->set('commerce_xero_data_type.manager', $dataManagerProphet->reveal());
    $container->set('commerce_xero_processor.manager', $processManagerProphet->reveal());

    $action = ProcessPayment::create($container, $configuration, 'commerce_xero_process_payment_action', $definition);

    // The assertion is in the shouldBeCalled prophecy method above.
    $action->execute($payment);
  }

  /**
   * Provides test arguments to mock dependencies.
   *
   * @return array
   *   An array of test arguments keyed by the test case description.
   */
  public function dependencyDataProvider() {
    return [
      'immediate execution fails' => ['immediate'],
      'process execution fails' => ['process'],
      'send execution fails' => ['send'],
    ];
  }

}
