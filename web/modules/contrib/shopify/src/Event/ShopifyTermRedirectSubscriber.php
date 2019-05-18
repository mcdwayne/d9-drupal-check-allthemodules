<?php

namespace Drupal\shopify\Event;

use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ShopifyWebhookSubscriber.
 *
 * Provides the webhook subscriber functionality.
 */
class ShopifyTermRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Redirects shopify tag/collection taxonomy terms to the right page.
   *
   * @todo: Not sure this is the best way of doing things.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    if (!($term = $event->getRequest()->get('taxonomy_term'))) {
      return;
    }

    if (!$term instanceof Term) {
      return;
    }

    $route_params = $event->getRequest()->get('_route_params');
    if (!isset($route_params['view_id']) || $route_params['view_id'] !== 'taxonomy_term') {
      return;
    }

    switch ($term->bundle()) {
      case ShopifyProduct::SHOPIFY_TAGS_VID:
        $event->setResponse(new RedirectResponse('/' . shopify_store_url('page_tag', $term->id())));
        break;

      case ShopifyProduct::SHOPIFY_COLLECTIONS_VID:
        $event->setResponse(new RedirectResponse('/' . shopify_store_url('page_collection', $term->id())));
        break;
    }

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

}
