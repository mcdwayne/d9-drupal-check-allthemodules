<?php

namespace Drupal\xero;

use Radcliffe\Xero\XeroClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Xero client factory class.
 */
class XeroClientFactory {

  /**
   * Return a configured XeroClient object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   *
   * @return \Radcliffe\Xero\XeroClient|FALSE
   */
  public function get(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $config = $config_factory->get('xero.settings');
    $xero_config = [
      'base_uri' => 'https://api.xero.com/api.xro/2.0/',
      'token' => $config->get('oauth.consumer_key'),
      'token_secret' => $config->get('oauth.consumer_secret'),
      'consumer_key' => $config->get('oauth.consumer_key'),
      'consumer_secret' => $config->get('oauth.consumer_secret'),
      'application' => 'private',
      'private_key' => $config->get('oauth.key_path')
    ];

    try {
      $client = new XeroClient($xero_config);

      return $client;
    }
    catch (\Exception $e) {
      $logger = $logger_factory->get('xero');
      $logger->error($e->getMessage());
      return FALSE;
    }
  }
}
