<?php

namespace Drupal\filebrowser\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Filebrowser metadata entity entities.
 *
 * @ingroup filebrowser
 */
interface FilebrowserMetadataEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Metadata entity name.
   *
   * @return string
   *   Name of the Metadata entity.
   */
  public function getName();

  /**
   * Sets the Metadata entity name.
   *
   * @param string $name
   *   The Metadata entity name.
   *
   * @return \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface
   *   The called Metadata entity entity.
   */
  public function setName($name);

  /**
   * Gets the Metadata entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Metadata entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Metadata entity creation timestamp.
   *
   * @param int $timestamp
   *   The Metadata entity creation timestamp.
   *
   * @return \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface
   *   The called Metadata entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Metadata entity published status indicator.
   *
   * Unpublished Metadata entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Metadata entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Metadata entity.
   *
   * @param bool $published
   *   TRUE to set this Metadata entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface
   *   The called Metadata entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the content of the Metadata entity.
   *
   * @return string
   *   CSerialised content of the Metadata entity.
   */
  public function getContent();

  /**
   * Sets the content of  Metadata entity.
   *
   * @param string $content
   * Entity contents. Metadata as serialised string
   *
   * @return \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface
   *   The called Metadata entity entity.
   */
  public function setContent($content);

  /**
   * Gets the fid of file owning this Metadata.
   *
   * @return integer
   */
  public function getFid();

  /**
   * Sets the fid of file owning this Metadata.
   *
   * @param integer $fid
   *
   * @return \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface
   *   The called Metadata entity entity.
   */
  public function setFid($fid);

  /**
   * Get the theme for this metadata
   */
  public function getTheme();

  /**
   * Sets the theme for this metadata
   * @param string $theme
   */
  public function setTheme($theme);

}
