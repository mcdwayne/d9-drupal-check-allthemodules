<?php

namespace Drupal\janrain_connect_super_admin\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class: JanrainConnectSuperAdminServices.
 */
class JanrainConnectSuperAdminServices {

  use StringTranslationTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->config = $config_factory->get('janrain_connect_super_admin.settings');
    $this->logger = $logger_factory->get('janrain_connect');
  }

  /**
   * Get Config Direct Access.
   */
  public function getConfigDirectAccess() {

    $return_default = [
      'direct_access_id' => $this->config->get('auth_id'),
      'direct_access_secret' => $this->config->get('auth_secret'),
    ];

    $use_encrypt = $this->config->get('encrypt_option');

    if (empty($use_encrypt)) {
      return $return_default;
    }

    $ket_id = $this->config->get('key_id');

    if (empty($ket_id)) {
      return $return_default;
    }

    // We can not use dependency injection because key is optional.
    // @codingStandardsIgnoreLine
    $data = \Drupal::service('key.repository')->getKey($ket_id)->getKeyValues();

    if (empty($data['direct_access_id'] || empty($data['direct_access_secret']))) {
      return $return_default;
    }

    return $data;

  }

}
