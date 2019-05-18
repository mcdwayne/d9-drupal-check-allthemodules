<?php

namespace Drupal\mailing_list;

use Drupal\mailing_list\SubscriptionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Mailing list manager implementation.
 */
class MailingListManager implements MailingListManagerInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The user private temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $userPrivateTempstore;

  /**
   * Create a new mailing list manager instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Drupal\user\PrivateTempStoreFactory $user_private_tempstore
   *   The user temp store.
   */
  public function __construct(AccountInterface $current_user, SessionManagerInterface $session_manager, PrivateTempStoreFactory $user_private_tempstore) {
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->userPrivateTempstore = $user_private_tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function grantSessionAccess(SubscriptionInterface $subscription) {
    // We need user session even for anonymous users.
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->sessionManager->start();
    }

    if (!$this->sessionManager->isStarted()) {
      // Unable to start the session, may be called from CLI or no cookies
      // allowed.
      return;
    }

    // Add subscription to the session access permissions.
    $collection = $this->userPrivateTempstore->get('mailing_list');
    if (!$grants = $collection->get('grants')) {
      $grants = [];
    }
    $grants[$subscription->uuid()] = REQUEST_TIME;
    $collection->set('grants', $grants);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeSessionAccess(SubscriptionInterface $subscription) {
    if ($this->hasSessionAccess($subscription)) {
      $collection = $this->userPrivateTempstore->get('mailing_list');
      $grants = $collection->get('grants');
      unset($grants[$subscription->uuid()]);
      $collection->set('grants', $grants);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSessionAccess(SubscriptionInterface $subscription) {
    return $this->sessionManager->isStarted()
      && ($grants = $this->userPrivateTempstore->get('mailing_list')->get('grants'))
      && isset($grants[$subscription->uuid()]);
  }

}
