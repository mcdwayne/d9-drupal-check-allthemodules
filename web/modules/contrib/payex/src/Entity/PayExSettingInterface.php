<?php

namespace Drupal\payex\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the PayExSettingInterface used by PayExSetting entity class.
 */
interface PayExSettingInterface extends ConfigEntityInterface  {

  /**
   * Gets the default currency code.
   *
   * @return string
   */
  public function getDefaultCurrencyCode();

  /**
   * Gets the default currency VAT.
   *
   * @return integer
   */
  public function getDefaultVat();

  /**
   * Gets the encruption key.
   *
   * @return string
   */
  public function getEncryptionKey();

  /**
   * Gets the live status
   *
   * @return boolean
   */
  public function getLive();

  /**
   * Gets the merchant account number.
   *
   * @return string
   */
  public function getMerchantAccount();

  /**
   * Gets the Purchase operation
   *
   * Currently two options are available:
   *  - SALE: Instant capture
   *  - AUTHORIZATION: Authorization only
   *
   * @return string
   */
  public function getPurchaseOperation();

  /**
   * Gets the PayEx Payment Gateway (PPG)
   *
   * Currently two versions exist, "1.0" and "2.0"
   *
   * @return string
   */
  public function getPPG();

  /**
   * Returns boolean status for if live payex integration should be used.
   *
   * @return boolean
   */
  public function isLive();

  /**
   * Returns boolean status for if test payex integration should be used.
   *
   * @return boolean
   */
  public function isTest();

  /**
   * Sets the encryption key
   *
   * @param $encryptionKey
   *   The encryption key to set
   *
   * @return $this
   */
  public function setEncryptionKey($encryptionKey);

}
