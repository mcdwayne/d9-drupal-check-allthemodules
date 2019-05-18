<?php

namespace Drupal\Tests\commerce_xero\Unit\EventSubscriber {

  use Drupal\commerce_xero\EventSubscriber\OrderSubscriber;
  use Drupal\Core\DependencyInjection\ContainerBuilder;
  use Drupal\state_machine\Event\WorkflowTransitionEvent;
  use Drupal\state_machine\Plugin\Workflow\WorkflowState;
  use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
  use Drupal\Tests\UnitTestCase;
  use Drupal\Tests\commerce_xero\Unit\CommerceXeroDataTestTrait;
  use Drupal\xero\Plugin\DataType\BankTransaction;
  use Drupal\xero\TypedData\Definition\AddressDefinition;
  use Drupal\xero\TypedData\Definition\BankTransactionDefinition;
  use Drupal\xero\TypedData\Definition\LineItemDefinition;
  use Drupal\xero\TypedData\Definition\PhoneDefinition;
  use Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition;
  use Prophecy\Argument;

  /**
   * Tests the order/payment event subscriber.
   *
   * @group commerce_xero
   */
  class OrderSubscriberTest extends UnitTestCase {

    use CommerceXeroDataTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      $container = new ContainerBuilder();

      $this->createTypedDataProphet();

      $container->set('typed_data_manager', $this->typedDataManagerProphet->reveal());
      \Drupal::setContainer($container);

      $trackingOptionDefinition = TrackingCategoryOptionDefinition::create('xero_tracking_option');
      $lineItemDefinition = LineItemDefinition::create('xero_line_item');
      $addressDefinition = AddressDefinition::create('xero_address');
      $phoneDefinition = PhoneDefinition::create('xero_phone');

      $this->mockTypedData('list', [[]], 0, $trackingOptionDefinition);
      $this->mockTypedData('list', [[]], 0, $lineItemDefinition);
      $this->mockTypedData('list', [[]], 0, $addressDefinition);
      $this->mockTypedData('list', [[]], 0, $phoneDefinition);

      $this->mockTypedData('xero_bank_transaction', []);

      $container->set('string_translation', $this->getStringTranslationStub());
      $container->set('typed_data_manager', $this->typedDataManagerProphet->reveal());
      \Drupal::setContainer($container);
    }

    /**
     * Asserts that the events are returned.
     */
    public function testGetSubscribedEvents() {
      $expected = ['commerce_payment.post_transition' => 'onPaymentReceived'];
      $this->assertArrayEquals($expected, OrderSubscriber::getSubscribedEvents());
    }

    /**
     * Asserts the event subscriber method.
     */
    public function testOnPaymentReceived() {
      $loggerProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelInterface');
      $loggerProphet->debug(Argument::any())->shouldNotBeCalled();
      $loggerProphet->error(Argument::any())->shouldNotBeCalled();

      $from = new WorkflowState('from', 'From');
      $to = new WorkflowState('to', 'To');

      $stateItemProphet = $this->prophesize('\Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface');
      $stateItemProphet->getOriginalId()->willReturn('from');
      $stateItemsProphet = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
      $stateItemsProphet->first()->willReturn($stateItemProphet->reveal());

      $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
      $paymentProphet->getPaymentGatewayId()->willReturn('cash');
      $paymentProphet->id()->willReturn('payment_id');
      $paymentProphet->get('state')->willReturn($stateItemsProphet->reveal());
      $payment = $paymentProphet->reveal();

      $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
      $strategyProphet->id()->willReturn('test');
      $strategyProphet->label()->willReturn('Test');
      $strategy = $strategyProphet->reveal();

      $resolverProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroStrategyResolverInterface');
      $resolverProphet
        ->resolve($paymentProphet->reveal())
        ->willReturn($strategy);

      $workflowProphet = $this->prophesize('\Drupal\state_machine\Plugin\Workflow\WorkflowInterface');
      $workflowProphet->getState(Argument::any())->willReturn($from);
      $transition = new WorkflowTransition('receive', 'Receive', ['from' => $from], $to);

      $dataManagerProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroDataTypeManager');
      $definition = new BankTransactionDefinition();
      $bankTransaction = new BankTransaction($definition);

      $dataManagerProphet
        ->createData($payment, $strategy)
        ->willReturn($bankTransaction);

      $processorProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroProcessorManager');
      $processorProphet
        ->process($strategy, $payment, $bankTransaction, 'immediate')
        ->willReturn(TRUE);

      $queueProphet = $this->prophesize('\Drupal\Core\Queue\QueueInterface');
      $queueProphet
        ->createItem(Argument::any())
        ->willReturn(1);

      $queueFactoryProphet = $this->prophesize('\Drupal\Core\Queue\QueueFactory');
      $queueFactoryProphet->get('commerce_xero_process')
        ->willReturn($queueProphet->reveal());

      $subscriber = new OrderSubscriber(
        $resolverProphet->reveal(),
        $dataManagerProphet->reveal(),
        $processorProphet->reveal(),
        $loggerProphet->reveal(),
        $queueFactoryProphet->reveal()
      );
      $event = new WorkflowTransitionEvent($transition, $workflowProphet->reveal(), $payment, 'state');

      $subscriber->onPaymentReceived($event);

      $this->assertTrue(TRUE, 'No exception was thrown.');
    }

  }
}

// @todo https://www.drupal.org/project/state_machine/issues/2982708
namespace Drupal\state_machine\Plugin\Workflow {

  if (!function_exists('t')) {

    function t($string, array $args = []) {
      return strtr($string, $args);
    }

  }
}
