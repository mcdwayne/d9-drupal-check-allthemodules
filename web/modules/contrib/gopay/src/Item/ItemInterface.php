<?php

namespace Drupal\gopay\Item;

/**
 * Interface ItemInterface.
 *
 * @package Drupal\gopay\Item
 */
interface ItemInterface {

  /**
   * Sets type.
   *
   * Use one of constants in \GoPay\Definition\Payment\PaymentItemType.
   *
   * @param string $type
   *   Type.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setType($type);

  /**
   * Sets name.
   *
   * @param string $name
   *   Name.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setName($name);

  /**
   * Sets product URL.
   *
   * @param string $url
   *   URL.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setProductUrl($url);

  /**
   * Sets EAN.
   *
   * @param string $ean
   *   EAN.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setEan($ean);

  /**
   * Sets total price of items.
   *
   * @param int $amount
   *   Amount of payment.
   * @param bool $in_cents
   *   Whether $amount is in cents or in units. GoPay API gets amount in cents,
   *   so if you enter amount in units, it will be converted to cents anyway.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setAmount($amount, $in_cents = TRUE);

  /**
   * Sets count of items.
   *
   * @param int $count
   *   Count of items.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setCount($count);

  /**
   * Sets VAT rate.
   *
   * Use one of GoPay\Definition\Payment\VatRate constants.
   *
   * @param int $vat_rate
   *   VAT rate.
   *
   * @return \Drupal\gopay\Item\ItemInterface
   *   Returns itself.
   */
  public function setVatRate($vat_rate);

  /**
   * Creates item configuration compatible with GoPay SDK Payments.
   *
   * @see https://doc.gopay.com/en/#items
   *
   * @return array
   *   Configuration of this item
   *
   * @throws \Drupal\gopay\Exception\GoPayInvalidSettingsException
   */
  public function toArray();

}
