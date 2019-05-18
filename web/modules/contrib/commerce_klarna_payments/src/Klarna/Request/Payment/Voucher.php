<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\VoucherInterface;
use Drupal\commerce_klarna_payments\Klarna\DateTrait;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;

/**
 * Value object for vouchers.
 */
class Voucher implements VoucherInterface {

  use DateTrait;
  use ObjectNormalizer {
    toArray as parentToArray;
  }

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setName(string $name) : VoucherInterface {
    $this->data['voucher_name'] = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompany(string $company) : VoucherInterface {
    $this->data['voucher_company'] = $company;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime(\DateTime $dateTime) : VoucherInterface {
    $this->data['start_time'] = $dateTime;

    return $this;
  }

  /**
   * Gets the datetime.
   *
   * @return \DateTime|null
   *   The datetime or null.
   */
  public function getStartTime() : ? \DateTime {
    return $this->data['start_time'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime(\DateTime $dateTime) : VoucherInterface {
    $this->data['end_time'] = $dateTime;

    return $this;
  }

  /**
   * Gets the end time.
   *
   * @return \DateTime|null
   *   The datetime or null.
   */
  public function getEndTime() : ? \DateTime {
    return $this->data['end_time'];
  }

  /**
   * {@inheritdoc}
   */
  public function setAffiliateName(string $name) : VoucherInterface {
    $this->data['affiliate_name'] = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    // Run ObjectNormalizer::toArray() first.
    $data = $this->parentToArray();

    foreach ($data as $key => $value) {
      if ($value instanceof \DateTime) {
        $data[$key] = $this->format($value);
      }
    }
    return $data;
  }

}
