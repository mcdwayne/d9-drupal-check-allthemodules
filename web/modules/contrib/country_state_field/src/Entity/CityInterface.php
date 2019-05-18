<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining City entities.
 *
 * @ingroup country_state_field
 */
interface CityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getState();

  /**
   * Gets the City name.
   *
   * @return string
   *   Name of the City.
   */
  public function getName();

  /**
   * Sets the City name.
   *
   * @param string $name
   *   The City name.
   *
   * @return \Drupal\country_state_field\Entity\CityInterface
   *   The called City entity.
   */
  public function setName($name);

  /**
   * Gets the City creation timestamp.
   *
   * @return int
   *   Creation timestamp of the City.
   */
  public function getCreatedTime();

  /**
   * Sets the City creation timestamp.
   *
   * @param int $timestamp
   *   The City creation timestamp.
   *
   * @return \Drupal\country_state_field\Entity\CityInterface
   *   The called City entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the City published status indicator.
   *
   * Unpublished City are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the City is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a City.
   *
   * @param bool $published
   *   TRUE to set this City to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\country_state_field\Entity\CityInterface
   *   The called City entity.
   */
  public function setPublished($published);

}
