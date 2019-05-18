<?php

namespace Drupal\broken_link\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\broken_link\Entity\BrokenLink;
use Drupal\broken_link\Entity\BrokenLinkRedirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class BrokenLinkSubscriber.
 *
 * @package Drupal\broken_link
 */
class BrokenLinkSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.exception'] = ['handleBrokenLink'];

    return $events;
  }

  /**
   * Method is called whenever the kernel.exception event is dispatched.
   *
   * Logs the broken link hit rate. And redirect to path configured, if broken
   * link matches any pattern defined.
   *
   * @param Event $event
   *   Event object.
   */
  public function handleBrokenLink(Event $event) {
    $exception = $event->getException();
    if ($exception instanceof NotFoundHttpException) {
      $request_path = rtrim($event->getRequest()->getPathInfo(), '/');
      $request_args = urldecode(trim($event->getRequest()->getQueryString()));
      $broken_link = new BrokenLink([], 'broken_link');
      $broken_link->merge($request_path, $request_args);
      $broken_link_redirect = new BrokenLinkRedirect([], 'broken_link_redirect');
      $redirect_path = $broken_link_redirect->getRedirectLink($request_path);
      if ($redirect_path) {
        $event->setResponse(new RedirectResponse($redirect_path));
      }
    }
  }

}
