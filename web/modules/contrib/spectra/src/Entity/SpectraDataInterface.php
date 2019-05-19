<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a SpectraData entity.
 * @ingroup spectra
 */
interface SpectraDataInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Spectra data name.
   *
   * @return string
   *   Name of the Spectra data.
   */
  public function getName();

  /**
   * Sets the Spectra data name.
   *
   * @param string $name
   *   The Spectra data name.
   *
   * @return \Drupal\spectra\Entity\SpectraDataInterface
   *   The called Spectra data entity.
   */
  public function setName($name);

  /**
   * Gets the Spectra data creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Spectra data.
   */
  public function getCreatedTime();

  /**
   * Sets the Spectra data creation timestamp.
   *
   * @param int $timestamp
   *   The Spectra data creation timestamp.
   *
   * @return \Drupal\spectra\Entity\SpectraDataInterface
   *   The called Spectra data entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Spectra data published status indicator.
   *
   * Unpublished Spectra data are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Spectra data is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Spectra data.
   *
   * @param bool $published
   *   TRUE to set this Spectra data to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\spectra\Entity\SpectraDataInterface
   *   The called Spectra data entity.
   */
  public function setPublished($published);
}

?>