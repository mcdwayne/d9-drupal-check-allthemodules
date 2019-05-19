<?php

namespace Drupal\zuora;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\zuora\Exception\ZuoraException;

abstract class ZuoraClientBase {

  protected $zuoraConfig;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->zuoraConfig = $config_factory->get('zuora.settings');

    if (!$this->zuoraConfig->get('access_key_id') || !$this->zuoraConfig->get('access_secret_key')) {
      throw new ZuoraException('You must set your Zuora credentials');
    }
  }

  /**
   * Returns if the API is running in sandbox mode.
   *
   * @return boolean
   */
  public function isSandboxed() {
    return $this->zuoraConfig->get('sandbox');
  }
}
