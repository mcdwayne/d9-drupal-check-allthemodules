<?php

namespace Drupal\remove_meta_and_headers\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener for handling Response Header.
 */
class RemoveResponseHeadersSubscriber implements EventSubscriberInterface {

  /**
   * ConfigFactory Object passed as Dependency Injection.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * RemoveResponseHeadersSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Register response headers item remove handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function removeResponseHeadersItem(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // If TRUE, fire event to remove X-Generator from Response.
    if (1 == $this->configFactory->get('response_header_x_generator')) {
      $response->headers->remove('X-Generator');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['removeResponseHeadersItem', -10];
    return $events;
  }

}
