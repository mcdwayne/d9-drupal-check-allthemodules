<?php

namespace Drupal\Tests\superfaktura\Unit;

use Drupal\commerce_order\Entity\Order;
use Drupal\superfaktura\InvoiceService;
use Drupal\Tests\UnitTestCase;

/**
 * Test description.
 *
 * @group superfaktura
 */
class InvoiceServiceTest extends UnitTestCase {

  /**
   * Testing computing of due date.
   */
  public function testComputeDueDate() {
    $logger_factory = $this->getMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $config_factory = $this->getConfigFactoryStub([
      'superfaktura.settings' => [
        'maturity' => 1,
      ],
    ]);
    $language_manager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $service = new InvoiceService($config_factory, $logger_factory, $language_manager);

    $order = $this->prophesize(Order::class);

    $order_placed_time = 1;

    $order->getPlacedTime()->willReturn($order_placed_time);

    $due_date = $service->computeDueDate($order->reveal());

    // Maturity is number of days.
    // Due date is maturity in seconds added on top of order placed time.
    $expected_due_date = 86400 + $order_placed_time;

    $this->assertEquals($expected_due_date, $due_date);
  }

}
