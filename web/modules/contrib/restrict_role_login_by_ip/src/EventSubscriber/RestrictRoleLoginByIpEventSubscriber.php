<?php

namespace Drupal\restrict_role_login_by_ip\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber RestrictRoleLoginByIpEventSubscriber.
 */
class RestrictRoleLoginByIpEventSubscriber implements EventSubscriberInterface {

  /**
   * Set message when user is denied access.
   */
  public function onKernelRequest($event) {
    $queryLogout = \Drupal::request()->query->get('logout');
    if (!empty($queryLogout) && $queryLogout == 'restrict') {
      drupal_set_message(t('You are not allowed to login from this IP address. Please contact the Site Administrator.'), 'error', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onKernelRequest',
    ];
  }

}
