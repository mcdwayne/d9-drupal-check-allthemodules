<?php

namespace Drupal\redirect_403_to_login_page\EventSubscriber;

use Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Exception subscriber for handling core default HTML error pages.
 */
class Redirect403ToLoginExceptionHtmlSubscriber extends DefaultExceptionHtmlSubscriber {
  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
      if (\Drupal::currentUser()->isAnonymous()) {
        $url = \Drupal::urlGenerator()->generate('user.login',['destination' => $event->getRequest()->getRequestUri()]);
        $response = new RedirectResponse($url);
        $event->setResponse($response);
      } else {
          parent::on403($event);
      }
  }

}
