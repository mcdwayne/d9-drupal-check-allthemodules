<?php

namespace Drupal\eventor\Contracts;

use Symfony\Component\EventDispatcher\Event;

/**
 * Interface Dispatcher.
 */
interface DispatcherInterface {

  /**
   * Dispatch an event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event object.
   */
  public function dispatch(Event $event);

}
