<?php

namespace Drupal\ckeditor_content_style\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Content Style" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckcs",
 *   label = @Translation("Content Style"),
 *   module = "ckeditor_content_style"
 * )
 */
class StyleContent extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {

    $file_path = drupal_get_path('module', 'ckeditor_content_style') . '/plugins/ckcs/plugin.js';
    return $file_path;

  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
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
      'ckcs' => [
        'label' => t('Content Style Guide'),
        'image' => drupal_get_path('module', 'ckeditor_content_style') . '/plugins/ckcs/icons/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
