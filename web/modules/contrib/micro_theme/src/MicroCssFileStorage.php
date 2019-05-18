<?php

namespace Drupal\micro_theme;

/**
 * Class CssFileStorage.
 *
 * @package Drupal\micro_theme
 */
final class MicroCssFileStorage extends MicroAssetFileStorage {

  /**
   * {@inheritdoc}
   */
  public function extension() {
    return 'css';
  }

}
