<?php

namespace Drupal\track_file_downloads\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining File Tracker entities.
 */
interface FileTrackerInterface extends ContentEntityInterface {

  /**
   * Gets the File Tracker creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime();

  /**
   * Sets the File Tracker creation timestamp.
   *
   * @param int $timestamp
   *   The File Tracker creation timestamp.
   *
   * @return \Drupal\track_file_downloads\Entity\FileTrackerInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the file if one exists.
   *
   * @return \Drupal\file\FileInterface
   *   The file this entity is tracking.
   */
  public function getFile();

  /**
   * Updates the download count for this entity.
   *
   * @return \Drupal\track_file_downloads\Entity\FileTrackerInterface
   *   The called entity.
   */
  public function incrementDownloadCount();

  /**
   * Gets the download count.
   *
   * @return int
   *   The number of downloads for the file this entity is tracking.
   */
  public function getDownloadCount(): int;

  /**
   * Updates the last download date.
   *
   * @return \Drupal\track_file_downloads\Entity\FileTrackerInterface
   *   The called entity.
   */
  public function updateLastDownloadedDate();

  /**
   * Gets the last downloaded date of the file this entity is tracking.
   *
   * @return string
   *   The last downloaded date timestamp.
   */
  public function getLastDownloadedDate();

}
