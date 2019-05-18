<?php

namespace Drupal\gzip_html\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Gzips the HTML of the response.
 *
 * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
 */
class GzipHTMLSubscriber implements EventSubscriberInterface {

  /**
   * Gzips the HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The filter event.
   */
  public function response(FilterResponseEvent $event) {
    // Check if the compression is disabled under system performance.
    if (!\Drupal::config('system.performance')->get('gzip_html.gzip_html')) {
      return;
    }

    $response = $event->getResponse();

    // Make sure that the following render classes are the only ones that
    // are minified.
    $allowed_response_classes = [
      'Drupal\big_pipe\Render\BigPipeResponse',
      'Drupal\Core\Render\HtmlResponse',
    ];
    // ... and if the browser support encoding.
    $return_compressed = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE;
    if (in_array(get_class($response), $allowed_response_classes)
      && $return_compressed == TRUE
      && $response->getStatusCode() == 200
    ) {
      $content = $response->getContent();
      $compressed = gzencode($content, 9, FORCE_GZIP);
      $response->setContent($compressed);
      $response->headers->set('Content-Encoding', 'gzip');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['response', -100];
    return $events;
  }

}
