<?php

namespace Drupal\rokka\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Rokka Metadata entities.
 *
 * @ingroup rokka
 */
interface RokkaMetadataInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Rokka Metadata name.
   *
   * @return string
   *   Hash of the Rokka Metadata.
   */
  public function getHash();

  /**
   * Sets the Rokka Metadata hash.
   *
   * @param string $hash
   *   The Rokka Metadata hash.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setHash($hash);


  /**
   * Sets the Rokka Metadata binary hash.
   *
   * @param string $binary_hash
   *   The Rokka Metadata binary_hash.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setBinaryHash($binary_hash);

  /**
   * Sets the Rokka Metadata height.
   *
   * @param string $height
   *   The Rokka Metadata height.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setHeight($height);

  /**
   * Sets the Rokka Metadata width.
   *
   * @param string $width
   *   The Rokka Metadata $width.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setWidth($width);

  /**
   * Sets the Rokka Metadata image format.
   *
   * @param string $width
   *   The Rokka Metadata $image_format.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setFormat($image_format);

  /**
   * Gets the Rokka Metadata creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Rokka Metadata.
   */
  public function getCreatedTime();

  /**
   * Sets the Rokka Metadata creation timestamp.
   *
   * @param int $timestamp
   *   The Rokka Metadata creation timestamp.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Rokka Metadata published status indicator.
   *
   * Unpublished Rokka Metadata are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Rokka Metadata is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Rokka Metadata.
   *
   * @param bool $published
   *   TRUE to set this Rokka Metadata to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\rokka\Entity\RokkaMetadataInterface
   *   The called Rokka Metadata entity.
   */
  public function setPublished($published);

}
