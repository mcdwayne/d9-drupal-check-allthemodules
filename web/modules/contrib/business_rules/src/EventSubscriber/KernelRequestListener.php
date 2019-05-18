<?php

namespace Drupal\business_rules\EventSubscriber;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelRequestListener.
 *
 * Provides the subscribed event for the plugin
 * BusinessRulesReactsOn\KernelRequest.
 *
 * @package Drupal\business_rules\EventSubscriber
 */
class KernelRequestListener implements EventSubscriberInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onKernelRequest', 1000]];
  }

  /**
   * Create a new event for BusinessRules plugin KernelRequest.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  public function onKernelRequest(Event $event) {

    $reacts_on_definition = $this->container
      ->get('plugin.manager.business_rules.reacts_on')
      ->getDefinition('kernel_request');

    $new_event = new BusinessRulesEvent(NULL, [
      'entity_type_id'   => NULL,
      'bundle'           => NULL,
      'entity'           => NULL,
      'entity_unchanged' => NULL,
      'reacts_on'        => $reacts_on_definition,
    ]);
    /** @var \Symfony\Component\EventDispatcher\EventDispatcher $event_dispatcher */
    $event_dispatcher = $this->container->get('event_dispatcher');
    $event_dispatcher->dispatch($reacts_on_definition['eventName'], $new_event);
  }

}
