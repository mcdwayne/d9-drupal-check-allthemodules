<?php

namespace Drupal\ckeditor_frame\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "CKEditorFrame" plugin.
 *
 * @CKEditorPlugin (
 *   id = "frame",
 *   label = @Translation("CKEditorFrame"),
 *   module = "ckeditor_frame"
 * )
 */
class CKEditorFrame extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = [];
    return $config;
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
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_frame') . '/js/plugins/frame/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ckeditor_frame') . '/js/plugins/frame/icons';
    return [
      'Frame' => [
        'label' => $this->t('Add frame'),
        'image' => $path . '/icon.png',
      ],
    ];
  }

}
