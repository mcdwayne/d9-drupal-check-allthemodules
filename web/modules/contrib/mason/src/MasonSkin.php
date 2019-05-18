<?php

namespace Drupal\mason;

/**
 * Implements MasonSkinInterface.
 */
class MasonSkin implements MasonSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $skins = [
      'default' => [
        'name' => 'Default',
        'provider' => 'mason',
        'css' => [
          'theme' => [
            'css/theme/mason.theme--default.css' => [],
          ],
        ],
      ],
      'selena' => [
        'name' => 'Selena',
        'provider' => 'mason',
        'css' => [
          'theme' => [
            'css/theme/mason.theme--selena.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
