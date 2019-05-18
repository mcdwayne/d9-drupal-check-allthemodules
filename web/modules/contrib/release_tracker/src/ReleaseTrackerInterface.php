<?php

namespace Drupal\release_tracker;

/**
 * Class ReleaseTracker
 *
 * Service class to handle all config changes for release tracker.
 *
 * @package Drupal\release_tracker
 */
interface ReleaseTrackerInterface {

  /**
   * Bumps the release number.
   *
   * @param string $type
   *   The type of release, should be one of major. minor or patch.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an unknown type is passed.
   */
  public function bump($type = 'patch');

  /**
   * Returns the current release string.
   *
   * @return string
   *   The current release string.
   */
  public function getCurrentRelease();

  /**
   * Sets the release number.
   *
   * @param string $release_number
   *   The release number to set.
   */
  public function setReleaseNumber($release_number);
}
