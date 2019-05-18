<?php

namespace Drupal\commerce_rental;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_rental\Entity\RentalPeriodInterface;

final class RentalRate {

  /**
   * @var string
   */
  protected $id;

  /**
   * @var $rentalPeriod \Drupal\commerce_rental\Entity\RentalPeriodInterface
   */
  protected $rentalPeriod;

  /**
   * @var $price \Drupal\commerce_price\Price
   */
  protected $price;

  /**
   * @var $variationId string
   */
  protected $variationId;

  public function __construct(RentalPeriodInterface $rental_period, Price $price, ProductVariationInterface $product_variation) {
    $this->rentalPeriod = $rental_period;
    $this->price = $price;
    $this->variationId = $product_variation->id();
    $this->id = $this->rentalPeriod->id();
  }

  public function getRentalPeriod() {
    return $this->rentalPeriod;
  }

  public function getPrice() {
    return $this->price;
  }

  public function getVariationId() {
    return $this->variationId;
  }

  public function getId() {
    return $this->id;
  }

}