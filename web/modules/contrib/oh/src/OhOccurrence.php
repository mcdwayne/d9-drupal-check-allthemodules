<?php

namespace Drupal\oh;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Defines an opening hours occurrence.
 */
class OhOccurrence extends OhDateRange implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * Message to add to the occurrence.
   *
   * @var string|null
   */
  protected $message;

  /**
   * Whether this occurrence is open.
   *
   * @var bool
   */
  protected $open = FALSE;

  /**
   * Set the message for the occurrence.
   *
   * @param string|null $message
   *   The message for the occurrence, or NULL if no message.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setMessage(?string $message) {
    $this->message = $message;
    return $this;
  }

  /**
   * Get the message for the occurrence.
   *
   * @return string|null
   *   The message for the occurrence, or NULL if no message.
   */
  public function getMessage(): ?string {
    return $this->message;
  }

  /**
   * Set whether this occurrence is open.
   *
   * @param bool $open
   *   Whether this occurrence is open.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setIsOpen(bool $open) {
    $this->open = $open;
    return $this;
  }

  /**
   * Get whether this occurrence is open.
   *
   * @return bool
   *   Whether this occurrence is open.
   */
  public function isOpen(): bool {
    return $this->open;
  }

}
