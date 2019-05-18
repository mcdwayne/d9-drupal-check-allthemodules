<?php

namespace Drupal\registration_invite\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class RegisterSubscriber.
 */
class RegisterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function checkIfReferred(GetResponseEvent $event) {
    $request = $event->getRequest();
    $config = \Drupal::config('user.settings');
    if ($request->attributes->get('_route') == 'user.register' && $config->get('register') === 'invite_only') {
      $access = TRUE;
      if (empty($_SESSION['invite_code']) && !(\Drupal::currentUser()->hasPermission('administer users'))) {
        $access = FALSE;
      }
      if (!$access) {
        $event->setResponse(new RedirectResponse($request->getBasePath() . '/register/redirect', 302));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkIfReferred'];
    return $events;
  }

}
