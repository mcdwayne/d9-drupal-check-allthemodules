<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\CustomerInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface as RequestInterfaceBase;
use Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface;

/**
 * An interface to describe requests.
 */
interface RequestInterface extends ObjectInterface, RequestInterfaceBase {

  /**
   * Sets the design.
   *
   * @param string $design
   *   The design.
   *
   * @return $this
   *   The self.
   */
  public function setDesign(string $design) : RequestInterface;

  /**
   * ISO 3166 alpha-2 purchase country.
   *
   * @param string $country
   *   The country.
   *
   * @return $this
   *   The self.
   */
  public function setPurchaseCountry(string $country) : RequestInterface;

  /**
   * ISO 4217 purchase currency.
   *
   * @param string $currency
   *   The currency.
   *
   * @return $this
   *   The self.
   */
  public function setPurchaseCurrency(string $currency) : RequestInterface;

  /**
   * RFC 1766 customer's locale.
   *
   * @param string $locale
   *   The locale.
   * @param array $additionalLocales
   *   An array of additionally allowed locales.
   *
   * @return $this
   *   The self.
   */
  public function setLocale(string $locale, array $additionalLocales = []) : RequestInterface;

  /**
   * The billing address.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface $address
   *   The address.
   *
   * @return $this
   *   The self.
   */
  public function setBillingAddress(AddressInterface $address) : RequestInterface;

  /**
   * Sets the shipping address.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface $address
   *   The address.
   *
   * @return $this
   *   The self.
   */
  public function setShippingAddress(AddressInterface $address) : RequestInterface;

  /**
   * Sets the tota order amount.
   *
   * Non-negative, minor units. Total amount of the order, including tax
   * and any discounts.
   *
   * @param int $amount
   *   The amount.
   *
   * @return $this
   *   The self.
   */
  public function setOrderAmount(int $amount) : RequestInterface;

  /**
   * Sets the total order tax amount.
   *
   * Non-negative, minor units. The total tax amount of the order.
   *
   * @param int $amount
   *   The amount.
   *
   * @return $this
   *   The self.
   */
  public function setOrderTaxAmount(int $amount) : RequestInterface;

  /**
   * Sets the order items.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface[] $orderItems
   *   The order items.
   *
   * @return $this
   *   The self.
   */
  public function setOrderItems(array $orderItems) : RequestInterface;

  /**
   * Adds an order item.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface $orderItem
   *   The order item.
   *
   * @return $this
   *   The self.
   */
  public function addOrderItem(OrderItemInterface $orderItem) : RequestInterface;

  /**
   * Sets the customer.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\CustomerInterface $customer
   *   The customer.
   *
   * @return $this
   *   The self.
   */
  public function setCustomer(CustomerInterface $customer) : RequestInterface;

  /**
   * Sets the merchant urls.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface $urlset
   *   The URL set.
   *
   * @return $this
   *   The self.
   */
  public function setMerchantUrls(UrlsetInterface $urlset) : RequestInterface;

  /**
   * Sets the first merchant reference.
   *
   * Used for storing merchant's internal order number or other reference.
   * If set, will be shown on the confirmation page as "order number" (max
   * 255 characters).
   *
   * @param string $reference
   *   The reference.
   *
   * @return $this
   *   The self.
   */
  public function setMerchantReference1(string $reference) : RequestInterface;

  /**
   * Sets the second merchant reference.
   *
   * Used for storing merchant's internal order number or other reference (max
   * 255 characters).
   *
   * @param string $reference
   *   The reference.
   *
   * @return $this
   *   The self.
   */
  public function setMerchantReference2(string $reference) : RequestInterface;

  /**
   * Pass through field (max 1024 characters)..
   *
   * @param string $data
   *   The merchant data.
   *
   * @return $this
   *   The self.
   */
  public function setMerchantData(string $data) : RequestInterface;

  /**
   * Sets the options for this purchase.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface $options
   *   The options.
   *
   * @return $this
   *   The self.
   */
  public function setOptions(OptionsInterface $options) : RequestInterface;

  /**
   * Sets the attachments.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentInterface $attachment
   *   The attachments.
   *
   * @return $this
   *   The self.
   */
  public function setAttachment(AttachmentInterface $attachment) : RequestInterface;

  /**
   * Sets the custom payment method ids.
   *
   * Ids for custom payment methods available in a given order. Only applicable
   * in GB/US sessions.
   *
   * @param array $ids
   *   The payment method ids.
   *
   * @return $this
   *   The self.
   */
  public function setCustomPaymentMethodIds(array $ids) : RequestInterface;

  /**
   * Adds a custom payment method.
   *
   * @param string $method
   *   The method.
   *
   * @return $this
   *   The self.
   *
   * @see \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface::setCustomPaymentMethodIds()
   */
  public function addCustomPaymentMethodId(string $method) : RequestInterface;

}
