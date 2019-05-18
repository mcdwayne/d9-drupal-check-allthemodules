<?php

namespace Drupal\gridstack;

/**
 * Implements GridStackSkinInterface.
 */
class GridStackSkin implements GridStackSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $skins = [
      'default' => [
        'name' => 'Default',
        'provider' => 'gridstack',
        'css' => [
          'theme' => [
            'css/theme/gridstack.theme--default.css' => [],
          ],
        ],
      ],
      'selena' => [
        'name' => 'Selena',
        'provider' => 'gridstack',
        'css' => [
          'theme' => [
            'css/theme/gridstack.theme--selena.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
