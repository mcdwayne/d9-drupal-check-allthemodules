<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerFilesave.
 *
 * @package Drupal\imager
 */
class ImagerFilesave implements ImagerPopupsInterface {

  /**
   * Build the form to save a file to local file system or back into Drupal.
   *
   * @return array
   *    Render array for filesave dialog
   */
  static public function build(array $config) {
    $id = 'imager-filesave';
    $content = [
      'messages' => [
        '#weight' => 2,
        '#prefix' => '<div id="imager-filesave-messages">',
        '#suffix' => '</div>',
      ],
      'table' => self::buildResolutionTable(3),
      'filename' => [
        '#weight' => 4,
        '#prefix' => '<div id="imager-filesave-filename-container">',
        '#suffix' => '</div>',
        '#type' => 'markup',
        '#markup' => "<span>" . t('File name:') . "</span><input id='imager-filesave-filename' type='text' />",
        '#allowed_tags' => ['span', 'input'],
      ],
    ];
    return [
      'content' => $content,
      'buttons' => ['Cancel', 'Overwrite', 'New image', 'Download image'],
      'id' => $id,
    ];
  }

  /**
   * Build the render table for image resolutions to select when saving images.
   *
   * @param int $weight
   *   Weight determines position within dialog.
   *
   * @return array
   *   Render array for image resolution selection table.
   */
  static private function buildResolutionTable($weight) {
    $build = [
      '#weight' => $weight,
      '#type' => 'table',
      '#attributes' => ['class' => ['table-no-striping']],
      '#theme' => 'table',
      '#header' => [
        '',
        t('Image'),
        t('Resolution'),
        t('Geometry'),
      ],
      '#sticky' => FALSE,
      '#rows' => [
        [
          'no_striping' => TRUE,
          'data' => [
            [
              'data' => [
                '#markup' => '<input type="radio" name="resolution" value="screen" />',
                '#allowed_tags' => ['input'],
              ],
            ],
            t('Displayed'),
            t('Screen'),
            ['id' => 'canvas-resolution'],
          ],
        ],
        [
          'no_striping' => TRUE,
          'data' => [
            [
              'data' => [
                '#markup' => '<input type="radio" name="resolution" value="image-cropped" checked="checked" />',
                '#allowed_tags' => ['input'],
              ],
            ],
            t('Displayed'),
            t('Image'),
            ['id' => 'image-display-resolution'],
          ],
        ],
        [
          'no_striping' => TRUE,
          'data' => [
            [
              'data' => [
                '#markup' => '<input type="radio" name="resolution" value="image-full" />',
                '#allowed_tags' => ['input'],
              ],
            ],
            t('Full'),
            t('Image'),
            ['id' => 'image-full-resolution'],
          ],
        ],
      ],
    ];
    return $build;
  }


}
