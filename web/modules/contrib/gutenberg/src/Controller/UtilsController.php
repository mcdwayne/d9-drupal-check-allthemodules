<?php

namespace Drupal\gutenberg\Controller;

use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Controller\ControllerBase;

/**
 * Utility controller.
 */
class UtilsController extends ControllerBase {
  public static function getBlocksSettings() {
    $settings = &drupal_static(__FUNCTION__);

    if(!isset($settings)) {
      $module_handler = \Drupal::service('module_handler');
      $path = $module_handler->getModule('gutenberg')->getPath();
  
      $file_path = DRUPAL_ROOT . '/' . $path . '/' . 'gutenberg.blocks.yml';
      if (file_exists($file_path)) {
        $file_contents = file_get_contents($file_path);
        $settings = Yaml::parse($file_contents);
      }  
    }
  
    return $settings;
  }
}
