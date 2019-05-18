<?php

namespace Drupal\login_notification;

use Drupal\Core\Session\AccountInterface;

/**
 * Define login notification manager interface.
 */
interface LoginNotificationManagerInterface {

  /**
   * Invoke login notifications.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function invokeLoginNotifications(AccountInterface $user);
}
