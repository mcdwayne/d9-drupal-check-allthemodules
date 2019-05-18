<?php

namespace Drupal\entity_modifier\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Entity modifier entities.
 *
 * @ingroup entity_modifier
 */
interface EntityModifierInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Entity modifier name.
   *
   * @return string
   *   Name of the Entity modifier.
   */
  public function getName();

  /**
   * Sets the Entity modifier name.
   *
   * @param string $name
   *   The Entity modifier name.
   *
   * @return \Drupal\entity_modifier\Entity\EntityModifierInterface
   *   The called Entity modifier entity.
   */
  public function setName($name);

  /**
   * Gets the Entity modifier creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Entity modifier.
   */
  public function getCreatedTime();

  /**
   * Sets the Entity modifier creation timestamp.
   *
   * @param int $timestamp
   *   The Entity modifier creation timestamp.
   *
   * @return \Drupal\entity_modifier\Entity\EntityModifierInterface
   *   The called Entity modifier entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Entity modifier published status indicator.
   *
   * Unpublished Entity modifier are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Entity modifier is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Entity modifier.
   *
   * @param bool $published
   *   TRUE to set this Entity modifier to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\entity_modifier\Entity\EntityModifierInterface
   *   The called Entity modifier entity.
   */
  public function setPublished($published);

}
