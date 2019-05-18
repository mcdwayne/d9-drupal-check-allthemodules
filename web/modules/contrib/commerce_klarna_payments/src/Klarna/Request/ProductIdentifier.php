<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\ProductIdentifierInterface;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;

/**
 * Value object for order item product identifiers.
 */
class ProductIdentifier implements ProductIdentifierInterface {

  use ObjectNormalizer;

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setCategoryPath(string $path) : ProductIdentifierInterface {
    $this->data['category_path'] = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGlobalTradeItemNumber(string $number) : ProductIdentifierInterface {
    $this->data['global_trade_item_number'] = $number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setManufacturerPartNumber(string $number) : ProductIdentifierInterface {
    $this->data['manufacturer_part_number'] = $number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBrandName(string $name) : ProductIdentifierInterface {
    $this->data['brand'] = $name;
    return $this;
  }

}
