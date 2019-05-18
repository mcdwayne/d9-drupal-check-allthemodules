<?php

namespace Drupal\imager\Popups;

use Drupal\imager\ImagerComponents;
use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerConfig.
 *
 * @package Drupal\imager
 */
class ImagerConfig implements ImagerPopupsInterface {

  /**
   * Build render array for configuration dialog.
   *
   * @return array
   *    Render array for configuration dialog.
   */
  static public function build(array $config) {
    $id = 'imager-config';
    $content = [
      '#weight' => 1,
      'viewer' => [
        '#type' => 'fieldset',
        '#title' => 'Image Viewer',
        'imager_bounds_enable' => [
          '#type' => 'checkbox',
          '#title' => t('Enable Bounds limiting'),
          '#description' => t('Prevents image from zooming smaller than the viewing area and from being panned (dragged) offscreen.'),
          '#attributes' => ['id' => 'imager-bounds-enable'],
        ],
      ],
      'slideshow' => [
        '#type' => 'fieldset',
        '#title' => 'Slideshow',
        'imager_slideshow_interval' => [
          '#type' => 'number',
          '#title' => t('Interval'),
          '#min' => 0,
          '#max' => 60,
          '#default_value' => '93',
          '#description' => t('Number of seconds between image changes.'),
          '#attributes' => ['id' => 'imager-slideshow-interval'],
        ],
      ],
      'debug' => [
        '#type' => 'fieldset',
        '#title' => 'Debug',
        'imager_debug_status' => [
          '#type' => 'checkbox',
          '#title' => t('Display Status'),
          '#description' => t('Display current state of variables.'),
          '#attributes' => ['id' => 'imager-debug-status'],
        ],
        'imager_debug_messages' => [
          '#title' => t('Display Messages'),
          '#description' => t('Display messages involving AJAX communications and debug messages.'),
          '#attributes' => ['id' => 'imager-debug-messages'],
        ],
      ],
    ];
    return [
      'content' => $content,
      'buttons' => ['Cancel', 'Apply'],
      'id' => $id,
    ];
  }

}
