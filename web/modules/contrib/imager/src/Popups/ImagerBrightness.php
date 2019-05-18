<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerBrightness.
 *
 * @package Drupal\imager
 */
class ImagerBrightness implements ImagerPopupsInterface {

  /**
   * Build render array for brightness/contrast slidebar popup.
   *
   * @return array
   *   Render array for brightness/contrast slider.
   */
  static public function build(array $config) {
    $id = 'imager-brightness';
    $content = [
      '#theme' => 'table',
      '#attributes' => ['class' => 'table-no-striping'],
      '#rows' => [
        [
          'no_striping' => TRUE,
          'data' => [
            t('Brightness'),
            [
              'data' => [
                '#markup' => '<input id="slider-brightness" class="imager-slider" type="range" min="-100" max="100" step="1" />',
                '#allowed_tags' => ['input'],
              ],
            ],
          ],
        ],
        [
          'no_striping' => TRUE,
          'data' => [
            t('Contrast'),
            [
              'data' => [
                '#markup' => '<input id="slider-contrast"   class="imager-slider" type="range" min="-100" max="100" step="1" />',
                '#allowed_tags' => ['input'],
              ],
            ],
          ],
        ],
      ],
    ];
    return [
      'content' => $content,
      'buttons' => ['Cancel', 'Reset', 'Apply'],
      'id' => $id,
    ];
  }

}
