<?php

namespace Drupal\streamy;

use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class StreamyCDNBase extends PluginBase implements StreamyCDNInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current plugin configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @inheritdoc
   */
  public function setUp() {
    if (isset($this->pluginDefinition['configPrefix'])) {
      $this->config = $this->configFactory->get($this->pluginDefinition['configPrefix'] . '.' . $this->pluginId);
    }
  }

  /**
   * @param $configFactory
   */
  public function setConfigFactory($configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * @param $request
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

}
