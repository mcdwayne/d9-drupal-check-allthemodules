<?php

namespace Drupal\entity_resource_layer\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber that serializes and removes ResourceResponses' data.
 */
class RestLoggerSubscriber implements EventSubscriberInterface {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The level of logging aggression.
   *
   * @var int
   */
  protected $logLevel;

  /**
   * RestLoggerSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route match service.
   * @param int $logLevel
   *   The level of aggression on logging.
   */
  public function __construct(LoggerInterface $logger, RequestStack $requestStack, CurrentRouteMatch $routeMatch, $logLevel = 1) {
    $this->logger = $logger;
    $this->routeMatch = $routeMatch;
    $this->requestStack = $requestStack;
    $this->logLevel = $logLevel;
  }

  /**
   * Serializes ResourceResponse responses data, and removes that data.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    if ($this->logLevel > 0 && !$this->isRestRequest()) {
      return;
    }

    $response = $event->getResponse();
    $this->logger->info('Response: ' . $response->getContent() . '; ');
  }

  /**
   * Listens for exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The exception event object.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    // Route might not be set but error can happen.
    if (!$this->isRestRequest()) {
      return;
    }

    $exception = $event->getException();
    $this->logger->error('Exception [' . get_class($exception) . ']: ' . $exception->getMessage() . '\n Trace:\n ' . $exception->getTraceAsString());
  }

  /**
   * Detects whether the request is a resource request.
   *
   * @return bool
   *   Rest request?
   */
  protected function isRestRequest() {
    // We can't really detect if the request is for a resource when no route
    // is found. We could check the _format query arg in the request, but
    // for any not found route that argument can be set, which might not be a
    // resource route.
    if (!($route = $this->routeMatch->getRouteObject())) {
      return FALSE;
    }

    $request = $this->requestStack->getCurrentRequest();

    return $route->hasRequirement('_format') &&
      $request->query->has('_format') &&
      $request->query->get('_format') !== 'html';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after \Drupal\rest\EventSubscriber\ResourceResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 4];
    $events[KernelEvents::EXCEPTION][] = ['onException', -50];
    return $events;
  }

}
