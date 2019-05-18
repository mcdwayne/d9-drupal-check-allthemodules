<?php

namespace Drupal\mason_example;

use Drupal\mason\MasonSkinInterface;

/**
 * Implements MasonSkinInterface as registered via hook_mason_skins_info().
 */
class MasonExampleSkin implements MasonSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $path  = base_path() . drupal_get_path('module', 'mason_example');
    $skins = [
      'zoe' => [
        'name' => 'X: Zoe',
        'provider' => 'mason_example',
        'css' => [
          'theme' => [
            $path . '/css/mason.theme--zoe.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
