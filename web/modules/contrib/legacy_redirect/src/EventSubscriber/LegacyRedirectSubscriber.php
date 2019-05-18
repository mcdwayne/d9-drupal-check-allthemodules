<?php

namespace Drupal\legacy_redirect\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Component\Utility\UrlHelper;

/**
 * KernelEvents::REQUEST subscriber for redirecting q=path/to/page requests.
 */
class LegacyRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Detects a q=path/to/page style request and performs a redirect.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelLegacyRedirect(GetResponseEvent $event) {
    global $base_url;
    $request = $event->getRequest();
    $query = $request->query->all();

    if (!empty($query['q']) && stripos($request->getPathInfo(), 'autocomplete') === FALSE) {
      $legacy_path = $query['q'];
      unset($query['q']);
      // Do not use url(), because we want to redirect to the exact q value
      // without invoking hooks or adjusting for aliases or language.
      $url = $base_url . '/index.php/' . UrlHelper::encodePath($legacy_path);
      if ($query) {
        $url .= '?' . UrlHelper::buildQuery($query);
      }
      $event->setResponse(new RedirectResponse($url, 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Run earlier than all the listeners in
    // Drupal\Core\EventSubscriber\PathSubscriber, because there is no need
    // to decode the incoming path, resolve language, etc. if the real path
    // information is in the query string.
    $events[KernelEvents::REQUEST][] = ['onKernelLegacyRedirect', 500];
    return $events;
  }
}
