<?php

namespace Drupal\drd\Entity;

/**
 * Provides an interface for defining Core entities.
 *
 * @ingroup drd
 */
interface CoreInterface extends BaseInterface {

  /**
   * Get language code of the core.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Get Drupal root directory of the core.
   *
   * @return string
   *   The Drupal root directory.
   */
  public function getDrupalRoot();

  /**
   * Gets the Core host.
   *
   * @return \Drupal\drd\Entity\HostInterface
   *   Host of the Core.
   */
  public function getHost();

  /**
   * Sets the Core drupal release.
   *
   * @param ReleaseInterface $release
   *   The Core drupal release.
   *
   * @return $this
   */
  public function setDrupalRelease(ReleaseInterface $release);

  /**
   * Gets the Core drupal release.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface
   *   Drupal release of the Core.
   */
  public function getDrupalRelease();

  /**
   * Get the update settings for the core.
   *
   * @return array
   *   The update settings.
   */
  public function getUpdateSettings();

  /**
   * Get the update plugin for the core.
   *
   * @return \Drupal\drd\Update\PluginStorageInterface
   *   The update plugin.
   */
  public function getUpdatePlugin();

  /**
   * Sets the Core host.
   *
   * @param HostInterface $host
   *   The Core host.
   *
   * @return $this
   */
  public function setHost(HostInterface $host);

  /**
   * Get all domains of the core.
   *
   * @param array $properties
   *   Properties for the query.
   *
   * @return \Drupal\drd\Entity\DomainInterface[]
   *   List of domains of this core.
   */
  public function getDomains(array $properties = []);

  /**
   * Get the first active domain for this core.
   *
   * @return \Drupal\drd\Entity\DomainInterface
   *   A domain entity.
   */
  public function getFirstActiveDomain();

  /**
   * Get all releases for which updates are available.
   *
   * @param bool $includeLocked
   *   Whether to include locked releases or not.
   * @param bool $securityOnly
   *   Whether to only include security releases or not.
   * @param bool $forceLockedSecurity
   *   Whether to locked security releases or not.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface[]
   *   List of release entities.
   */
  public function getAvailableUpdates($includeLocked = FALSE, $securityOnly = FALSE, $forceLockedSecurity = FALSE);

  /**
   * Get list of update logs.
   *
   * @return array
   *   List of update logs.
   */
  public function getUpdateLogList();

  /**
   * Get update log from a specific timestamp.
   *
   * @param int $timestamp
   *   Timestamp to select which update log to return.
   *
   * @return string
   *   The update log.
   */
  public function getUpdateLog($timestamp);

  /**
   * Save a collected  update log.
   *
   * @param string $log
   *   The update log.
   *
   * @return $this
   */
  public function saveUpdateLog($log);

  /**
   * Set project releases being locked for this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface[] $releases
   *   List of locked release entities.
   *
   * @return $this
   */
  public function setLockedReleases(array $releases);

  /**
   * Get project releases being locked by this core.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface[]
   *   List of releases.
   */
  public function getLockedReleases();

  /**
   * Check if a release is locked for this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release to ckeck.
   * @param bool $checkGlobal
   *   Set to True if you also want to check global lock status.
   *
   * @return bool
   *   True if release is lock, False otherwise.
   */
  public function isReleaseLocked(ReleaseInterface $release, $checkGlobal = FALSE);

  /**
   * Lock a release for this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release which should be locked.
   *
   * @return $this
   */
  public function lockRelease(ReleaseInterface $release);

  /**
   * Unlock a release for this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release which should be unlocked.
   *
   * @return $this
   */
  public function unlockRelease(ReleaseInterface $release);

  /**
   * Unlock all releases for this core.
   *
   * @return $this
   */
  public function unlockAllReleases();

  /**
   * Set project releases being hacked on this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface[] $releases
   *   List of hacked release entities.
   *
   * @return $this
   */
  public function setHackedReleases(array $releases);

  /**
   * Get project releases being hacked on this core.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface[]
   *   List of releases.
   */
  public function getHackedReleases();

  /**
   * Check if a release is hacked on this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release to ckeck.
   *
   * @return bool
   *   True if release is lock, False otherwise.
   */
  public function isReleaseHacked(ReleaseInterface $release);

  /**
   * Mark a release as hacked on this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release which should be marked as hacked.
   *
   * @return $this
   */
  public function markReleaseHacked(ReleaseInterface $release);

  /**
   * Mark a release as unhacked on this core.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release which should be marked as unhacked.
   *
   * @return $this
   */
  public function markReleaseUnhacked(ReleaseInterface $release);

}
