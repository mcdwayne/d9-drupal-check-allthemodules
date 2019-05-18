<?php

namespace Drupal\entity_router;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\entity_router\Event\EntityResponseEvent;
use Drupal\entity_router\Response\EntityResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * An instance of the "event_dispatcher" service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * An instance of the "MODULE.plugin.manager.event_response_handler" service.
   *
   * @var \Drupal\entity_router\EntityResponseHandlerManager
   */
  protected $entityResponseHandlerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, EntityResponseHandlerManager $entity_response_handler_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->entityResponseHandlerManager = $entity_response_handler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];

    $events[KernelEvents::RESPONSE][] = ['onResponse', 200];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event): void {
    $response = $event->getResponse();

    if ($response instanceof EntityResponse) {
      $entity = $response->getEntity();
      $request = $event->getRequest();
      // The Drupal's "_format" is forbidden by JSON:API 2.x.
      $format = $request->get('format', 'html');

      try {
        // Transform the response.
        $response = $this->entityResponseHandlerManager
          ->createInstance($format)
          ->getResponse($request, $entity)
          // Set actual status code before dispatching an event.
          ->setStatusCode($response->getStatusCode());

        $this->eventDispatcher->dispatch(EntityResponseEvent::NAME, new EntityResponseEvent($request, $response, $entity));
      }
      catch (PluginException $e) {
        $status = 400;
        $response = new Response(sprintf('<h1>%d Bad Request.</h1><p>The "%s" request format cannot be handled.</p>', $status, $format), $status);
      }

      $event->setResponse($response);
    }
  }

}
