<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_nordea\DependencyInjection;


use Braintree\Exception;
use Drupal\Core\Config\ConfigValueException;

class KeysHelper
{
  const STORAGE_KEY_LIVE_PUBLIC = 'live_public';
  const STORAGE_KEY_LIVE_PRIVATE = 'live_private';
  const STORAGE_KEY_TEST_PUBLIC = 'test_public';
  const STORAGE_KEY_TEST_PRIVATE = 'test_private';

  const STORAGE_KEY_CONFIG_NAME = 'commerce_payment.commerce_nordea.{id}.keys';

  const STORAGE_KEY_DEFAULT_PRIVATE = 'demo-merchant-private.pem';
  const STORAGE_KEY_DEFAULT_PUBLIC = 'demo-merchant-public.pem';

  const STORAGE_KEY_GATEWAY_LIVE = 'nordea-e-commerce-live-public-key.pem';
  const STORAGE_KEY_GATEWAY_TEST = 'pay-page-public.pem';

  /** Database */
  public function getLivePrivateKey($id)
  {
    return $this->getKey($id, self::STORAGE_KEY_LIVE_PRIVATE);
  }

  public function getLivePublicKey($id)
  {
    return $this->getKey($id, self::STORAGE_KEY_LIVE_PUBLIC);
  }

  public function getTestPrivateKey($id)
  {
    return $this->getKey($id, self::STORAGE_KEY_TEST_PRIVATE);
  }

  public function getTestPublicKey($id)
  {
    return $this->getKey($id, self::STORAGE_KEY_TEST_PUBLIC);
  }

  protected function getKey($id, $type)
  {
    $config_factory = \Drupal::configFactory();
    return $config_factory->get($this->getConfigName($id))->get($type);
  }

  public function storeKeys($id, $type, $publicKey, $privateKey)
  {

    $config_factory = \Drupal::configFactory();

    try {
      if ($type === 'live') {
        $config_factory->getEditable($this->getConfigName($id))->set(self::STORAGE_KEY_LIVE_PRIVATE, $privateKey)->save();
        $config_factory->getEditable($this->getConfigName($id))->set(self::STORAGE_KEY_LIVE_PUBLIC, $publicKey)->save();
      } else {
        $config_factory->getEditable($this->getConfigName($id))->set(self::STORAGE_KEY_TEST_PRIVATE, $privateKey)->save();
        $config_factory->getEditable($this->getConfigName($id))->set(self::STORAGE_KEY_TEST_PUBLIC, $publicKey)->save();
      }
    } catch (ConfigValueException $e) {
      return false;
    } catch (Exception $e) {
      return false;
    }

    return true;
  }

  public function getConfigName($id)
  {
    return str_replace('{id}', $id, self::STORAGE_KEY_CONFIG_NAME);
  }

}