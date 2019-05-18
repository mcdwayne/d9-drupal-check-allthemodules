<?php

namespace Drupal\node_subscription\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Node subscription entities.
 *
 * @ingroup node_subscription
 */
interface NodeSubscriptionStorageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Node subscription name.
   *
   * @return string
   *   Name of the Node subscription.
   */
  public function getName();

  /**
   * Sets the Node subscription name.
   *
   * @param string $name
   *   The Node subscription name.
   *
   * @return \Drupal\node_subscription\Entity\NodeSubscriptionStorageInterface
   *   The called Node subscription entity.
   */
  public function setName($name);

  /**
   * Gets the Node subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Node subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Node subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Node subscription creation timestamp.
   *
   * @return \Drupal\node_subscription\Entity\NodeSubscriptionStorageInterface
   *   The called Node subscription entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Node subscription published status indicator.
   *
   * Unpublished Node subscription are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Node subscription is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Node subscription.
   *
   * @param bool $published
   *   TRUE to set this Node subscription to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\node_subscription\Entity\NodeSubscriptionStorageInterface
   *   The called Node subscription entity.
   */
  public function setPublished($published);

}
