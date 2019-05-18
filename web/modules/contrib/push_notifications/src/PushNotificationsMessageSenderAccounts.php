<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsMessageSenderAccounts.
 */

namespace Drupal\push_notifications;

/**
 * Send a simple message alert to a list of tokens..
 */
class PushNotificationsMessageSenderAccounts extends PushNotificationsMessageSenderBase{

  /**
   * @var array $accounts
   *   Recipient account objects.
   */
  protected $accounts;

  /**
   * Constructor.
   *
   * @param \Drupal\push_notifications\PushNotificationsDispatcher $dispatcher
   *   Alert Dispatcher.
   */
  public function __construct(PushNotificationsDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Setter function for accounts.
   *
   * @param array $accounts
   *   Account objects.
   */
  public function setAccounts($accounts) {
    $this->accounts = $accounts;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $accounts
   *   Account objects.
   */
  public function generateTokens() {
    foreach ($this->accounts as $account) {
      $user_tokens = push_notification_get_user_tokens($account->id());
      if (!empty($user_tokens)) {
        $this->tokens = array_merge($this->tokens, $user_tokens);
      }
    }
  }

}