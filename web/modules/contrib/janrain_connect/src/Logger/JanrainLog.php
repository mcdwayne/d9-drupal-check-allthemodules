<?php

namespace Drupal\janrain_connect\Logger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * JanrainLog.
 */
class JanrainLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a JanrainLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Factory.
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->logger = $logger_factory->get('janrain_connect');
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (!empty($this->config->get('enable_janrain_rest_log'))) {
      $this->logger->log($level, $message, $context);
    }
  }

}
