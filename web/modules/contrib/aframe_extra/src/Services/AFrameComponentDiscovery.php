<?php

/**
 * @file Providing the service that detect aframe components.
 */

namespace  Drupal\aframe_extra\Services;

/**
 * Class AFrameCompnentDiscovery.
 *
 * @package Drupal\aframe_extra
 */
class AFrameComponentDiscovery {

  protected $aframeComponentsPath;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->aframeComponentsPath = DRUPAL_ROOT . base_path() . 'libraries/aframecomponent/';
  }

  /**
   * Function to get the different versions of the aframe library.
   */
  public function aframeScanComponents() {
    $aframe_components = [];
    $handle = @opendir($this->aframeComponentsPath) or \Drupal::logger('aframe')->notice("Unable to open " . $this->aframeComponentsPath);
    while ($entry = @readdir($handle)) {
      if (!in_array($entry, ['.', '..'])) {
        $aframe_components[] = $entry;
      }
    }
    closedir($handle);

    return $aframe_components;
  }

}
