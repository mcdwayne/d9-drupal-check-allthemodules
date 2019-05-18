<?php

namespace Drupal\icon\Plugin\Icon;

/**
 * Drupal Core Icon Set.
 *
 * @IconSet(
 *   id = "core",
 *   label = @Translation("Core Icons"),
 *   icons = {},
 *   provider = "drupal",
 *   url = "",
 *   version = "",
 *   path = "",
 *   renderer = "image",
 *   settings = {},
 *   attached = {},
 * )
 */
class CoreSet extends IconSetBase {

  /**
   * {@inheritdoc}
   */
  public function process() {

    $mask = '/.svg/';
    $svg_icons = file_scan_directory(DRUPAL_ROOT . '/core/misc/icons', $mask, ['recurse' => TRUE]);
    foreach ($svg_icons as $icon_id => $icon_config) {
      $icon = [
        'id' => $icon_config->name,
        'filename' => $icon_config->filename,
        'path' =>  $icon_config->uri,
      ];
      $this->setIcon($icon, $icon_config->name);
    }
  }

}
