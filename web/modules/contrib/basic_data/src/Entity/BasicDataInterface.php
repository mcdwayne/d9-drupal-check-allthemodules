<?php

namespace Drupal\basic_data\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Basic Data entities.
 *
 * @ingroup basic_data
 */
interface BasicDataInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Basic Data name.
   *
   * @return string
   *   Name of the Basic Data.
   */
  public function getName();

  /**
   * Sets the Basic Data name.
   *
   * @param string $name
   *   The Basic Data name.
   *
   * @return \Drupal\basic_data\Entity\BasicDataInterface
   *   The called Basic Data entity.
   */
  public function setName($name);

  /**
   * Gets the Basic Data data.
   *
   * @return string
   *   Data property of the Basic Data.
   */
  public function getData();

  /**
   * Sets the Basic Data data.
   *
   * @param string $data
   *   The Basic Data data.
   *
   * @return \Drupal\basic_data\Entity\BasicDataInterface
   *   The called Basic Data entity.
   */
  public function setData($data);

  /**
   * Gets the Basic Data creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Basic Data.
   */
  public function getCreatedTime();

  /**
   * Sets the Basic Data creation timestamp.
   *
   * @param int $timestamp
   *   The Basic Data creation timestamp.
   *
   * @return \Drupal\basic_data\Entity\BasicDataInterface
   *   The called Basic Data entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Basic Data published status indicator.
   *
   * Unpublished Basic Data are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Basic Data is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Basic Data.
   *
   * @param bool $published
   *   TRUE to set this Basic Data to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\basic_data\Entity\BasicDataInterface
   *   The called Basic Data entity.
   */
  public function setPublished($published);

}
