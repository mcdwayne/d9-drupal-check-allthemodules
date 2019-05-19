<?php

namespace Drupal\views_ajax_get\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adding the headers for cache control so that these requests can be cached.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof ViewAjaxResponse) {
      return;
    }

    $request = $event->getRequest();
    if (RouteMatch::createFromRequest($request)
      ->getRouteName() === 'views.ajax') {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $response->getView();
      // If view is excluded from conversion, don't add cache headers.
      if (_views_ajax_get_is_ajax_get_view($view)) {
        $view->getRequest()->headers->set('Cache-Control', 'public, max-age=' . \Drupal::config('system_performance_settings')->get('cache.page.max_age'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];
    return $events;
  }

}
