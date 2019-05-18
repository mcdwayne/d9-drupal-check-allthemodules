<?php

namespace Drupal\commerce_product_reservation;

/**
 * Class StockResult.
 */
class StockResult {

  /**
   * SKU.
   *
   * @var string
   */
  private $sku;

  /**
   * Stock.
   *
   * @var int
   */
  private $stock;

  /**
   * Store id.
   *
   * @var string
   */
  private $storeId;

  /**
   * Getter.
   */
  public function getSku() {
    return $this->sku;
  }

  /**
   * Setter.
   */
  public function setSku($sku) {
    $this->sku = $sku;
  }

  /**
   * Getter.
   */
  public function getStock() {
    return $this->stock;
  }

  /**
   * Setter.
   */
  public function setStock($stock) {
    $this->stock = $stock;
  }

  /**
   * Getter.
   */
  public function getStoreId() {
    return $this->storeId;
  }

  /**
   * Setter.
   */
  public function setStoreId($storeId) {
    $this->storeId = $storeId;
  }

  /**
   * Helper.
   */
  public static function createFromValues(array $values) {
    $fields = [
      'sku',
      'stock',
      'storeId',
    ];
    $self = new static();
    foreach ($fields as $field) {
      if (!isset($values[$field])) {
        continue;
      }
      $self->{$field} = $values[$field];
    }
    return $self;
  }

}
