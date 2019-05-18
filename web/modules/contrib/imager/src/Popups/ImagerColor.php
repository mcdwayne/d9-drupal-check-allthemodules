<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerColor.
 *
 * @package Drupal\imager
 */
class ImagerColor implements ImagerPopupsInterface {

  /**
   * Build render array for HSL slider popup.
   *
   * @return array
   *   Render array for Hue/Saturation/Lightness dialog.
   */
  static public function build(array $config) {
    $id = 'imager-color';
    $content = [
      '#theme' => 'table',
      '#attributes' => ['class' => 'table-no-striping'],
      '#rows' => [
        [
          'no_striping' => TRUE,
          'data' => [
            t('Hue'),
            [
              'data' => [
                '#markup' => '<input id="slider-hue" class="imager-slider" type="range" min="-100" max="100" step="1" />',
                '#allowed_tags' => ['input'],
              ],
            ],
          ],
        ],
        [
          'no_striping' => TRUE,
          'data' => [
            t('Saturation'),
            [
              'data' => [
                '#markup' => '<input id="slider-saturation" class="imager-slider" type="range" min="-100" max="100" step="1" />',
                '#allowed_tags' => ['input'],
              ],
            ],
          ],
        ],
        [
          'no_striping' => TRUE,
          'data' => [
            t('Lightness'),
            [
              'data' => [
                '#markup' => '<input id="slider-lightness"  class="imager-slider" type="range" min="-100" max="100" step="1" />',
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
