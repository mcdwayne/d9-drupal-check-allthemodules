<?php

namespace Drupal\tag1quo\Adapter\Logger;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\tag1quo\Adapter\Core\Core;

/**
 * Class Logger8.
 *
 * @internal This class is subject to change.
 */
class Logger8 extends Logger {

  /**
   * The logger service, if it exists.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function isDatabaseConnection(\Exception $exception) {
    return $exception instanceof \PDOException || $exception instanceof DatabaseExceptionWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Core $core, $channel = self::CHANNEL) {
    parent::__construct($core, $channel);
    $this->logger = \Drupal::logger($this->channel);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Proxy to the logger service.
    $this->logger->log($level, $message, $context);
  }

}
