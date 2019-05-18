<?php

namespace Drupal\rocketship\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Rocketship Feed entities.
 *
 * @ingroup rocketship
 */
interface FeedInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Rocketship Feed label.
   *
   * @return string
   *   Label of the Rocketship Feed.
   */
  public function getLabel();

  /**
   * Sets the Rocketship Feed label.
   *
   * @param string $label
   *   The Rocketship Feed label.
   *
   * @return \Drupal\rocketship\Entity\FeedInterface
   *   The called Rocketship Feed entity.
   */
  public function setLabel($label);

  /**
   * Gets the Rocketship Feed creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Rocketship Feed.
   */
  public function getCreatedTime();

  /**
   * Sets the Rocketship Feed creation timestamp.
   *
   * @param int $timestamp
   *   The Rocketship Feed creation timestamp.
   *
   * @return \Drupal\rocketship\Entity\FeedInterface
   *   The called Rocketship Feed entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Rocketship Feed enabled status indicator.
   *
   * Disabled feeds are not processed for new nodes.
   *
   * @return bool
   *   TRUE if the Rocketship Feed is enabled.
   */
  public function isEnabled();

  /**
   * Sets the enabled status of a Rocketship Feed.
   *
   * @param bool $enabled
   *   TRUE to set this Rocketship Feed to enabled, FALSE otherwise.
   *
   * @return \Drupal\rocketship\Entity\FeedInterface
   *   The called Rocketship Feed entity.
   */
  public function setEnabled($enabled);

  /**
   * Returns the feed URL string that can be used to query issues.
   *
   * @return string
   *   The feed URL.
   */
  public function getFeedUrl();

}
