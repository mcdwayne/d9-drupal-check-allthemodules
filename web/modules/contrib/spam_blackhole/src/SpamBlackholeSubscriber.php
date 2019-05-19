<?php

/**
 * @file
 * Contains \Drupal\spam_blackhole\SpamBlackholeSubscriber.
 */

namespace Drupal\spam_blackhole;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class SpamBlackholeSubscriber implements EventSubscriberInterface {

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents(){
        $events[KernelEvents::REQUEST][] = array('onEvent');
        return $events;
    }

  public function onEvent() {
    global $base_url;
    $user = \Drupal::currentUser();
    // Don't do anything if this is an authenticated user
    if ($user->id() != 0) {
      return;
    }
   _drupal_add_library('spam_blackhole/drupal.spam_blackhole');
   $base_domain = drupal_substr($base_url, 0, strpos($base_url, '/', 8));
   _drupal_add_js(
     array('spam_blackhole' =>
       array(
         'base_url' => $base_domain,
         'dummy_url' => \Drupal::config('spam_blackhole.settings')->get('spam_blackhole_dummy_base_url'),
          )), 'setting');
  }
}
