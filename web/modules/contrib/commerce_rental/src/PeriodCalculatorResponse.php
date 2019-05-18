<?php

namespace Drupal\commerce_rental;

final class PeriodCalculatorResponse {

  /**
   * How many times the RentalPeriod was applied
   *
   * @var int $quantity
   */
  protected $quantity;

  /**
   * The new date that the next RentalPeriod should start from.
   *
   * @var \DateTime $newDate
   */
  protected $newDate;

  public function __construct(int $quantity, \DateTime $new_date) {
    $this->quantity = $quantity;
    $this->newDate = $new_date;
  }

  /**
   * @return int
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * @return \DateTime
   */
  public function getNewDate() {
    return $this->newDate;
  }

}