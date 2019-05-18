<?php

namespace Drupal\dblog_persistent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Persistent Log Message Type entities.
 */
interface ChannelInterface extends ConfigEntityInterface {

  /**
   * Get all types matched by this filter.
   *
   * @return string[]
   */
  public function getTypes(): array;

  /**
   * Get the minimum severity of this filter.
   *
   * @return int[]
   */
  public function getLevels(): array;

  /**
   * Get the message substring.
   *
   * @return string
   */
  public function getMessage(): string;

  /**
   * Check a given message against the configured filters.
   *
   * @param int $level
   * @param string $type
   * @param string $message
   *
   * @return bool
   */
  public function matches(int $level, string $type, string $message): bool;

}
