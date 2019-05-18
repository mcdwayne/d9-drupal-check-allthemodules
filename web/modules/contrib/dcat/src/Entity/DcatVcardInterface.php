<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining vCard entities.
 *
 * @ingroup dcat
 */
interface DcatVcardInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the vCard type.
   *
   * @return string
   *   The vCard type.
   */
  public function getType();

  /**
   * Gets the vCard name.
   *
   * @return string
   *   Name of the vCard.
   */
  public function getName();

  /**
   * Sets the vCard name.
   *
   * @param string $name
   *   The vCard name.
   *
   * @return \Drupal\dcat\Entity\DcatVcardInterface
   *   The called vCard entity.
   */
  public function setName($name);

  /**
   * Gets the vCard creation timestamp.
   *
   * @return int
   *   Creation timestamp of the vCard.
   */
  public function getCreatedTime();

  /**
   * Sets the vCard creation timestamp.
   *
   * @param int $timestamp
   *   The vCard creation timestamp.
   *
   * @return \Drupal\dcat\Entity\DcatVcardInterface
   *   The called vCard entity.
   */
  public function setCreatedTime($timestamp);

}
