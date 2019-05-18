<?php
/**
 * @file
 * Contains
 *   \Drupal\page_manager_search\EventSubscriber\PageManagerSearchRedirect.
 */

namespace Drupal\page_manager_search\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class PageManagerSearchRedirect
 *
 * @package page_manager_search
 *
 * Redirect from Page Manager Search page to Page Variant page.
 */
class PageManagerSearchRedirect implements EventSubscriberInterface {

  public function checkForRedirection(GetResponseEvent $event) {
    $baseUrl = $event->getRequest()->getBaseUrl();
    $attr = $event->getRequest()->attributes;

    if ($attr !== NULL && $attr->get('page_manager_search') !== NULL) {
      $path = $attr->get('page_manager_search')
        ->get('path_to_page')
        ->getValue();

      $event->setResponse(new RedirectResponse($baseUrl . $path[0]['value']));
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
