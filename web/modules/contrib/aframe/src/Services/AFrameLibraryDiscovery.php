<?php

/**
 * @file Providing the service that detect aframe library and components.
 */

namespace  Drupal\aframe\Services;

/**
 * Class AFrameLibraryDiscovery.
 *
 * @package Drupal\aframe
 */
class AFrameLibraryDiscovery {

  protected $aframeLibraryPath;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->aframeLibraryPath = DRUPAL_ROOT . base_path() . 'libraries/aframe/dist/';
  }

  /**
   * Function to get the different versions of the aframe library.
   */
  public function aframeScanLibraryVersions() {
    $aframe_versions = [];
    $handle = @opendir($this->aframeLibraryPath) or \Drupal::logger('aframe')->notice("Unable to open " . $this->aframeLibraryPath);
    while ($entry = @readdir($handle)) {
      $matches = [];
      if (is_file($this->aframeLibraryPath . $entry) && preg_match('/^aframe-v(.*)\.min\.js$/i', $entry, $matches)) {
        $aframe_versions[$matches[1]] = base_path() . 'libraries/aframe/dist/' . $entry;
      }
    }
    if ($handle) {
      closedir($handle);
    }

    // Sort by version.
    krsort($aframe_versions);

    return $aframe_versions;
  }

}
