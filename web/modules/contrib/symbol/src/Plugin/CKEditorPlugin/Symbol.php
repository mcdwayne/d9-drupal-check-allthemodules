<?php

namespace Drupal\symbol\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "symbol" plugin.
 *
 * @CKEditorPlugin(
 *   id = "symbol",
 *   label = @Translation("Symbol")
 * )
 */
class Symbol extends CKEditorPluginBase implements CKEditorPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/symbol/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Symbol' => array(
        'label' => $this->t('Symbol'),
        'image' => 'libraries/symbol/icons/symbol.png',
      ),
    ];
  }
}