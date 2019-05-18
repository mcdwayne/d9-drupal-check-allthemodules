<?php

namespace Drupal\Tests\commerce_xero\Unit\Plugin\QueueWorker;

use Drupal\commerce_xero\CommerceXeroData;
use Drupal\commerce_xero\Plugin\QueueWorker\CommerceXeroProcess;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\TypedData\Definition\BankTransactionDefinition;
use Prophecy\Argument;

/**
 * Tests the queue worker plugin.
 *
 * @group commerce_xero
 *
 * @coversDefaultClass \Drupal\commerce_xero\Plugin\QueueWorker\CommerceXeroProcess
 */
class CommerceXeroProcessTest extends UnitTestCase {

  /**
   * A mock container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->container = new ContainerBuilder();

    // Mocks the queue factory and various queues.
    $queueProphet = $this->prophesize('\Drupal\Core\Queue\QueueInterface');
    $queueProphet->createItem(Argument::type('\Drupal\commerce_xero\CommerceXeroDataInterface'));
    $queue = $queueProphet->reveal();

    $queueFactoryProphet = $this->prophesize('\Drupal\Core\Queue\QueueFactory');
    $queueFactoryProphet->get(Argument::any())->willReturn($queue);

    // Mocks entity storage interfaces.
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyStorageProphet = $this->prophesize('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $strategyStorageProphet->load(Argument::type('string'))->willReturn($strategyProphet->reveal());

    $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $paymentStorageProphet = $this->prophesize('\Drupal\Core\Entity\ContentEntityStorageInterface');
    $paymentStorageProphet->load(Argument::type('integer'))->willReturn($paymentProphet->reveal());

    $entityTypeManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityTypeManagerProphet->getStorage('commerce_payment')->willReturn($paymentStorageProphet->reveal());
    $entityTypeManagerProphet->getStorage('commerce_xero_strategy')->willReturn($strategyStorageProphet->reveal());

    // Prophecy does not allow to mock chained methods because of its "opinion".
    $updateQuery = $this->getMockBuilder('\Drupal\Core\Database\Query\Update')
      ->disableOriginalConstructor()
      ->getMock();
    $updateQuery->expects($this->any())
      ->method('fields')
      ->willReturnSelf();
    $updateQuery->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $updateQuery->expects($this->any())
      ->method('execute');

    $databaseProphet = $this->prophesize('\Drupal\Core\Database\Connection');
    $databaseProphet->update('queue')->willReturn($updateQuery);

    // Sets the services on the container.
    $this->container->set('entity_type.manager', $entityTypeManagerProphet->reveal());
    $this->container->set('queue', $queueFactoryProphet->reveal());
    $this->container->set('database', $databaseProphet->reveal());
  }

  /**
   * Asserts that the process queue sends to the send queue.
   */
  public function testProcessItem() {
    // Mocks the processor manager return value based on the test arguments.
    $processorProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroProcessorManager');
    $processorProphet->process(
      Argument::type('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface'),
      Argument::type('\Drupal\commerce_payment\Entity\PaymentInterface'),
      Argument::type('\Drupal\Core\TypedData\ComplexDataInterface'),
      Argument::type('string')
    )->willReturn(TRUE);

    // Mocks the logger factory and logger.
    $loggerProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelInterface');
    $loggerFactoryProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerFactoryProphet->get('commerce_xero')->willReturn($loggerProphet->reveal());

    // Sets the remaining services in the container.
    $this->container->set('commerce_xero_processor.manager', $processorProphet->reveal());
    $this->container->set('logger.factory', $loggerFactoryProphet->reveal());
    \Drupal::setContainer($this->container);

    // Mocks some random typed data that won't be used.
    $typedDataProphet = $this->prophesize('\Drupal\Core\TypedData\ComplexDataInterface');
    $typedData = $typedDataProphet->reveal();

    // Creates the data to use.
    $data = new CommerceXeroData('test', 1, $typedData, 'process');

    // Creates the queue worker process via the static method for coverage.
    $configuration = [
      'id' => 'commerce_xero_process',
    ];
    $plugin_definition = [
      'id' => 'commerce_xero_process',
      'title' => 'Commerce Xero Process',
      'cron' => ['time' => 60],
      'class' => '\Drupal\commerce_xero\Plugin\QueueWorker\CommerceXeroProcess',
    ];
    $worker = CommerceXeroProcess::create($this->container, $configuration, 'commerce_xero_process', $plugin_definition);

    $worker->processItem($data);

    $this->assertEquals('send', $data->getExecutionState());
  }

  /**
   * Asserts that the correct logger method was called for the given test.
   *
   * @param bool $processStatus
   *   The processor manager result.
   * @param string $loggerMethod
   *   The logger method to assert.
   * @param string $loggerMessage
   *   The logger message to assert.
   *
   * @dataProvider processItemLogProvider
   *
   * @doesNotPerformAssertions
   *
   * @throws \Exception
   */
  public function testProcessItemLog($processStatus, $loggerMethod, $loggerMessage) {
    // Mocks the processor manager return value based on the test arguments.
    $processorProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroProcessorManager');
    $processorProphet->process(
      Argument::type('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface'),
      Argument::type('\Drupal\commerce_payment\Entity\PaymentInterface'),
      Argument::type('\Drupal\Core\TypedData\ComplexDataInterface'),
      Argument::type('string')
    )->willReturn($processStatus);

    // Mocks the logger factory and logger to expect the appropriate messages.
    $loggerProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelInterface');
    $loggerProphet
      ->{$loggerMethod}($loggerMessage, Argument::type('array'))
      ->shouldBeCalled();
    $loggerFactoryProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerFactoryProphet->get('commerce_xero')->willReturn($loggerProphet->reveal());

    // Sets the remaining services in the container.
    $this->container->set('commerce_xero_processor.manager', $processorProphet->reveal());
    $this->container->set('logger.factory', $loggerFactoryProphet->reveal());
    \Drupal::setContainer($this->container);

    // Mocks some random typed data that won't be used.
    $transactionDefinition = new BankTransactionDefinition([
      'class' => '\Drupal\xero\Plugin\DataType\BankTransaction',
      'type' => 'xero_bank_transaction',
    ]);
    $typedDataProphet = $this->prophesize('\Drupal\Core\TypedData\ComplexDataInterface');
    $typedDataProphet->getDataDefinition()->willReturn($transactionDefinition);
    $typedData = $typedDataProphet->reveal();

    // Creates the data to use.
    $data = new CommerceXeroData('test', 1, $typedData, 'send');

    // Creates the queue worker process via the static method for coverage.
    $configuration = [
      'id' => 'commerce_xero_process',
    ];
    $plugin_defintion = [
      'id' => 'commerce_xero_process',
      'title' => 'Commerce Xero Process',
      'cron' => ['time' => 60],
      'class' => '\Drupal\commerce_xero\Plugin\QueueWorker\CommerceXeroProcess',
    ];
    $worker = CommerceXeroProcess::create($this->container, $configuration, 'commercee_xero_process', $plugin_defintion);

    $worker->processItem($data);
  }

  /**
   * Provides test arguments for the testProcessItemLog method.
   *
   * @return array
   *   An array of test arguments.
   */
  public function processItemLogProvider() {
    return [
      'send queue, no failure' => [
        TRUE,
        'info',
        'Successfully posted @type to Xero for payment @id using strategy @strategy',
      ],
      'send queue, failure' => [
        FALSE,
        'error',
        'Error posting @type to Xero for payment @id using strategy @strategy',
      ],
    ];
  }

}
