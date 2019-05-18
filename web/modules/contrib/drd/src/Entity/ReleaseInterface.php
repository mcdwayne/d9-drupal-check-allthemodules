<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Release entities.
 *
 * @ingroup drd
 */
interface ReleaseInterface extends UpdateStatusInterface, ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get language code of the release.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Get or set a flag whether this release has just been created.
   *
   * @param bool|null $flag
   *   The flag to set or NULL to receive the current setting.
   *
   * @return bool
   *   The current flag.
   */
  public function isJustCreated($flag = NULL);

  /**
   * Get or set a flag whether this release has just been created.
   *
   * @return bool
   *   The current flag.
   */
  public function isUnsupported();

  /**
   * Get or set a flag whether this release has just been created.
   *
   * @return bool
   *   The current flag.
   */
  public function isSecurityRelevant();

  /**
   * Gets the Release's update status.
   *
   * @return string
   *   Update status of the Release.
   */
  public function getUpdateStatus();

  /**
   * Gets the Release version.
   *
   * @return string
   *   Version of the Release.
   */
  public function getVersion();

  /**
   * Gets the Release version without the optional leadin "8.x-".
   *
   * @return string
   *   Version of the Release.
   */
  public function getReleaseVersion();

  /**
   * Sets the Release version.
   *
   * @param string $version
   *   The Release version.
   *
   * @return $this
   */
  public function setVersion($version);

  /**
   * Gets the Release Major Version.
   *
   * @return \Drupal\drd\Entity\MajorInterface
   *   Major Version of the Release.
   */
  public function getMajor();

  /**
   * Sets the Release Major Version.
   *
   * @param MajorInterface $major
   *   The Release Major Version.
   *
   * @return $this
   */
  public function setMajor(MajorInterface $major);

  /**
   * Gets the Release creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Release.
   */
  public function getCreatedTime();

  /**
   * Sets the Release creation timestamp.
   *
   * @param int $timestamp
   *   The Release creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Release published status indicator.
   *
   * Unpublished Release are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Release is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Release.
   *
   * @param bool $published
   *   TRUE to set this Release to published, FALSE to set it to unpublished.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Returns the Release locked status indicator.
   *
   * A Locked Release will not be updated.
   *
   * @return bool
   *   TRUE if the Release is locked.
   */
  public function isLocked();

  /**
   * Sets the locked status of a Release.
   *
   * @param bool $locked
   *   TRUE to lock this Release, FALSE to unlock it.
   *
   * @return $this
   */
  public function setLocked($locked);

  /**
   * Create new or return existing release entity.
   *
   * @param string $type
   *   The project type.
   * @param string $name
   *   The project name.
   * @param string $version
   *   The release version.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface
   *   The release entity.
   */
  public static function findOrCreate($type, $name, $version);

  /**
   * Find existing release entity.
   *
   * @param string $name
   *   The project name.
   * @param string $version
   *   The release version.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface|bool
   *   The release entity, or False if not found.
   */
  public static function find($name, $version);

  /**
   * Get the project type.
   *
   * @return string
   *   The project type.
   */
  public function getProjectType();

  /**
   * Get the url that points to the project on drupal.org.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getProjectLink();

  /**
   * Get the url that points to the release on drupal.org.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getReleaseLink();

  /**
   * Get the url that points to the download on drupal.org.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getDownloadLink();

}
