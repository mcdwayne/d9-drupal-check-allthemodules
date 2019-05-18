<?php

namespace Drupal\Tests\commerce_partpay\Unit;

use Drupal\commerce_partpay\PartPay\PartPayService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\commerce_partpay\PartPay\PartPayService
 *
 * @group commerce_partpay
 */
class PartPayServiceTest extends UnitTestCase {

  protected $paymentExpressMock;

  /**
   * PartPay gateway.
   *
   * @var \Drupal\commerce_partpay\PartPay\PartPayService
   */
  protected $partPayService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();

    $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->container = new ContainerBuilder();

    $this->container->set('logger.factory', $this->loggerMock);

    $this->partPayService = new PartPayService($this->loggerMock);
  }

}
