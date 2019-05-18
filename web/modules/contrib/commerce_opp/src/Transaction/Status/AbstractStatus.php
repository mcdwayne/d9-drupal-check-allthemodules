<?php

namespace Drupal\commerce_opp\Transaction\Status;

use Drupal\commerce_opp\Brand;

/**
 * Abstract transaction status base class.
 */
abstract class AbstractStatus implements TransactionStatusInterface {

  /**
   * The transaction ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The result code.
   *
   * @var string
   */
  protected $code;

  /**
   * The status description.
   *
   * @var string
   */
  protected $description;

  /**
   * The payment brand.
   *
   * @var \Drupal\commerce_opp\Brand|null
   */
  protected $brand;

  /**
   * Constructs a new AbstractStatus object.
   *
   * @param string $id
   *   The transaction ID.
   * @param string $code
   *   The result code.
   * @param string $description
   *   The status description.
   * @param \Drupal\commerce_opp\Brand|null $brand
   *   The payment brand. Defaults to NULL.
   */
  public function __construct($id, $code, $description, Brand $brand = NULL) {
    $this->id = $id;
    $this->code = $code;
    $this->description = $description;
    $this->brand = $brand;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrand() {
    return $this->brand;
  }

  /**
   * {@inheritdoc}
   */
  public function isAsyncPayment() {
    return $this->brand ? !$this->brand->isSync() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getType();

}
