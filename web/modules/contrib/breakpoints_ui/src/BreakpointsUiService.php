<?php

namespace Drupal\breakpoints_ui;

use Drupal\Component\Serialization\Yaml;

/**
 * Class breakpointsUiService.
 *
 * @package Drupal\breakpoints_ui
 */
class BreakpointsUiService {
  public function getAllBreakpoints(){
    $breakpointsManager = \Drupal::service('breakpoint.manager');
    $getBreakpointsGroups = array_keys($breakpointsManager->getGroups());
    $breakpointInfo = [];
    foreach ($getBreakpointsGroups as $getBreakpointsGroup) {
      $typeExtension = implode(',', array_values($breakpointsManager->getGroupProviders($getBreakpointsGroup)));

      if($typeExtension == 'theme'){
        $projectPath = drupal_get_path('theme', $getBreakpointsGroup);
      }
      if($typeExtension == 'module'){
        $projectPath = drupal_get_path('module', $getBreakpointsGroup);
      }
      $breakPointYML =   Yaml::decode(file_get_contents($projectPath . '/' .  $getBreakpointsGroup . '.breakpoints.yml'));
      $breakpointInfo[$getBreakpointsGroup] = $breakPointYML;
    }
    return $breakpointInfo;
  }
}
