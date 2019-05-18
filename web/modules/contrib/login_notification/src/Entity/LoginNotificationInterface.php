<?php

namespace Drupal\login_notification\Entity;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Define login notification interface.
 */
interface LoginNotificationInterface {

  /**
   * Condition met all requirements.
   *
   * @return bool
   */
  public function conditionsMetAll();

  /**
   * Get message notification.
   *
   * @param array $data
   *   An array of token data.
   *
   * @return string
   */
  public function getMessage($data = []);

  /**
   * Get active notification conditions.
   *
   * @return array
   */
  public function getActiveConditions();

  /**
   * Render login message.
   *
   * @param array $data
   *   The token data.
   * @param string $type
   *   The notification message type.
   */
  public function render($data = [], $type = MessengerInterface::TYPE_STATUS);
}
