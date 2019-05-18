<?php

namespace Drupal\imager\Popups;

use Drupal\imager\ImagerComponents;
use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerViewer.
 *
 * @package Drupal\imager
 */
class ImagerViewer implements ImagerPopupsInterface{

  /**
   * Build render array for the imager viewer.
   *
   * @param array $config
   *   Array to configure popup.
   *
   * @return array
   *   Render array for the Viewer popup.
   */
  static public function build(array $config) {
    $id = 'imager-viewer';
    $content = [
      'button_wrapper' => [
        '#prefix' => '<div id="button-wrapper">',
        '#suffix' => '</div>',
        '#weight' => 1,
        'image_buttons' => [
          '#prefix' => '<div id="image-buttons" class="imager-buttons">',
          '#suffix' => '</div>',
          '#weight' => 1,
          'title' => [
            '#type' => 'markup',
            '#markup' => t('Image'),
            '#prefix' => '<div>',
            '#suffix' => '</div>',
            '#weight' => 0,
          ],
          'image_exit' => ImagerComponents::buildButton(1, 'image-exit', 'close.png', t('Exit image popup')),
          'image_left' => ImagerComponents::buildButton(3, 'image-left', 'left_arrow.png', t('View image to the left')),
          'image_right' => ImagerComponents::buildButton(4, 'image-right', 'right_arrow.png', t('View image to the right')),
        ],
        'view_buttons' => [
          '#prefix' => '<div id="view-buttons" class="imager-buttons">',
          '#suffix' => '</div>',
          '#weight' => 3,
          'title' => [
            '#type' => 'markup',
            '#markup' => t('View'),
            '#prefix' => '<div>',
            '#suffix' => '</div>',
            '#weight' => 0,
          ],
          'mode_fullscreen' => ImagerComponents::buildButton(2, 'mode-fullscreen', 'fullscreen.png', t('View image full screen')),
          'view_browser' => ImagerComponents::buildButton(1, 'view-browser', 'newtab.png', t('View image in new tab, useful when printing'), TRUE),
          'view_info' => ImagerComponents::buildButton(2, 'view-info', 'information.png', t('View Image information')),
          'view_slideshow' => ImagerComponents::buildButton(2, 'view-slideshow', 'slideshow.png', t('View images in slideshow')),
          'view_zoom_in' => ImagerComponents::buildButton(5, 'view-zoom-in', 'zoomin.png', t('Zoom into the image')),
          'view_zoom_out' => ImagerComponents::buildButton(6, 'view-zoom-out', 'zoomout.png', t('Zoom out of the image')),
        ],
        'edit_buttons' => [
          '#prefix' => '<div id="edit-buttons" class="imager-buttons">',
          '#suffix' => '</div>',
          '#weight' => 4,
          'title' => [
            '#type' => 'markup',
            '#markup' => t('Edit'),
            '#prefix' => '<div>',
            '#suffix' => '</div>',
          ],
          'mode_crop' => ImagerComponents::buildButton(1, 'mode-crop', 'frame.png', t('Start crop - select area to crop')),
          'edit_crop' => ImagerComponents::buildButton(2, 'edit-crop', 'scissors.png', t('Crop selected area')),
          'edit_brightness' => ImagerComponents::buildButton(3, 'edit-brightness', 'contrast.png', t('Edit brightness and contrast')),
          'edit_color' => ImagerComponents::buildButton(4, 'edit-color', 'color_wheel.png', t('Edit hue, saturation and lightness')),
          'edit_ccw' => ImagerComponents::buildButton(5, 'edit-ccw', 'rotate-left.png', t('Rotate image 90 degrees counter-clockwise')),
          'edit_cw' => ImagerComponents::buildButton(6, 'edit-cw', 'rotate-right.png', t('Rotate image 90 degrees clockwise')),
          'view_reset' => ImagerComponents::buildButton(7, 'view-reset', 'reset.png', t('Reset the image back to the original')),
        ],
        'file_buttons' => [
          '#prefix' => '<div id="file-buttons" class="imager-buttons">',
          '#suffix' => '</div>',
          '#weight' => 5,
          'title' => [
            '#type' => 'markup',
            '#markup' => t('File'),
            '#prefix' => '<div>',
            '#suffix' => '</div>',
          ],
          // @TODO don't display if doesn't have permissions.
          'file_save' => ImagerComponents::buildButton(1, 'file-save',
            'database_go.png', t('Save edited image to database')),
          'file_download' => ImagerComponents::buildButton(3, 'file-download',
            'download.png', t('Download image to local file system'), TRUE),
          // @TODO Don't display if not at appropriate IP addresses.
          'mode_configure' => ImagerComponents::buildButton(4, 'mode-configure',
            'configure.png', t('Configure settings')),
          'help' => ImagerComponents::buildButton(4, 'imager-help',
            'help.png', t('Display Imager help')),
        ],
        'debug_buttons' => [
          '#prefix' => '<div id="debug-buttons" class="imager-buttons">',
          '#suffix' => '</div>',
          '#weight' => -5,
          'debug_status' => ImagerComponents::buildButton(1, 'debug-status',
            'bug.png', t('Toggle status output')),
        ],
      ],
      'imager_canvas_wrapper' => [
        '#prefix' => '<div id="imager-canvas-wrapper">',
        '#suffix' => '</div>',
        '#weight' => 2,
        'imager_canvas' => [
          '#weight' => 1,
          '#markup' => '<canvas id="imager-canvas"></canvas>',
          '#allowed_tags' => ['canvas'],
        ],
        'imager_image' => [
          '#type' => 'markup',
          '#weight' => 2,
          '#markup' => '<img id="imager-image" src="' .
            $GLOBALS["base_url"] . '/' . drupal_get_path('module', 'imager') . '/icons/transparent.png' .
            '" alt="" title="" />',
        ],
        'imager_canvas2' => [
          '#weight' => 3,
          '#markup' => '<canvas id="imager-canvas2"></canvas>',
          '#allowed_tags' => ['canvas'],
        ],
      ],
    ];
    return [
      'content' => $content,
      'id' => $id,
    ];
  }

}
