<?php

namespace Drupal\release_tracker;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ReleaseTracker
 *
 * Service class to handle all config changes for release tracker.
 *
 * @package Drupal\release_tracker
 */
class ReleaseTracker implements ReleaseTrackerInterface {

  /**
   * The editable config for release tracker.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor for the ReleaseTracker class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('release_tracker.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function bump($type = 'patch') {
    if (!in_array($type, ['major', 'minor', 'patch'])) {
      throw new \InvalidArgumentException("Type must be one of 'major', 'minor' or 'patch'.");
    }
    $current_release_parts = explode('.', $this->getCurrentRelease());
    $current_release = [
      'major' => $current_release_parts[0],
      'minor' => $current_release_parts[1],
      'patch' => $current_release_parts[2],
    ];

    // Bumped the requested type by 1 and reset all following types to 0.
    $bumped = FALSE;
    foreach ($current_release as $key => &$item) {
      if ($key === $type) {
        $item++;
        $bumped = TRUE;
      }
      elseif ($bumped) {
        $item = 0;
      }
    }

    $this->config->set('release', implode('.', $current_release));
    $this->config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRelease() {
    return $current_release_string = $this->config->get('release');
  }


  /**
   * {@inheritdoc}
   */
  public function setReleaseNumber($release_number) {
    if (!$this->validateReleaseNumber($release_number)) {
      throw new \InvalidArgumentException("Invalid release number given.");
    }
    $this->config->set('release', $release_number);
    $this->config->save();
  }

  /**
   * Validates the passed number as a valid release number.
   *
   * @param string $number
   *   The number to validate.
   *
   * @return bool
   *   Returns TRUE if the passed number is a valid release number, FALSE
   *   otherwise.
   */
  protected function validateReleaseNumber($number) {
    if (preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $number)) {
      return TRUE;
    }
    return FALSE;
  }

}
