<?php
/**
 * @file
 * Contains \Drupal\iubenda_integration\EventSubscriber\IubendaEventSubscriber.
 */

namespace Drupal\iubenda_integration\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber IubendaEventSubscriber.
 */
class IubendaEventSubscriber implements EventSubscriberInterface {

  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();

    // do not capture redirects or modify XML HTTP Requests
    if ($request->isXmlHttpRequest()) {
      return;
    }

    if(!function_exists("file_get_html")) {
      module_load_include('php', 'iubenda_integration',
        'vendor/iubenda/iubenda-cookie-class/simple_html_dom');
    }

    module_load_include('php', 'iubenda_integration',
      'vendor/iubenda/iubenda-cookie-class/iubenda.class');

    // Parse non administrative pages only.
    $is_admin_page = \Drupal::service('router.admin_context')->isAdminRoute();
    if (!empty($response)
      && !$is_admin_page
      && class_exists('Page')) {

      /*
       * Parse all page's HTML and check for cookies intent lock. For more informations go to
       * https://www.iubenda.com/en/help/posts/1976.
       */
      if (!\Page::consent_given() && !\Page::bot_detected()) {
        $page = new \Page($response->getContent());
        $page->parse();
        $response->setContent($page->get_converted_page());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse', -128],
    ];
  }
}