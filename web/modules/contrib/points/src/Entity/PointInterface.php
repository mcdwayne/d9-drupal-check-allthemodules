<?php

namespace Drupal\points\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Point entities.
 *
 * @ingroup points
 */
interface PointInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Point creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Point.
   */
  public function getCreatedTime();

  /**
   * Sets the Point creation timestamp.
   *
   * @param int $timestamp
   *   The Point creation timestamp.
   *
   * @return \Drupal\points\Entity\PointInterface
   *   The called Point entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Implements getPoints method.
   *
   * @return double
   *   Return points.
   */
  public function getPoints();

  /**
   * Implements setPoints method.
   *
   * @param double $points
   *   Points data.
   */
  public function setPoints($points);

  /**
   * Implements getLog method.
   *
   * @return string
   *   Retunr logs.
   */
  public function getLog();

  /**
   * Implements setLog method.
   *
   * @param string $log
   *   Logs data.
   */
  public function setLog($log);

  /**
   * Implements getState method.
   *
   * @return double
   *   Return state.
   */
  public function getState();

}
