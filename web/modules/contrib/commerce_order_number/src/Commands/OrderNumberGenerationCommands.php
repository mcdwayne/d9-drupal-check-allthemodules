<?php

namespace Drupal\commerce_order_number\Commands;

use Drupal\commerce_order_number\OrderNumber;
use Drupal\commerce_order_number\OrderNumberGenerationServiceInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for leveraging order number generation commands.
 */
class OrderNumberGenerationCommands extends DrushCommands {

  /**
   * The order number generation service.
   *
   * @var \Drupal\commerce_order_number\OrderNumberGenerationServiceInterface
   */
  protected $orderNumberGenerationService;

  /**
   * Constructs a new OrderNumberSubscriber object.
   *
   * @param \Drupal\commerce_order_number\OrderNumberGenerationServiceInterface $order_number_generation_service
   *   The order number generation service.
   */
  public function __construct(OrderNumberGenerationServiceInterface $order_number_generation_service) {
    $this->orderNumberGenerationService = $order_number_generation_service;
  }

  /**
   * Reset the last order number in the system. Use with caution!
   *
   * @param int $increment_number
   *   The order increment number to set.
   *
   * @option year int|null
   *   The year. If NULL, the current year will be assumed.
   *
   * @option month int|null
   *   The month. If NULL, the current year will be assumed.
   *
   * @command commerce_order_number:reset-last-number
   *
   * @usage commerce_order_number:reset-last-number 17 --year=2018 --month=1
   *   Resets the last generated order number to increment number 17 and its
   *   period to the year 2018 and the month january.
   *
   * @validate-module-enabled commerce_order_number
   *
   * @throws \InvalidArgumentException
   *   If the parameters used are incomplete or invalid.
   */
  public function resetLastOrderNumber($increment_number, array $options = ['year' => NULL, 'month' => NULL]) {
    if (empty($increment_number) || !is_numeric($increment_number) || $increment_number < 1) {
      throw new \InvalidArgumentException('You must specify a valid positive integer for the increment number.');
    }

    $year = $options['year'];
    if (is_null($year)) {
      $year = date('Y');
    }
    else {
      if (!is_numeric($year) || $year < 1900) {
        throw new \InvalidArgumentException('You have specified an invalid year.');
      }
    }

    $month = $options['month'];
    if (is_null($month)) {
      $month = date('m');
    }
    else {
      if (!is_numeric($month) || $month < 1 || $month > 12) {
        throw new \InvalidArgumentException('You have specified an invalid month.');
      }
    }
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);

    $order_number = new OrderNumber($increment_number, $year, $month);
    $this->orderNumberGenerationService->resetLastOrderNumber($order_number);
  }

}
