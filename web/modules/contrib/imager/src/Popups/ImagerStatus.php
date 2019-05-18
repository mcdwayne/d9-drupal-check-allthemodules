<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerStatus.
 *
 * @package Drupal\imager
 */
class ImagerStatus implements ImagerPopupsInterface {

  /**
   * Build render array for current Status popup.
   *
   * @return array
   *   Render array for Imager status dialog.
   */
  static public function build(array $config) {
    $id = 'imager-status';
    $content = [
      '#prefix' => '<div id="' . $id . '">',
      '#suffix' => '</div>',
      'content' => [
        '#attributes' => ['id' => ['imager-status-content']],
        '#weight' => 1,
        '#type' => 'container',
        'col_left' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['imager-status-col'],
            'id' => 'imager-status-col-1',
          ],
          'table_general' => [
            '#type' => 'table',
            '#theme' => 'table',
            '#header' => [
              t('Name'),
              t('Value'),
            ],
            '#rows' => [
              [
                'Edit Mode',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-edit-mode',
                ],
              ],
              [
                'Full Screen',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-full-screen',
                ],
              ],
              [
                'Distance',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-distance',
                ],
              ],
              [
                'Elapsed',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-elapsed',
                ],
              ],
              [
                'Zoom',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-zoom',
                ],
              ],
              [
                'Rotation',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-rotation',
                ],
              ],
              [
                'Brightness',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-brightness',
                ],
              ],
              [
                'Contrast',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-contrast',
                ],
              ],
              [
                'Hue',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-hue',
                ],
              ],
              [
                'Saturation',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-saturation',
                ],
              ],
              [
                'Lightness',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-lightness',
                ],
              ],
            ],
          ],
        ],
        'col_right' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['imager-status-col'],
            'id' => 'imager-status-col-2',
          ],
          'table_geometries' => [
            '#type' => 'table',
            '#theme' => 'table',
            '#header' => [
              t('Name'),
              t('Width'),
              t('Height'),
            ],
            '#rows' => [
              [
                'Maximum Canvas',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-max-canvas-width',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-max-canvas-height',
                ],
              ],
              [
                'Actual Canvas',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-actual-canvas-width',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-actual-canvas-height',
                ],
              ],
              [
                'Displayed Image',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-disp-image-width',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-disp-image-height',
                ],
              ],
              [
                'Full Image',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-full-image-width',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-full-image-height',
                ],
              ],
            ],
          ],
          'table_points' => [
            '#type' => 'table',
            '#title' => 'Points',
            '#theme' => 'table',
            '#header' => [
              t('Name'),
              t('X'),
              t('Y'),
            ],
            '#rows' => [
              [
                'Image Offset UL',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-y',
                ],
              ],
              [
                'Image Offset LR',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-y',
                ],
              ],
              [
                'Image Offset UL tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-y-tx',
                ],
              ],
              [
                'Image Offset LR tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-y-tx',
                ],
              ],
              [
                'Upper Left Canvas',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-ul-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-ul-y',
                ],
              ],
              [
                'Lower Right Canvas',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-lr-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-lr-y',
                ],
              ],
              [
                'Upper Left Canvas Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-ul-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-ul-y-tx',
                ],
              ],
              [
                'Lower Right Canvas Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-lr-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-canvas-lr-y-tx',
                ],
              ],
/*            [
                'Image Offset UL',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-y',
                ],
              ],
              [
                'Image Offset LR',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-y',
                ],
              ],
              [
                'Image Offset UL Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-ul-y-tx',
                ],
              ],
              [
                'Image Offset LR Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-image-lr-y-tx',
                ],
              ], */
              [
                'Mouse Now',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-now-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-now-y',
                ],
              ],
              [
                'Mouse Now Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-now-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-now-y-tx',
                ],
              ],
              [
                'Mouse Down',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-down-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-down-y',
                ],
              ],
              [
                'Mouse Down Tx',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-down-x-tx',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-down-y-tx',
                ],
              ],
              [
                'Crop Upper Left',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-crop-ul-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-crop-ul-y',
                ],
              ],
              [
                'Crop Lower Right',
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-crop-lr-x',
                ],
                [
                  'data' => '',
                  'class' => 'imager-text-right',
                  'id' => 'imager-status-crop-lr-y',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    return [
      'content' => $content,
      'buttons' => ['Close'],
      'id' => $id,
    ];
  }

}
