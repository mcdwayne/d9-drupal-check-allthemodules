<?php

namespace Drupal\hn_test_events\EventSubscriber;

use Drupal\hn\Event\HnEntityEvent;
use Drupal\hn\Event\HnHandledEntityEvent;
use Drupal\hn\Event\HnResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultSubscriber.
 */
class DefaultSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    return [
      HnResponseEvent::CREATED => 'createdEvent',
      HnResponseEvent::CREATED_CACHE_MISS => 'createdCacheMissEvent',
      HnResponseEvent::POST_ENTITIES_ADDED => 'postEntitiesAddedEvent',
      HnResponseEvent::PRE_SEND => 'preSendEvent',
      HnEntityEvent::ADDED => 'addedEntityEvent',
      HnHandledEntityEvent::POST_HANDLE => 'postHandledEntityEvent',
    ];
  }

  /**
   *
   */
  public function createdEvent(HnResponseEvent $event) {
    $this->addEventIdToResponse($event, HnResponseEvent::CREATED);
  }

  /**
   *
   */
  public function createdCacheMissEvent(HnResponseEvent $event) {
    $this->addEventIdToResponse($event, HnResponseEvent::CREATED_CACHE_MISS);
  }

  /**
   *
   */
  public function postEntitiesAddedEvent(HnResponseEvent $event) {
    $this->addEventIdToResponse($event, HnResponseEvent::POST_ENTITIES_ADDED);
  }

  /**
   *
   */
  public function preSendEvent(HnResponseEvent $event) {
    $this->addEventIdToResponse($event, HnResponseEvent::PRE_SEND);
  }

  /**
   *
   */
  public function addedEntityEvent(HnEntityEvent $event) {
    $entity = $event->getEntity();
    $entity->created = 1234;
    $event->setEntity($entity);
  }

  /**
   *
   */
  public function postHandledEntityEvent(HnHandledEntityEvent $event) {
    $entity = $event->getHandledEntity();
    $entity[HnHandledEntityEvent::POST_HANDLE] = TRUE;
    $event->setHandledEntity($entity);
  }

  /**
   *
   */
  private function addEventIdToResponse(HnResponseEvent $event, $eventId) {
    $responseData = $event->getResponseData();
    $responseData['subscriber'][$eventId] = TRUE;
    $event->setResponseData($responseData);
  }

}
