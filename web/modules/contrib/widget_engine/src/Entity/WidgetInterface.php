<?php

namespace Drupal\widget_engine\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Widget entities.
 *
 * @ingroup widget_engine
 */
interface WidgetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Widget type.
   *
   * @return string
   *   The Widget type.
   */
  public function getType();

  /**
   * Gets the Widget name.
   *
   * @return string
   *   Name of the Widget.
   */
  public function getName();

  /**
   * Sets the Widget name.
   *
   * @param string $name
   *   The Widget name.
   *
   * @return \Drupal\widget_engine\Entity\WidgetInterface
   *   The called Widget entity.
   */
  public function setName($name);

  /**
   * Gets the Widget creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Widget.
   */
  public function getCreatedTime();

  /**
   * Sets the Widget creation timestamp.
   *
   * @param int $timestamp
   *   The Widget creation timestamp.
   *
   * @return \Drupal\widget_engine\Entity\WidgetInterface
   *   The called Widget entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Widget published status indicator.
   *
   * Unpublished Widget are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Widget is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Widget.
   *
   * @param bool $published
   *   TRUE to set this Widget to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\widget_engine\Entity\WidgetInterface
   *   The called Widget entity.
   */
  public function setPublished($published);

}
