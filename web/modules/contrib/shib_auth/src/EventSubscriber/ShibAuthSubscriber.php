<?php

namespace Drupal\shib_auth\EventSubscriber;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\shib_auth\Login\LoginHandler;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Xss;

/**
 * Class ShibAuthSubscriber.
 *
 * @package Drupal\shib_auth\EventSubscriber
 */
class ShibAuthSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\shib_auth\Login\LoginHandler
   */
  private $lh;

  /**
   * ShibAuthSubscriber constructor.
   *
   * @param \Drupal\shib_auth\Login\LoginHandler $lh
   */
  public function __construct(LoginHandler $lh) {
    $this->lh = $lh;
  }

  /**
   * Show debug messages, if needed.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function checkForShibbolethDebug(GetResponseEvent $event) {

    $config = \Drupal::config('shib_auth.shibbolethsettings');

    if (!$config->get('enable_debug_mode')) {
      // Debugging is off-- bail.
      return;
    }

    $patterns = '';
    if ($path_prefix = $config->get('debug_prefix_path')) {
      $patterns = $path_prefix . "\n" . $path_prefix . '*';
    }

    $current_path = \Drupal::service('path.current')->getPath();
    if ($patterns && !\Drupal::service('path.matcher')->matchPath($current_path, $patterns)) {
      // Path doesn't match-- bail.
      return;
    }

    $tags = ['pre', 'b', 'br'];

    $debug_message = Xss::filter('<b>' . $this->t('Shibboleth debug information') . '</b>', $tags);
    $rendered_message = Markup::create($debug_message);
    drupal_set_message($rendered_message);

    $current_user = \Drupal::currentUser();
    if ($current_user->id()) {
      $user_info = [
        'uid' => $current_user->id(),
        'name' => $current_user->getAccountName(),
        'mail' => $current_user->getEmail(),
        'roles' => $current_user->getRoles(),
      ];
      $debug_message = Xss::filter('<b>Drupal::currentUser():</b><br/><pre>' . print_r($user_info, TRUE) . '</pre>', $tags);
      $rendered_message = Markup::create($debug_message);
      drupal_set_message($rendered_message);
    }

    // Show $_SESSION variables.
    // Work around that drupal_set_message() keeps previous messages in $_SESSION.
    if (!empty($_SESSION)) {
      $session_copy = $_SESSION;
    }
    else {
      $session_copy = [];
    }
    if (isset($session_copy['messages'])) {
      unset($session_copy['messages']);
    }
    $debug_message = Xss::filter('<b>$_SESSION:</b><br/><pre>' . print_r($session_copy, TRUE) . '</pre>', $tags);
    unset($session_copy);
    $rendered_message = Markup::create($debug_message);
    drupal_set_message($rendered_message);

    // Show $_SERVER variables.
    $debug_message = Xss::filter('<b>$_SERVER:</b><br/><pre>' . print_r($_SERVER, TRUE) . '</pre>', $tags);
    $rendered_message = Markup::create($debug_message);
    drupal_set_message($rendered_message);

    // Show config settings.
    $settings = $config->getRawData();
    $adv_config = \Drupal::config('shib_auth.advancedsettings');
    $settings += $adv_config->getRawData();
    ksort($settings);
    $debug_message = Xss::filter('<b>' . $this->t('Module configuration') . ':</b><br/><pre>' . print_r($settings, TRUE) . '</pre>', $tags);
    $rendered_message = Markup::create($debug_message);
    drupal_set_message($rendered_message);

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForShibbolethDebug', 28];
    return $events;
  }

}
