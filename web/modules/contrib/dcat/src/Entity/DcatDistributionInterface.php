<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Distribution entities.
 *
 * @ingroup dcat
 */
interface DcatDistributionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the Distribution name.
   *
   * @return string
   *   Name of the Distribution.
   */
  public function getName();

  /**
   * Sets the Distribution name.
   *
   * @param string $name
   *   The Distribution name.
   *
   * @return \Drupal\dcat\Entity\DcatDistributionInterface
   *   The called Distribution entity.
   */
  public function setName($name);

  /**
   * Gets the Distribution creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Distribution.
   */
  public function getCreatedTime();

  /**
   * Sets the Distribution creation timestamp.
   *
   * @param int $timestamp
   *   The Distribution creation timestamp.
   *
   * @return \Drupal\dcat\Entity\DcatDistributionInterface
   *   The called Distribution entity.
   */
  public function setCreatedTime($timestamp);

}
