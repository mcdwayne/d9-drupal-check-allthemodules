<?php

namespace Drupal\eventor\Service;

use Drupal\eventor\Contracts\DispatcherInterface;
use Stringy\Stringy;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EventDispatcher.
 */
class EventDispatcher implements DispatcherInterface {

  /**
   * The dispatcher instance.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Create a new EventDispatcher instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Dispatch all raised events.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event object.
   */
  public function dispatch(Event $event) {
    $eventName = $this->getEventName($event);

    $this->dispatcher->dispatch($eventName, $event);
  }

  /**
   * Make the fired event name look more object-oriented.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event object.
   *
   * @return string
   *   Event name.
   */
  protected function getEventName(Event $event) {
    $oClass = new \ReflectionClass($event);

    return Stringy::create($oClass->getShortName())
      ->underscored()
      ->__toString();
  }

}
