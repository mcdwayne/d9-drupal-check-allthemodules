<?php

namespace Drupal\transactionalphp;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Defines a base class for all database transaction events.
 */
class TransactionalPhpEvent extends GenericEvent {

  /**
   * Get argument by key.
   *
   * @param string $key
   *   Key.
   *
   * @throws \InvalidArgumentException
   *   If key is not found.
   *
   * @return mixed
   *   Contents of array key.
   */
  public function &getArgument($key) {
    if ($this->hasArgument($key)) {
      return $this->arguments[$key];
    }

    throw new \InvalidArgumentException(sprintf('Argument "%s" not found.', $key));
  }

}
