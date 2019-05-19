<?php

namespace Drupal\uvrp\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;

/**
 * Configure uvrp Subscriber for this site.
 */
class RVPSubscriber implements EventSubscriberInterface {

  /**
   * Redirect pattern based url.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function rvp(GetResponseEvent $event) {
    $session = $this->uvrpSession();
    if (!$session) {
      $session_id    = $this->drupalHashBase64(uniqid(mt_rand(), TRUE));
      $cookie_domain = ini_get('session.cookie_domain');
      setcookie('uvrp', $session_id, REQUEST_TIME + 2592000, '/', $cookie_domain, 1, 1);
    }

    $node = uvrp_get_current_node();
    if ($node instanceof NodeInterface) {
      if ($node->getType() == 'product') {
        db_merge('uvrp')->key([
          'nid' => $node->id(),
          'sid' => isset($_COOKIE['uvrp']) ? $_COOKIE['uvrp'] : session_id(),
          'uid' => uvrp_current_user_id(),
        ])->fields([
          'ip' => uvrp_client_ip(),
          'created' => REQUEST_TIME,
        ])->execute();
      }
    }
  }

  /**
   * Hash the data value.
   */
  private function drupalHashBase64($data) {
    $hash = base64_encode(hash('sha256', $data, TRUE));
    // Modify the hash so it's safe to use in URLs.
    return strtr($hash, ['+' => '-', '/' => '_', '=' => '']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['rvp'];
    return $events;
  }

  /**
   * Check if cookie variable is set.
   */
  private function uvrpSession() {
    if (isset($_COOKIE['uvrp']) && !empty($_COOKIE['uvrp'])) {
      return TRUE;
    }
    else {
      $_COOKIE['uvrp'] = '';
      return FALSE;
    }
  }

}
