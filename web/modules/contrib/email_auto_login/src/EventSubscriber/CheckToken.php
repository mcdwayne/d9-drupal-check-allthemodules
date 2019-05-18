<?php

/**
 * @file
 * Contains \Drupal\email_auto_login\EventSubscriber\CheckToken.
 */

namespace Drupal\email_auto_login\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Email Auto Login subscriber for controller requests.
 */
class CheckToken implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

  public function checkForRedirection(GetResponseEvent $event) {
    if (isset($_GET['l']) && user_is_anonymous()) {

      // In case cron is broken, let's make sure we've invalidated old tokens.
      _email_auto_login_invalidate();

      $token = $_GET['l'];

      $uid = db_select('email_auto_login_tokens', 'e')
        ->fields('e', array('uid'))
        ->condition('e.token', $token)
        ->execute()
        ->fetchField();

      $account = user_load($uid);

      db_delete('email_auto_login_tokens')
        ->condition('token', $token)
        ->execute();

      // Don't allow admin or blocked users to login using this method.
      if ($account->id() != 1 && $account->isAuthenticated()) {
        user_login_finalize($account);

        // Reload the current page after login to avoid access denied pageю
        $event->setResponse(new RedirectResponse(current_path()));
      }
    }
  }
}
