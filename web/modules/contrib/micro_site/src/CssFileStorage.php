<?php

namespace Drupal\micro_site;

/**
 * Class CssFileStorage.
 *
 * @package Drupal\micro_site
 */
final class CssFileStorage extends AssetFileStorage {

  /**
   * {@inheritdoc}
   */
  public function extension() {
    return 'css';
  }

}
