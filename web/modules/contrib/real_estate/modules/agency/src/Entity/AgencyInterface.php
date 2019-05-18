<?php

namespace Drupal\real_estate_agency\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Agency entities.
 *
 * @ingroup real_estate_agency
 */
interface AgencyInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Agency type.
   *
   * @return string
   *   The Agency type.
   */
  public function getType();

  /**
   * Gets the Agency name.
   *
   * @return string
   *   Name of the Agency.
   */
  public function getName();

  /**
   * Sets the Agency name.
   *
   * @param string $name
   *   The Agency name.
   *
   * @return \Drupal\real_estate_agency\Entity\AgencyInterface
   *   The called Agency entity.
   */
  public function setName($name);

  /**
   * Gets the Agency creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Agency.
   */
  public function getCreatedTime();

  /**
   * Sets the Agency creation timestamp.
   *
   * @param int $timestamp
   *   The Agency creation timestamp.
   *
   * @return \Drupal\real_estate_agency\Entity\AgencyInterface
   *   The called Agency entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Agency published status indicator.
   *
   * Unpublished Agency are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Agency is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Agency.
   *
   * @param bool $published
   *   TRUE to set this Agency to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\real_estate_agency\Entity\AgencyInterface
   *   The called Agency entity.
   */
  public function setPublished($published);

}
