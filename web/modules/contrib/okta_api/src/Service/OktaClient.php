<?php

namespace Drupal\okta_api\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Okta\Client;
use Drupal\Core\Config\ConfigFactory;

/**
 * Service class for OktaClient.
 */
class OktaClient {

  public $oktaClient = NULL;

  /**
   * Create the Okta API client.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   An instance of Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $configFactory,
                              LoggerChannelFactory $loggerFactory,
                              ModuleHandlerInterface $module_handler) {
    $this->config = $configFactory->get('okta_api.settings');
    $domain = $this->config->get('okta_domain');
    $this->loggerFactory = $loggerFactory;
    $this->moduleHandler = $module_handler;

    $oktaClientConfig = [
      // Don't auto-bootstrap the Okta resource properties.
      'bootstrap' => FALSE,
      // Use the okta preview (oktapreview.com) domain.
      'preview' => $this->config->get('preview_domain'),
      // 'headers' => [
      // 'Some-Header'    => 'Some value',
      // 'Another-Header' => 'Another value'
      // ].
    ];

    if (isset($domain) && $domain !== '') {
      $oktaClientConfig['domain'] = $domain;
    }

    $this->Client = new Client(
        $this->config->get('organisation_url'),
        $this->config->get('okta_api_key'),
        $oktaClientConfig
      );
  }

  /**
   * Debug OKTA response and exceptions.
   *
   * @param mixed $data
   *   Data to debug.
   * @param string $type
   *   Response or Exception.
   */
  public function debug($data, $type = 'response') {
    if ($this->config->get('debug_' . $type)) {
      if ($this->moduleHandler->moduleExists('devel')) {
        ksm($data);
      }
    }
  }

}
