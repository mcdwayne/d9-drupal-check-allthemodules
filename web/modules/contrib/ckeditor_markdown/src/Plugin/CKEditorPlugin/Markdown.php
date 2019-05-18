<?php

namespace Drupal\ckeditor_markdown\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "markdown" plugin.
 *
 * @CKEditorPlugin(
 *   id = "markdown",
 *   label = @Translation("Markdown")
 * )
 */
class Markdown extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ckeditor_markdown') . '/js/plugins/markdown';
    return [
      'Markdown' => [
        'label' => t('Markdown'),
        'image' => $path . '/icons/markdown.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_markdown') . '/js/plugins/markdown/plugin.js';
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
  public function getConfig(Editor $editor) {
    return [];
  }

}
