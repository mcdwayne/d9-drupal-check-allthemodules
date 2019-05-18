<?php

namespace Drupal\eqneditor\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "eqneditor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "eqneditor",
 *   label = @Translation("Equation buttons"),
 *   module = "eqneditor"
 * )
 */
class Equationbutton extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return base_path() . 'libraries/eqneditor/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'EqnEditor' => [
        'label' => t('Equation Button'),
        'image' => base_path() . 'libraries/eqneditor/icons/eqneditor.png',
      ],
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }
}
