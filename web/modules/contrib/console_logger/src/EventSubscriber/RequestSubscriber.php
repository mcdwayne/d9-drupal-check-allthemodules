<?php

/**
 * @file
 * Contains the even listeners that logs the incoming request to the console.
 */

namespace Drupal\console_logger\EventSubscriber;

use Drupal\console_logger\RequestLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Implement a request listener for logging requests to the console.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * The request logger service.
   *
   * @var \Drupal\console_logger\RequestLogger
   */
  protected $requestLogger;

  /**
   * Constructs and event subscriber to log request terminations.
   *
   * @param \Drupal\console_logger\RequestLogger $requestLogger
   *   The request logger service.
   */
  public function __construct(RequestLogger $requestLogger) {
    $this->requestLogger = $requestLogger;
  }

  /**
   * Log the termination of the request.
   */
  public function onTerminate(PostResponseEvent $response_event) {
    $this->requestLogger->terminateRequest($response_event);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onTerminate');
    return $events;

  }

}
