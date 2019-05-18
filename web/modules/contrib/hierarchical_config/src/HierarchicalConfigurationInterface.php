<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\HierarchicalConfigurationInterface.
 */

namespace Drupal\hierarchical_config;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Hierarchical configuration entities.
 *
 * @ingroup hierarchical_config
 */
interface HierarchicalConfigurationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Hierarchical configuration type.
   *
   * @return string
   *   The Hierarchical configuration type.
   */
  public function getType();

  /**
   * Gets the Hierarchical configuration name.
   *
   * @return string
   *   Name of the Hierarchical configuration.
   */
  public function getName();

  /**
   * Sets the Hierarchical configuration name.
   *
   * @param string $name
   *   The Hierarchical configuration name.
   *
   * @return \Drupal\hierarchical_config\HierarchicalConfigurationInterface
   *   The called Hierarchical configuration entity.
   */
  public function setName($name);

  /**
   * Gets the Hierarchical configuration creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Hierarchical configuration.
   */
  public function getCreatedTime();

  /**
   * Sets the Hierarchical configuration creation timestamp.
   *
   * @param int $timestamp
   *   The Hierarchical configuration creation timestamp.
   *
   * @return \Drupal\hierarchical_config\HierarchicalConfigurationInterface
   *   The called Hierarchical configuration entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Hierarchical configuration published status indicator.
   *
   * Unpublished Hierarchical configuration are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Hierarchical configuration is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Hierarchical configuration.
   *
   * @param bool $published
   *   TRUE to set this Hierarchical configuration to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\hierarchical_config\HierarchicalConfigurationInterface
   *   The called Hierarchical configuration entity.
   */
  public function setPublished($published);

}
