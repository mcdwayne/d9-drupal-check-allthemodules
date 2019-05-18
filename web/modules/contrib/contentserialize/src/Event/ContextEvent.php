<?php

namespace Drupal\contentserialize\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * An import event with the current serialization context array.
 *
 * This allows subscribers to read/write the context used during serialization.
 */
class ContextEvent extends Event {

  /**
   * Create a context event.
   *
   * @param array $context
   *   (optional) The current context, if any.
   */
  public function __construct(array $context = []) {
    $this->context = $context;
  }

  /**
   * The context.
   *
   * @var array
   */
  public $context;

}
