<?php

namespace Drupal\imager\Popups;

use Drupal\imager\Popups\ImagerPopupsInterface;

/**
 * Class ImagerInfo.
 *
 * @package Drupal\imager
 */
class ImagerInfo implements ImagerPopupsInterface {

  /**
   * Build render array for information dialog - displays rendered file_entity.
   *
   * @return array
   *   Render array for Information dialog.
   */
  static public function build(array $config) {
    $id = 'imager-info';
    $content = [
      '#prefix' => '<div id="imager-info">',
      '#suffix' => '</div>',
      'content' => [
        '#prefix' => '<div id="imager-info-content" class="imager-content">',
        '#suffix' => '</div>',
        '#weight' => 1,
        '#type' => 'markup',
        '#markup' => t('Placeholder for information popup'),
      ],
    ];
    return [
      'content' => $content,
      'buttons' => ['Close'],
      'id' => $id,
    ];
  }

}
