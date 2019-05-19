<?php

namespace Drupal\tfa_ga_totp\Routing;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class TfaRouteCheck implements EventSubscriberInterface {

  /**
   * Check route.
   */
  public function checkTfaForce(GetResponseEvent $event) {
    global $base_url;
    $roles = \Drupal::config('tfa.settings')->get('required_roles');
    $current_user = \Drupal::currentUser();
    $user_roles = $current_user->getRoles();
    $route_name = \Drupal::routeMatch()->getRouteName();
    $common_roles = array_intersect($user_roles, $roles);

    if (!empty($common_roles) && \Drupal::config('tfa.settings')->get('enabled')) {
      $is_forced = \Drupal::config('tfa.settings')->get('tfa_force_setup');
      $userData = \Drupal::service('user.data');
      $uid = $current_user->id();
      $result = $userData->get('tfa', $uid, 'tfa_user_settings');
      $exclude_pages = ['tfa.overview', 'tfa.validation.setup', 'user.logout'];

      if ($is_forced == 1 && empty($result['data']['plugins']) && !in_array($route_name, $exclude_pages)) {
        $redirect = new RedirectResponse($base_url . '/user/' . $uid . '/security/tfa?tfa_required=1');
        $redirect->send();
      }
    }
  }

  /**
   * Check function.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkTfaForce'];
    return $events;
  }

}
