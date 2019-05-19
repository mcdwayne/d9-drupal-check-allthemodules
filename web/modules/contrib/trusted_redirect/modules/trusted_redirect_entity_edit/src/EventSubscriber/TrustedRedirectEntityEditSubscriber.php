<?php

namespace Drupal\trusted_redirect_entity_edit\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Trusted redirect entity edit subscriber to prevent additional redirects.
 */
class TrustedRedirectEntityEditSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Redirect to trusted host.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondAssureEntityEditRedirect(FilterResponseEvent $event) {
    // Apply only for entity edit url.
    if ($this->routeMatch->getRouteName() != 'trusted_redirect_entity_edit.edit.controller') {
      return;
    }
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      // If the response is already being redirected we want the job to be
      // finished. There are other subscribers that can modify our response.
      // For instance, the regular RedirectResponseSubscriber would redirect
      // the response if destination query string is presented. Additionally
      // custom module trusted_redirect may redirect to external host.
      // But this particular redirect should happen internally, it's just
      // another representation of entity edit url (node edit form, user edit
      // form, etc..) so it needs to stay within current origin.
      $event->stopPropagation();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // First or very first.
    $events[KernelEvents::RESPONSE][] = ['onRespondAssureEntityEditRedirect', 1001];
    return $events;
  }

}
