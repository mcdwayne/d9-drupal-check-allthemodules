<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventDispatcherMock
 *
 * @package Drupal\sir_trevor\Tests\Unit\TestDoubles
 */
class EventDispatcherMock extends EventDispatcherDummy {

  /** @var \Drupal\Tests\sir_trevor\Unit\TestDoubles\ComplexDataValueProcessingEventSubscriberMock */
  private $subscriber;

  /**
   * @param \Drupal\Tests\sir_trevor\Unit\TestDoubles\ComplexDataValueProcessingEventSubscriberMock $subscriber
   */
  public function setSubscriber(ComplexDataValueProcessingEventSubscriberMock $subscriber) {
    $this->subscriber = $subscriber;
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch($eventName, Event $event = NULL) {
    if (!empty($this->subscriber)) {
      $this->subscriber->processEvent($event);
    }

    return $event;
  }

}
