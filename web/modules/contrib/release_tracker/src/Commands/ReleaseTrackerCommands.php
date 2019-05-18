<?php

namespace Drupal\release_tracker\Commands;

use Drupal\release_tracker\ReleaseTrackerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the release_tracker module.
 */
class ReleaseTrackerCommands extends DrushCommands {

  /**
   * @var ReleaseTrackerInterface
   */
  protected $releaseTracker;

  /**
   * ReleaseTrackerCommands constructor.
   *
   * @param ReleaseTrackerInterface $release_tracker
   */
  public function __construct(ReleaseTrackerInterface $release_tracker) {
    $this->releaseTracker = $release_tracker;
  }

  /**
   * Bumps the release number.
   *
   * @command release:tracker:bump
   *
   * @param string $type
   *   The type of release, should be one of major. minor or patch.
   *
   * @throws \Exception
   *   Thrown when an unknown type is passed.
   *
   * @usage drush rtb minor
   *   Bump the release one minor version, ie. 8.3.1 to 8.4.0
   *
   * @aliases rtb,release-tracker-bump
   */
  public function trackerBump($type = 'patch') {
    $this->releaseTracker->bump($type);
    $current_release = $this->releaseTracker->getCurrentRelease();
    $this->logger()->info('Release set to ' . $current_release);
    $this->output()->writeln('Release set to ' . $current_release);
  }

  /**
   * Outputs the current release number.
   *
   * @command release:tracker:current
   *
   * @usage drush rtc
   *
   * @aliases rtc,release-tracker-current
   */
  public function trackerCurrent() {
    $this->output()->writeln('Release currently set to ' . $this->releaseTracker->getCurrentRelease());
  }

  /**
   * Sets the release number.
   *
   * @command release:tracker:set
   *
   * @param string $number
   *   Release number to set, must contain a major, minor and patch number
   *   separated by a period, for instance 1.2.3
   *
   * @throws \Exception
   *   Thrown when an invalid number is passed.
   *
   * @usage drush release:tracker:set 3.2.1
   *   Set the release number
   *
   * @aliases rts,release-tracker-set
   */
  public function trackerSetCurrent($number) {
    $this->releaseTracker->setReleaseNumber($number);
    $current_release = $this->releaseTracker->getCurrentRelease();
    $this->logger()->info('Release set to ' . $current_release);
    $this->output()->writeln('Release set to ' . $current_release);
  }
}
