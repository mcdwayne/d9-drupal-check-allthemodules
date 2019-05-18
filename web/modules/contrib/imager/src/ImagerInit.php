<?php

namespace Drupal\imager;

/**
 * Class ImagerInit.
 *
 * @package Drupal\imager
 */
class ImagerInit {

  /**
   * Create render array to attach necessary libraries and settings.
   *
   * @param array $config
   *   Configuration array.  Not used.
   *
   * @return array
   *   Render array.
   */
  static public function start(array $config) {
    $build = array(
      '#attached' => array(
        'drupalSettings' => array(
          'imager' => array(
            'objects' => 'space to pass something',
          ),
        ),
        'library' => [
          'imager/imager-base',
          'imager/imager-editor',
        ],
      ),
    );
    return $build;
  }

}
