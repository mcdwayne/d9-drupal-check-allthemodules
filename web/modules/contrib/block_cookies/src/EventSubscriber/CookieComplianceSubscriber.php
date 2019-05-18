<?php

namespace Drupal\block_cookies\EventSubscriber;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CookieComplianceSubscriber.
 *
 * @package Drupal\block_cookies\EventSubscriber
 */
class CookieComplianceSubscriber implements EventSubscriberInterface {

  /**
   * Clear all cookies created by php if user opt-out.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  public function clearCookies(Event $event) {
    if (\Drupal::currentUser()->id() !== 0 && \Drupal::config('block_cookies.settings')->get('force_allow_cookies_auth_users') === 0) {
      return;
    }
    if (isset($_COOKIE['cookie-agreed'])) {
      $cookie_agreed = $_COOKIE['cookie-agreed'];
      if ($cookie_agreed !== '0') {
        return;
      }
      else {
        if (isset($_SERVER['HTTP_COOKIE'])) {
          $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
          $host = \Drupal::request()->getHost();
          $params = session_get_cookie_params();
          foreach ($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            $session_cookie = (substr($name, 0, 4) == 'SESS' && strlen($name) == 36);
            if ($name && $name != 'cookie-agreed' && !$session_cookie) {
              unset($_COOKIE[$name]);
              setcookie($name, '', time() - 42000);
              setcookie($name, '', time() - 42000, '/');
              setcookie($name, '', time() - 42000, '/', $host);

              setcookie($name, '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
              );
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['clearCookies'];
    return $events;
  }

}
