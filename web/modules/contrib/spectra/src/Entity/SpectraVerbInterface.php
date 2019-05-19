<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Spectra verb entities.
 *
 * @ingroup spectra
 */
interface SpectraVerbInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Spectra verb name.
   *
   * @return string
   *   Name of the Spectra verb.
   */
  public function getName();

  /**
   * Sets the Spectra verb name.
   *
   * @param string $name
   *   The Spectra verb name.
   *
   * @return \Drupal\spectra\Entity\SpectraVerbInterface
   *   The called Spectra verb entity.
   */
  public function setName($name);

  /**
   * Gets the Spectra verb creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Spectra verb.
   */
  public function getCreatedTime();

  /**
   * Sets the Spectra verb creation timestamp.
   *
   * @param int $timestamp
   *   The Spectra verb creation timestamp.
   *
   * @return \Drupal\spectra\Entity\SpectraVerbInterface
   *   The called Spectra verb entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Spectra verb published status indicator.
   *
   * Unpublished Spectra verb are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Spectra verb is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Spectra verb.
   *
   * @param bool $published
   *   TRUE to set this Spectra verb to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\spectra\Entity\SpectraVerbInterface
   *   The called Spectra verb entity.
   */
  public function setPublished($published);

}
