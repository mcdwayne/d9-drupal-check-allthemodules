<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Widget Entity entities.
 *
 * @ingroup stacks
 */
interface WidgetEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Widget label.
   *
   * @return string
   *   The Widget label
   */
  public function label();

  /**
   * Gets the Widget Entity type.
   *
   * @return string
   *   The Widget Entity type.
   */
  public function getType();

  /**
   * Gets the Widget Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Widget Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Widget Entity creation timestamp.
   *
   * @param int $timestamp
   *   The Widget Entity creation timestamp.
   *
   * @return \Drupal\stacks\WidgetEntityInterface
   *   The called Widget Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Increments the widget_times_used value for this entity.
   */
  public function triggerTimesUsed();

  /**
   * Returns the Widget Entity published status indicator.
   *
   * Unpublished Widget Entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Widget Entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Widget Entity.
   *
   * @param bool $published
   *   TRUE to set this Widget Entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\stacks\WidgetEntityInterface
   *   The called Widget Entity entity.
   */
  public function setPublished($published);

}
