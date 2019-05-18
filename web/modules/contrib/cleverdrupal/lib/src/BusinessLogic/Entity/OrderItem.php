<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 *
 */
class OrderItem {
  /**
   * @var string
   */
  private $orderId;

  /**
   * @var string
   */
  private $productId;

  /**
   * @var string
   */
  private $product = '';

  /**
   * @var \DateTime
   */
  private $stamp = NULL;

  /**
   * @var float
   */
  private $price = 0;

  /**
   * @var string
   */
  private $currency = '';

  /**
   * @var int
   */
  private $amount = 1;

  /**
   * @var string
   */
  private $productSource = '';

  /**
   * @var string
   */
  private $brand = '';

  /**
   * @var array
   */
  private $productCategory = [];

  /**
   * @var array
   */
  private $attributes = [];

  /**
   * @var string
   */
  private $mailingId = NULL;

  /**
   * @var  string*/
  private $recipientEmail;

  /**
   * Order constructor.
   *
   * @param string $orderId
   * @param string $product
   */
  public function __construct($orderId, $product) {
    $this->orderId = $orderId;
    $this->product = $product;
  }

  /**
   * @return string
   */
  public function getOrderId() {
    return $this->orderId;
  }

  /**
   * @return int
   */
  public function getProductId() {
    return $this->productId;
  }

  /**
   * @param string $productId
   */
  public function setProductId($productId) {
    $this->productId = $productId;
  }

  /**
   * @return string
   */
  public function getProduct() {
    return $this->product;
  }

  /**
   * @return \DateTime
   */
  public function getStamp() {
    return $this->stamp;
  }

  /**
   * @param \DateTime $stamp
   */
  public function setStamp(\DateTime $stamp = NULL) {
    $this->stamp = $stamp;
  }

  /**
   * @return float
   */
  public function getPrice() {
    return $this->price;
  }

  /**
   * @param float $price
   */
  public function setPrice($price) {
    $this->price = $price;
  }

  /**
   * @return string
   */
  public function getCurrency() {
    return $this->currency;
  }

  /**
   * @param string $currency
   */
  public function setCurrency($currency) {
    $this->currency = $currency;
  }

  /**
   * @return int
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * @param int $amount
   */
  public function setAmount($amount) {
    $this->amount = $amount;
  }

  /**
   * @return string
   */
  public function getProductSource() {
    return $this->productSource;
  }

  /**
   * @param string $productSource
   */
  public function setProductSource($productSource) {
    $this->productSource = $productSource;
  }

  /**
   * @return string
   */
  public function getBrand() {
    return $this->brand;
  }

  /**
   * @param string $brand
   */
  public function setBrand($brand) {
    $this->brand = $brand;
  }

  /**
   * @return array
   */
  public function getProductCategory() {
    return $this->productCategory;
  }

  /**
   * @param array $productCategory
   */
  public function setProductCategory(array $productCategory) {
    $this->productCategory = $productCategory;
  }

  /**
   * @return array
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * @param array $attributes
   */
  public function setAttributes(array $attributes) {
    $this->attributes = $attributes;
  }

  /**
   * @return string
   */
  public function getMailingId() {
    return $this->mailingId;
  }

  /**
   * @param string $mailingsId
   */
  public function setMailingId($mailingsId) {
    $this->mailingId = $mailingsId;
  }

  /**
   * @return string
   */
  public function getRecipientEmail() {
    return $this->recipientEmail;
  }

  /**
   * @param string $recipientEmail
   */
  public function setRecipientEmail($recipientEmail) {
    $this->recipientEmail = $recipientEmail;
  }

}
