<?php

namespace Drupal\simple_integrations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Integration entity.
 */
interface IntegrationInterface extends ConfigEntityInterface {

  /**
   * Check whether the Integration is active.
   *
   * If an integration is active, it should be possible to establish a
   * connection to the endpoint provided. If it isn't, and a GET request is
   * made, an exception will be thrown.
   *
   * @return bool
   *   Whether the Integration is active.
   */
  public function isActive();

  /**
   * Check whether the Integration is in debug mode.
   *
   * If an Integration is not in debug mode and a developer attempts to use the
   * logDebugMessage method, then an exception will be thrown. This is a
   * failsafe to ensure that unnecessary logging is not performed.
   *
   * @return bool
   *   Whether the integration is in debug mode.
   */
  public function isDebugMode();

  /**
   * In debug mode, print a message to the logs.
   *
   * @param string $message
   *   The message to print to the log.
   * @param string $type
   *   Optional. Type of message to display, per Drupal's logger types.
   *
   * @throws \Drupal\simple_integrations\Exception\DebugModeDisabledException
   * @throws \Drupal\simple_integrations\Exception\EmptyDebugMessageException
   */
  public function logDebugMessage($message, $type);

}
