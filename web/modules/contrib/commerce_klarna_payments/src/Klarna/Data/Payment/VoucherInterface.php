<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe vouchers.
 */
interface VoucherInterface extends ObjectInterface {

  /**
   * Description of the product the voucher entitles to, e.g. "China trip".
   *
   * @param string $name
   *   The name.
   *
   * @return $this
   *   The self.
   */
  public function setName(string $name) : VoucherInterface;

  /**
   * Sets the company.
   *
   * Name of the company that will provide good / service upon voucher
   * (not the same as the selling merchant), e.g. "Sun Trips Ltd."
   *
   * @param string $company
   *   The company.
   *
   * @return $this
   *   The self.
   */
  public function setCompany(string $company) : VoucherInterface;

  /**
   * Sets the start time.
   *
   * @param \DateTime $dateTime
   *   The start time.
   *
   * @return $this
   *   The self.
   */
  public function setStartTime(\DateTime $dateTime) : VoucherInterface;

  /**
   * Sets the end time.
   *
   * @param \DateTime $dateTime
   *   The end time.
   *
   * @return $this
   *   The self.
   */
  public function setEndTime(\DateTime $dateTime) : VoucherInterface;

  /**
   * Name of the affiliate that originated the purchase. If none, leave blank.
   *
   * @param string $name
   *   The affiliate name.
   *
   * @return $this
   *   The self.
   */
  public function setAffiliateName(string $name) : VoucherInterface;

}
