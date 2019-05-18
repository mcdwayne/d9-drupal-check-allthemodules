<?php

namespace Drupal\commerce_rental;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_rental\Entity\RentalPeriod;
use Drupal\Core\Cache\CacheBackendInterface;

class RentalRateHelper {

  /**
   * Array of RentalRate objects.
   *
   * @var \Drupal\commerce_rental\RentalRate[] $rates
   */
  protected $rates = [];

  /**
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface $productVariationVariation;
   */
  protected $productVariation;

  protected function initializeRates() {
    $rental_rates = $this->productVariation->get('rental_rates');
    foreach ($rental_rates as $rental_rate) {
      $rental_period = RentalPeriod::load($rental_rate->period_id);
      $price = new Price($rental_rate->number, $rental_rate->currency_code);
      $rate = new RentalRate($rental_period, $price, $this->productVariation);
      $this->addRate($rate);
    }
    $this->save();
  }

  public function setProductVariation(ProductVariation $product_variation) {
    $this->productVariation = $product_variation;
    $this->initializeRates();
    return $this;
  }

  public function getProductVariation() {
    return $this->productVariation;
  }

  public function getRates() {
    return $this->getHourRates() + $this->getDayRates();
  }

  public function getRateById($id) {
    if (array_key_exists($id, $this->rates)) {
      return $this->rates[$id];
    }
    return FALSE;
  }

  /**
   * @return \Drupal\commerce_rental\RentalRate|null
   */
  public function getCheapestRate() {
    if ($hour_rates = $this->getHourRates()) {
      return end($hour_rates);
    }
    if ($day_rates = $this->getDayRates()) {
      return end($day_rates);
    }
    return NULL;
  }

  /**
   * @return \Drupal\commerce_rental\RentalRate[]|array
   */
  public function getHourRates() {
    $hour_rates = [];

    foreach ($this->rates as $rate) {
      $rental_period = $rate->getRentalPeriod();
      if ($rental_period->getGranularity() == RentalPeriod::GRANULARITY_HOURS) {
        $hour_rates[] = $rate;
      }
    }

    if (!empty($hour_rates)) {
      uasort($hour_rates, array($this, "compare"));
    }

    return $hour_rates;
  }

  /**
   * @return \Drupal\commerce_rental\RentalRate[]|array
   */
  public function getDayRates() {
    $day_rates = [];

    foreach ($this->rates as $rate) {
      $rental_period = $rate->getRentalPeriod();
      if ($rental_period->getGranularity() == RentalPeriod::GRANULARITY_DAYS) {
        $day_rates[] = $rate;
      }
    }

    if (!empty($day_rates)) {
      uasort($day_rates, array($this, "compare"));
    }

    return $day_rates;
  }

  /**
   * @param \Drupal\commerce_rental\RentalRate $rate1
   * @param \Drupal\commerce_rental\RentalRate $rate2
   *
   * @return mixed
   */
  protected function compare($rate1, $rate2) {
    return $rate2->getRentalPeriod()->getTimeUnits() - $rate1->getRentalPeriod()->getTimeUnits();
  }

  public function addRate(RentalRate $rate) {
    $this->rates[$rate->getId()] = $rate;
  }

  public function removeRate($id) {
    if (array_key_exists($id, $this->rates)) {
      unset($this->rates[$id]);
    }
  }

  public function removeAllRates() {
    $this->rates = [];
  }

  /**
   * Calculates the price based on the variation rental rates and the dates specified.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function calculatePrice(OrderItemInterface $order_item) {
    $rental_quantities = $this->extractRentalQuantities($order_item);
    $price_number = 0;
    foreach ($rental_quantities as $rental_quantity) {
      $rate = $this->getRateById($rental_quantity['period_id']);
      $rate_number = (float)$rate->getPrice()->getNumber();
      $quantity = (float)$rental_quantity['quantity'];
      $price_number += $rate_number * $quantity;
    }
    return new Price((string)$price_number, 'USD');
  }

  protected function extractRentalQuantities(OrderItemInterface $order_item) {
    $rental_quantities = [];
    $items = $order_item->get('rental_quantity')->getValue();
    foreach ($items as $item) {
      $rental_quantities[] = [
        'period_id' => $item['period_id'],
        'quantity' => $item['value']
      ];
    }

    return $rental_quantities;
  }

  public function save() {
    $cache = \Drupal::cache('commerce_rental_period_manager');
    $cache->set($this->productVariation->id(), $this, CacheBackendInterface::CACHE_PERMANENT);
  }

}