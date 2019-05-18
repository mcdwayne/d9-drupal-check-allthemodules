<?php

namespace Drupal\raven\Commands;

use Drupal\raven\Logger\Raven;
use Drush\Commands\DrushCommands;
use Exception;

/**
 * Provides Drush commands for Raven module.
 */
class RavenCommands extends DrushCommands {

  /**
   * The @logger.raven service.
   *
   * @var Drupal\raven\Logger\Raven|null
   */
  protected $ravenLogger;

  /**
   * Injects Raven logger service.
   */
  public function setRavenLogger(Raven $raven_logger) {
    $this->ravenLogger = $raven_logger;
  }

  /**
   * Sends a test message to Sentry.
   *
   * Because messages are sent to Sentry asynchronously, there is no guarantee
   * that the message was actually delivered successfully.
   *
   * @param string $message
   *   The message text.
   * @param array $options
   *   An associative array of options.
   *
   * @option level
   *   The message level (debug, info, warning, error, fatal).
   * @option logger
   *   The logger.
   *
   * @command raven:captureMessage
   */
  public function captureMessage($message = 'Test message from Drush.', array $options = ['level' => 'info', 'logger' => 'drush']) {
    if (!$this->ravenLogger) {
      throw new Exception('Raven logger service not available.');
    }
    if (!$this->ravenLogger->client) {
      throw new Exception('Raven client not available.');
    }
    $id = $this->ravenLogger->client->captureMessage($message, [], ['level' => $options['level'], 'logger' => $options['logger']]);
    if (!$id) {
      throw new Exception('Send failed.');
    }
    $this->logger()->success(dt('Message sent as event %id.', ['%id' => $id]));
  }

}
