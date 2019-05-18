<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Country entities.
 *
 * @ingroup country_state_field
 */
interface CountryInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Country name.
   *
   * @return string
   *   Name of the Country.
   */
  public function getName();

  /**
   * Sets the Country name.
   *
   * @param string $name
   *   The Country name.
   *
   * @return \Drupal\country_state_field\Entity\CountryInterface
   *   The called Country entity.
   */
  public function setName($name);

  /**
   * Gets the Country creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Country.
   */
  public function getCreatedTime();

  /**
   * Sets the Country creation timestamp.
   *
   * @param int $timestamp
   *   The Country creation timestamp.
   *
   * @return \Drupal\country_state_field\Entity\CountryInterface
   *   The called Country entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Country published status indicator.
   *
   * Unpublished Country are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Country is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Country.
   *
   * @param bool $published
   *   TRUE to set this Country to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\country_state_field\Entity\CountryInterface
   *   The called Country entity.
   */
  public function setPublished($published);

}
