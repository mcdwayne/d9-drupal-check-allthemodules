<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe shipping information.
 */
interface ShippingInformationInterface extends ObjectInterface {

  /**
   * Sets the shipping company.
   *
   * @param string $company
   *   The company.
   *
   * @return $this
   *   The self.
   */
  public function setShippingCompany(string $company) : ShippingInformationInterface;

  /**
   * Sets the shipping method.
   *
   * @param string $method
   *   The shipping method.
   *
   * @return $this
   *   The self.
   */
  public function setShippingMethod(string $method) : ShippingInformationInterface;

  /**
   * Sets the tracking number.
   *
   * @param string $number
   *   The number.
   *
   * @return $this
   *   The self.
   */
  public function setTrackingNumber(string $number) : ShippingInformationInterface;

  /**
   * Sets the tracking uri.
   *
   * @param string $uri
   *   The uri.
   *
   * @return $this
   *   The self.
   */
  public function setTrackingUri(string $uri) : ShippingInformationInterface;

  /**
   * Sets the return shipping company.
   *
   * @param string $company
   *   The company.
   *
   * @return $this
   *   The self.
   */
  public function setReturnShippingCompany(string $company) : ShippingInformationInterface;

  /**
   * Sets the return tracking number.
   *
   * @param string $number
   *   The number.
   *
   * @return $this
   *   The self.
   */
  public function setReturnTrackingNumber(string $number) : ShippingInformationInterface;

  /**
   * Sets the return tracking uri.
   *
   * @param string $uri
   *   The uri.
   *
   * @return $this
   *   The self.
   */
  public function setReturnTrackingUri(string $uri) : ShippingInformationInterface;

}
