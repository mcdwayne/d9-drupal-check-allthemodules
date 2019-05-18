<?php

namespace Drupal\gopay\Item;

use GoPay\Definition\Payment\PaymentItemType;
use Drupal\gopay\Exception\GoPayInvalidSettingsException;

/**
 * Class Item.
 *
 * @package Drupal\gopay\Item
 */
class Item implements ItemInterface {

  /**
   * Item type, defaults as ITEM const.
   *
   * @var string
   */
  protected $type;

  /**
   * Name.
   *
   * @var string
   */
  protected $name;

  /**
   * Product URL.
   *
   * @var string
   */
  protected $productUrl;

  /**
   * EAN.
   *
   * @var string
   */
  protected $ean;

  /**
   * Total price of items.
   *
   * @var int
   */
  protected $amount;

  /**
   * Count of items, defaults to 1.
   *
   * @var int
   */
  protected $count;

  /**
   * VAT rate.
   *
   * @var int
   */
  protected $vatRate;

  /**
   * Item constructor.
   */
  public function __construct() {
    $this->type = PaymentItemType::ITEM;
    $this->count = 1;

    // These are mandatory properties without default values.
    $this->amount = NULL;
    $this->name = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductUrl($url) {
    $this->productUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEan($ean) {
    $this->ean = $ean;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($amount, $in_cents = TRUE) {
    if (!$in_cents) {
      $amount *= 100;
    }
    $this->amount = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCount($count) {
    $this->count = $count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVatRate($vat_rate) {
    $this->vatRate = $vat_rate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    // Check for mandatory.
    if (!$this->name) {
      throw new GoPayInvalidSettingsException('You must specify item name');
    }
    if (!$this->amount) {
      throw new GoPayInvalidSettingsException('You must specify item amount');
    }

    return [
      'type' => $this->type,
      'name' => $this->name,
      'product_url' => $this->productUrl,
      'ean' => $this->ean,
      'amount' => $this->amount,
      'count' => $this->count,
      'vat_rate' => $this->vatRate,
    ];
  }

}
