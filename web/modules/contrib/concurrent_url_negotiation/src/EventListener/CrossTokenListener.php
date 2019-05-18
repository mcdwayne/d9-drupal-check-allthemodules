<?php

namespace Drupal\concurrent_url_negotiation\EventListener;

use Drupal\concurrent_url_negotiation\CrossAuth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CrossTokenListener.
 */
class CrossTokenListener implements EventSubscriberInterface {

  /**
   * Cross authentication service to log in users from token.
   *
   * @var \Drupal\concurrent_url_negotiation\CrossAuth
   */
  protected $crossAuth;

  /**
   * CrossTokenListener constructor.
   *
   * @param CrossAuth $crossAuth
   *    The cross authentication service.
   */
  public function __construct(CrossAuth $crossAuth) {
    $this->crossAuth = $crossAuth;
  }

  /**
   * Authenticates the user if the request query contains token.
   *
   * If the token was found in the query it also redirects to same path without
   * the token and id.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *    The request event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $requestQuery = $request->query;

    if ($requestQuery->has('cross_id')) {
      $this->crossAuth->authenticate(
        $requestQuery->get('cross_id'),
        $requestQuery->get('cross_token')
      );

      $redirectUrl = $request->getSchemeAndHttpHost() .
        $request->getBaseUrl() .
        $request->getPathInfo();

      // Remove the token and id from the query bag and rebuild the query.
      $requestQuery->remove('cross_id');
      $requestQuery->remove('cross_token');

      if ($queryString = http_build_query($requestQuery->all())) {
        $redirectUrl .= '?' . $queryString;
      }

      // If there is a destination in the request query than our redirect
      // response will be overwritten. Prevent that.
      if ($requestQuery->has('destination')) {
        $requestQuery->remove('destination');
      }

      $event->setResponse(new RedirectResponse($redirectUrl));
    }
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    return $events;
  }

}
