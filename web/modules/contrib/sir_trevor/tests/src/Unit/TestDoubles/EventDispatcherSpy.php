<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventDispatcherSpy
 *
 * @package Drupal\Tests\sir_trevor\Unit\TestDoubles
 */
class EventDispatcherSpy extends EventDispatcherMock {
  /** @var array */
  private $dispatchedEvents = [];

  /**
   * {@inheritdoc}
   */
  public function dispatch($eventName, Event $event = NULL) {
    $this->dispatchedEvents[] = $eventName;
    return parent::dispatch($eventName, $event);
  }

  /**
   * @return array
   */
  public function getDispatchedEvents() {
    return $this->dispatchedEvents;
  }

}
