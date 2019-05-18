<?php

namespace Drupal\janrain_connect\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * JanrainConnect Validate Class.
 */
class JanrainConnectValidate {

  use StringTranslationTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * JanrainConnectValidate constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('janrain_connect');
  }

  /**
   * Validate response.
   *
   * @param array $response
   *   Response.
   */
  public function validateResponse(array $response) {
    // We log it if there are errors.
    if ($response['has_errors']) {
      // Info response on watchdog.
      $this->logger->info(json_encode($response));

      return FALSE;
    }
  }

}
