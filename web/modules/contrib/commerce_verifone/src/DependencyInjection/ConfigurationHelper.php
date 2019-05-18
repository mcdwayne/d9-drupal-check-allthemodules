<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_verifone\DependencyInjection;


class ConfigurationHelper
{

  const GATEWAY_MODE_TEST = 'test';
  const GATEWAY_MODE_LIVE = 'live';

  protected $_keysHelper;

  protected $_gatewayId;
  protected $_configuration;
  protected $_defaultConfiguration;

  public function __construct($gatewayId, $configuration, $defaultConfiguration)
  {

    $this->_gatewayId = $gatewayId;
    $this->_configuration = $configuration;
    $this->_defaultConfiguration = $defaultConfiguration;

    $this->_keysHelper = new KeysHelper();
  }

  public function getData($key)
  {
    if (!empty($this->_configuration[$key]) || $this->_configuration[$key] == 0) {
      return $this->_configuration[$key];
    }

    if(isset($this->_defaultConfiguration[$key]) && !empty($this->_defaultConfiguration[$key])) {
      return $this->_defaultConfiguration[$key];
    }

    return null;
  }

  /** FILES */
  public function getMerchantAgreement()
  {
    if ($this->isLiveMode()) {
      return $this->_configuration['merchant_agreement_code'];
    }

    if (isset($this->_configuration['merchant_agreement_code_test']) && !empty($this->_configuration['merchant_agreement_code_test'])) {
      return $this->_configuration['merchant_agreement_code_test'];
    }

    return $this->getMerchantAgreementDefault();
  }

  public function getMerchantAgreementDefault()
  {
    return $this->_defaultConfiguration['merchant_agreement_code_test'];
  }

  public function isLiveMode()
  {
    return $this->getData('mode') === self::GATEWAY_MODE_LIVE;
  }

  public function getKeyMode()
  {
    return $this->getData('key_handling_mode');
  }

  public function isKeySimpleMode()
  {
    return (int)$this->getKeyMode() === 0;
  }

  public function isKeyAdvancedMode()
  {
    return (int)$this->getKeyMode() === 1;
  }

  public function getKeysDirectory()
  {
    return $this->getData('keys_directory');
  }

  public function getShopPrivateKeyFileName()
  {
    if($this->isLiveMode()) {
      return $this->getData('shop_private_keyfile');
    }

    return $this->getData('shop_private_keyfile_test');
  }

  public function getLiveShopPrivateKeyPath()
  {

    if ($this->isKeySimpleMode()) {
      return null;
    }

    return $this->getKeysDirectory() . DIRECTORY_SEPARATOR . $this->getData('shop_private_keyfile');

  }

  public function getTestShopPrivateKeyPath()
  {

    if ($this->isKeySimpleMode()) {
      return null;
    }

    return $this->getKeysDirectory() . DIRECTORY_SEPARATOR . $this->getData('shop_private_keyfile_test');
  }

  public function getShopPrivateKeyPath()
  {
    if($this->isLiveMode()) {
      return $this->getLiveShopPrivateKeyPath();
    }

    return $this->getTestShopPrivateKeyPath();
  }

  public function getShopPrivateKeyFile()
  {
    return $this->getShopPrivateKey();
  }

  public function getShopPrivateKey()
  {

    // If TEST mode is set
    if (!$this->isLiveMode()) {

      if ($this->getMerchantAgreement() === $this->getMerchantAgreementDefault()) {
        // If DEFAULT test merchant is set, return default key
        return $this->getShopPrivateKeyDefault();
      }

      if ($this->isKeySimpleMode()) {
        // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
        return $this->_keysHelper->getTestPrivateKey($this->_gatewayId);
      }

      $path = $this->getTestShopPrivateKeyPath();
      if (file_exists($path)) {
        // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
        return file_get_contents($path);
      }

      // return default key file
      return $this->getShopPrivateKeyDefault();
    }

    // If LIVE mode is set

    if ($this->isKeySimpleMode()) {
      // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
      return $this->_keysHelper->getLivePrivateKey($this->_gatewayId);
    }

    $path = $this->getLiveShopPrivateKeyPath();
    if (file_exists($path)) {
      // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
      return file_get_contents($path);
    }

    // return nothing
    return null;
  }

  public function getShopPrivateKeyPathDefault()
  {
    return $this->getModuleKeysDirectory() . KeysHelper::STORAGE_KEY_DEFAULT_PRIVATE;
  }

  public function getShopPrivateKeyDefault()
  {
    return file_get_contents($this->getShopPrivateKeyPathDefault());
  }

  public function getShopPublicKey()
  {
    // If TEST mode is set
    if (!$this->isLiveMode()) {

      if ($this->getMerchantAgreement() === $this->getMerchantAgreementDefault()) {
        // If DEFAULT test merchant is set, return default key
        // For default simple key is not require to configure in payment service.
        return null;
      }

      if ($this->isKeySimpleMode()) {
        // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
        return $this->_keysHelper->getTestPublicKey($this->_gatewayId);
      }


      // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
      // This is not required, because if this mode is set, it means that payment service is configured,
      // and it does not require to configure again.
      return null;
    }

    // If LIVE mode is set
    if ($this->isKeySimpleMode()) {
      // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
      return $this->_keysHelper->getLivePublicKey($this->_gatewayId);
    }

    // When advanced mode is set, or is default merchant agreement then in not require simple key to display.
    return null;
  }

  public function getShopPublicKeyPathDefault()
  {
    return $this->getModuleKeysDirectory() . KeysHelper::STORAGE_KEY_DEFAULT_PUBLIC;
  }

  public function getShopPublicKeyDefault()
  {
    return file_get_contents($this->getShopPublicKeyPathDefault());
  }

  public function getPaymentPublicKeyPath()
  {
    if($this->isLiveMode()) {
      return $this->getModuleKeysDirectory() . KeysHelper::STORAGE_KEY_GATEWAY_LIVE;
    }

    return $this->getModuleKeysDirectory() . KeysHelper::STORAGE_KEY_GATEWAY_TEST;
  }

  public function getPaymentPublicKeyFile()
  {
    return file_get_contents($this->getPaymentPublicKeyPath());
  }

  public function getModuleKeysDirectory()
  {
    return   \Drupal::service('module_handler')->getModule('commerce_verifone')->getPath() . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR;
  }

  /**
   * @return KeysHelper
   */
  public function getKeysHelper()
  {
    return $this->_keysHelper;
  }
}