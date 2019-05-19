<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a simplenews stats entity type.
 */
interface SimplenewsStatsItemInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the simplenews stats title.
   *
   * @return string
   *   Title of the simplenews stats.
   */
  public function getTitle();

  /**
   * Sets the simplenews stats title.
   *
   * @param string $title
   *   The simplenews stats title.
   *
   * @return \Drupal\simplenews_stats\SimplenewsStatsInterface
   *   The called simplenews stats entity.
   */
  public function setTitle($title);

  /**
   * Gets the simplenews stats creation timestamp.
   *
   * @return int
   *   Creation timestamp of the simplenews stats.
   */
  public function getCreatedTime();

  /**
   * Sets the simplenews stats creation timestamp.
   *
   * @param int $timestamp
   *   The simplenews stats creation timestamp.
   *
   * @return \Drupal\simplenews_stats\SimplenewsStatsInterface
   *   The called simplenews stats entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the simplenews stats status.
   *
   * @return bool
   *   TRUE if the simplenews stats is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the simplenews stats status.
   *
   * @param bool $status
   *   TRUE to enable this simplenews stats, FALSE to disable.
   *
   * @return \Drupal\simplenews_stats\SimplenewsStatsInterface
   *   The called simplenews stats entity.
   */
  public function setStatus($status);

}
