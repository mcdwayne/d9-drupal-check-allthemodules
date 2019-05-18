<?php

namespace Drupal\quicker_login\EventSubscriber;

use Drupal\quicker_login\Service\QuickerLoginService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * QuickerLogin event subscriber.
 */
class QuickerLoginSubscriber implements EventSubscriberInterface {

  /**
   * The quicker_login service.
   *
   * @var Drupal\quicker_login\Service\QuickerLoginService
   */
  protected $quickerLoginService;

  /**
   * {@inheritdoc}
   */
  public function __construct(QuickerLoginService $quicker_login_service) {
    $this->quickerLoginService = $quicker_login_service;
  }

  /**
   * Check for login query identifier.
   */
  public function checkForLogin(GetResponseEvent $event) {
    $user_name = NULL;
    $return_uri = NULL;

    // r4032login module returnto.
    if ($event->getRequest()->query->get('returnto')) {
      $return_uri = $event->getRequest()->query->get('returnto');
      list($path, $query) = explode('?', $return_uri);
      $matches = [];
      preg_match("/ql\=[^&]+/", $query, $matches);
      if (count($matches) > 0) {
        $query = $matches[0];
        $return_uri = $path . '?' . str_replace([$matches[0], '?'], '', $query);
      }

      $user_name = str_replace('ql=', '', $query);
    }
    elseif ($event->getRequest()->query->get('ql')) {
      $request = $event->getRequest();
      $user_name = $event->getRequest()->query->get('ql');
      $return_uri = $request->getUri();
      $return_uri = preg_replace("/(&ql=" . $user_name . "|ql=" . $user_name . ")$/", "", $return_uri);
    }

    if ($user_name) {
      $successful = $this->quickerLoginService->loginUserName($user_name);
      if ($successful) {
        $event->setResponse(new RedirectResponse($return_uri));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForLogin'];
    return $events;
  }

}
