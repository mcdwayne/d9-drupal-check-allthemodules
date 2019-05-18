<?php

namespace Drupal\gridstack_example;

use Drupal\gridstack\GridStackSkinInterface;

/**
 * Implements GridStackSkinInterface via hook_gridstack_skins_info().
 */
class GridStackExampleSkin implements GridStackSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $path = base_path() . drupal_get_path('module', 'gridstack_example');

    $skins = [
      'zoe' => [
        'name' => 'X: Zoe',
        'provider' => 'gridstack_example',
        'css' => [
          'theme' => [
            $path . '/css/gridstack.theme--zoe.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
