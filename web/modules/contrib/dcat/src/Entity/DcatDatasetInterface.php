<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Dataset entities.
 *
 * @ingroup dcat
 */
interface DcatDatasetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the Dataset name.
   *
   * @return string
   *   Name of the Dataset.
   */
  public function getName();

  /**
   * Sets the Dataset name.
   *
   * @param string $name
   *   The Dataset name.
   *
   * @return \Drupal\dcat\Entity\DcatDatasetInterface
   *   The called Dataset entity.
   */
  public function setName($name);

  /**
   * Gets the Dataset creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Dataset.
   */
  public function getCreatedTime();

  /**
   * Sets the Dataset creation timestamp.
   *
   * @param int $timestamp
   *   The Dataset creation timestamp.
   *
   * @return \Drupal\dcat\Entity\DcatDatasetInterface
   *   The called Dataset entity.
   */
  public function setCreatedTime($timestamp);

}
