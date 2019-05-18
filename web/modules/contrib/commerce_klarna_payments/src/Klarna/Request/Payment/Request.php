<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\CustomerInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface;
use Drupal\commerce_klarna_payments\Klarna\RequestBase;
use Webmozart\Assert\Assert;

/**
 * Value object for making requests.
 */
class Request extends RequestBase implements RequestInterface {

  protected $localeMapping = [
    'sv-se' => 'SE',
    'fi-fi' => 'FI',
    'sv-fi' => 'FI',
    'nb-no' => 'NO',
    'de-de' => 'DE',
    'de-at' => 'AT',
    'en-us' => 'US',
    'en-gb' => 'GB',
  ];

  /**
   * {@inheritdoc}
   */
  public function setDesign(string $design) : RequestInterface {
    $this->data['design'] = $design;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchaseCountry(string $country) : RequestInterface {
    $this->data['purchase_country'] = $country;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchaseCurrency(string $currency) : RequestInterface {
    $this->data['purchase_currency'] = $currency;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocale(string $locale, array $additionalLocales = []) : RequestInterface {
    $locales = array_merge($this->localeMapping, $additionalLocales);

    if (!array_key_exists($locale, $locales)) {
      throw new \InvalidArgumentException('Invalid locale');
    }
    $this->data['locale'] = $locale;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingAddress(AddressInterface $address) : RequestInterface {
    $this->data['billing_address'] = $address;
    return $this;
  }

  /**
   * Gets the billing address.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface|null
   *   The billing address.
   */
  public function getBillingAddress() : ? AddressInterface {
    return $this->data['billing_address'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingAddress(AddressInterface $address) : RequestInterface {
    $this->data['shipping_address'] = $address;
    return $this;
  }

  /**
   * Gets the shipping address.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface|null
   *   The shipping address.
   */
  public function getShippingAddress() : ? AddressInterface {
    return $this->data['shipping_address'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderAmount(int $amount) : RequestInterface {
    $this->data['order_amount'] = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderTaxAmount(int $amount) : RequestInterface {
    $this->data['order_tax_amount'] = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItems(array $orderItems) : RequestInterface {
    Assert::allIsInstanceOf($orderItems, OrderItemInterface::class);

    $this->data['order_lines'] = $orderItems;
    return $this;
  }

  /**
   * Gets the order items.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface[]
   *   The order lines.
   */
  public function getOrderItems() : array {
    return $this->data['order_lines'] ?? [];
  }

  /**
   * Gets order items of given type.
   *
   * @param string $type
   *   The order item type to get.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface[]
   *   The order lines.
   */
  public function getOrderItemsOfType(string $type) : array {
    return array_filter($this->getOrderItems(), function (OrderItemInterface $orderItem) use ($type) {
      return $orderItem->getType() === $type;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderItem(OrderItemInterface $orderItem) : RequestInterface {
    $this->data['order_lines'][] = $orderItem;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(CustomerInterface $customer) : RequestInterface {
    $this->data['customer'] = $customer;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchantUrls(UrlsetInterface $urlset) : RequestInterface {
    $this->data['merchant_urls'] = $urlset;
    return $this;
  }

  /**
   * Gets the merchant urls.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface|null
   *   The merchant urls or NULL.
   */
  public function getMerchantUrls() : ? UrlsetInterface {
    return $this->data['merchant_urls'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchantReference1(string $reference) : RequestInterface {
    $this->data['merchant_reference1'] = $reference;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchantReference2(string $reference) : RequestInterface {
    $this->data['merchant_reference2'] = $reference;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchantData(string $data) : RequestInterface {
    $this->data['merchant_data'] = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(OptionsInterface $options) : RequestInterface {
    $this->data['options'] = $options;
    return $this;
  }

  /**
   * Gets the options.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface|null
   *   The options or NULL.
   */
  public function getOptions() : ? OptionsInterface {
    return $this->data['options'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachment(AttachmentInterface $attachment) : RequestInterface {
    $this->data['attachment'] = $attachment;
    return $this;
  }

  /**
   * Gets the attachment.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentInterface|null
   *   The attachment.
   */
  public function getAttachment() : ? AttachmentInterface {
    return $this->data['attachment'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomPaymentMethodIds(array $ids) : RequestInterface {
    $this->data['custom_payment_method_ids'] = $ids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCustomPaymentMethodId(string $method) : RequestInterface {
    $this->data['custom_payment_method_ids'][] = $method;
    return $this;
  }

}
