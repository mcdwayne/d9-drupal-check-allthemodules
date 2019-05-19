<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Spectra noun entities.
 *
 * @ingroup spectra
 */
interface SpectraNounInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Spectra noun name.
   *
   * @return string
   *   Name of the Spectra noun.
   */
  public function getName();

  /**
   * Sets the Spectra noun name.
   *
   * @param string $name
   *   The Spectra noun name.
   *
   * @return \Drupal\spectra\Entity\SpectraNounInterface
   *   The called Spectra noun entity.
   */
  public function setName($name);

  /**
   * Gets the Spectra noun creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Spectra noun.
   */
  public function getCreatedTime();

  /**
   * Sets the Spectra noun creation timestamp.
   *
   * @param int $timestamp
   *   The Spectra noun creation timestamp.
   *
   * @return \Drupal\spectra\Entity\SpectraNounInterface
   *   The called Spectra noun entity.
   */
  public function setCreatedTime($timestamp);

}
