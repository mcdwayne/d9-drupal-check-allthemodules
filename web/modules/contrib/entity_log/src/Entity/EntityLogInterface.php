<?php

namespace Drupal\entity_log\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Entity log entities.
 *
 * @ingroup entity_log
 */
interface EntityLogInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Entity log name.
   *
   * @return string
   *   Name of the Entity log.
   */
  public function getName();

  /**
   * Sets the Entity log name.
   *
   * @param string $name
   *   The Entity log name.
   *
   * @return \Drupal\entity_log\Entity\EntityLogInterface
   *   The called Entity log entity.
   */
  public function setName($name);

  /**
   * Gets the Entity log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Entity log.
   */
  public function getCreatedTime();

  /**
   * Sets the Entity log creation timestamp.
   *
   * @param int $timestamp
   *   The Entity log creation timestamp.
   *
   * @return \Drupal\entity_log\Entity\EntityLogInterface
   *   The called Entity log entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Entity log published status indicator.
   *
   * Unpublished Entity log are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Entity log is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Entity log.
   *
   * @param bool $published
   *   TRUE to set this Entity log to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\entity_log\Entity\EntityLogInterface
   *   The called Entity log entity.
   */
  public function setPublished($published);

}
