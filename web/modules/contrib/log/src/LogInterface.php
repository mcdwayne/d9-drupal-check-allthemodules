<?php

/**
 * @file
 * Contains \Drupal\log\LogInterface.
 */

namespace Drupal\log;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Log entities.
 *
 * @ingroup log
 */
interface LogInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the log name.
   *
   * @return string
   *   The log name.
   */
  public function getName();

  /**
   * Gets the log type.
   *
   * @return string
   *   The log type.
   */
  public function getType();

  /**
   * Gets the log type name.
   *
   * @return string
   *   The log type name.
   */
  public function getTypeName();

  /**
   * Gets the log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the log.
   */
  public function getCreatedTime();
}
