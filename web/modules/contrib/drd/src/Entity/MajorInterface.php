<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Major Version entities.
 *
 * @ingroup drd
 */
interface MajorInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get language code of the major.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Gets the Major Version name.
   *
   * @return string
   *   Name of the Major Version.
   */
  public function getName();

  /**
   * Sets the Major Version name.
   *
   * @param string $name
   *   The Major Version name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the Major Version coreversion.
   *
   * @return int
   *   Core version of the Major Version.
   */
  public function getCoreVersion();

  /**
   * Sets the Major Version coreversion.
   *
   * @param int $coreversion
   *   The Major Version core version.
   *
   * @return $this
   */
  public function setCoreVersion($coreversion);

  /**
   * Gets the Major Version majorversion.
   *
   * @return int
   *   Major version of the Major Version.
   */
  public function getMajorVersion();

  /**
   * Sets the Major Version major version.
   *
   * @param int $majorversion
   *   The Major Version major version.
   *
   * @return $this
   */
  public function setMajorVersion($majorversion);

  /**
   * Gets the Major Version project.
   *
   * @return \Drupal\drd\Entity\ProjectInterface
   *   Project of the Major Version.
   */
  public function getProject();

  /**
   * Sets the Major Version project.
   *
   * @param ProjectInterface $project
   *   The Major Version project.
   *
   * @return $this
   */
  public function setProject(ProjectInterface $project);

  /**
   * Gets the Major Version parent project.
   *
   * @return \Drupal\drd\Entity\ProjectInterface
   *   Parent project of the Major Version.
   */
  public function getParentProject();

  /**
   * Sets the Major Version parent project.
   *
   * @param ProjectInterface $project
   *   The Major Version parent project.
   *
   * @return $this
   */
  public function setParentProject(ProjectInterface $project);

  /**
   * Gets the Major Version recommended release.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface
   *   Recommended release of the Major Version.
   */
  public function getRecommendedRelease();

  /**
   * Sets the Major Version recommended release.
   *
   * @param ReleaseInterface $release
   *   The Major Version recommended release.
   *
   * @return $this
   */
  public function setRecommendedRelease(ReleaseInterface $release);

  /**
   * Gets the Major Version creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Major Version.
   */
  public function getCreatedTime();

  /**
   * Sets the Major Version creation timestamp.
   *
   * @param int $timestamp
   *   The Major Version creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Major Version published status indicator.
   *
   * Unpublished Major Version are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Major Version is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Major Version.
   *
   * @param bool $published
   *   TRUE to set this Major Version to published, FALSE otherwise.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Returns the Major Version hidden status indicator.
   *
   * Hidden Major Version will not be checked for update status.
   *
   * @return bool
   *   TRUE if the Major Version is hidden.
   */
  public function isHidden();

  /**
   * Sets the hidden status of a Major Version.
   *
   * @param bool $hidden
   *   TRUE to set this Major Version to hidden, FALSE otherwise (default).
   *
   * @return $this
   */
  public function setHidden($hidden);

  /**
   * Returns the Major Version supported status indicator.
   *
   * Unsupported Major Version will raise warnings.
   *
   * @return bool
   *   TRUE if the Major Version is supported.
   */
  public function isSupported();

  /**
   * Sets the supported status of a Major Version.
   *
   * @param bool $supported
   *   TRUE to set this Major Version to supported (default), FALSE otherwise.
   *
   * @return $this
   */
  public function setSupported($supported);

  /**
   * Update project status.
   *
   * Collect all update statuses of installed releases of this major and write
   * their aggregated values into this major's db record.
   *
   * @return $this
   */
  public function updateStatus();

  /**
   * Create new or return existing major entity.
   *
   * @param string $type
   *   Project type.
   * @param string $name
   *   Project name.
   * @param string $version
   *   Major version.
   *
   * @return \Drupal\drd\Entity\MajorInterface
   *   The major entity.
   */
  public static function findOrCreate($type, $name, $version);

  /**
   * Find existing major entity.
   *
   * @param string $name
   *   Project name.
   * @param string $version
   *   Major version.
   *
   * @return \Drupal\drd\Entity\MajorInterface|bool
   *   The major entity, or False if not found.
   */
  public static function find($name, $version);

}
