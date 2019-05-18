<?php

namespace Drupal\licensing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining License entities.
 *
 * @ingroup license
 */
interface LicenseInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the License type.
   *
   * @return string
   *   The License type.
   */
  public function getType();

  /**
   * Gets the License name.
   *
   * @return string
   *   Name of the License.
   */
  public function getName();

  /**
   * Sets the License name.
   *
   * @param string $name
   *   The License name.
   *
   * @return \Drupal\licensing\Entity\LicenseInterface
   *   The called License entity.
   */
  public function setName($name);

  /**
   * Gets the License creation timestamp.
   *
   * @return int
   *   Creation timestamp of the License.
   */
  public function getCreatedTime();

  /**
   * Sets the License creation timestamp.
   *
   * @param int $timestamp
   *   The License creation timestamp.
   *
   * @return \Drupal\licensing\Entity\LicenseInterface
   *   The called License entity.
   */
  public function setCreatedTime($timestamp);

}
