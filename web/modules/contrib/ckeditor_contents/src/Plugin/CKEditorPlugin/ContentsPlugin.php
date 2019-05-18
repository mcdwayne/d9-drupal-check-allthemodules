<?php

/**
 * @file
 * Contains \Drupal\ckeditor_contents\Plugin\CKEditorPlugin\ContentsPlugin.
 */

namespace Drupal\ckeditor_contents\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "contents" plugin.
 *
 * @CKEditorPlugin(
 *   id = "contents",
 *   label = @Translation("Add Table of Contents")
 * )
 */
class ContentsPlugin extends CKEditorPluginBase {


  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Contents' => [
        'label' => t('Add Table of Contents'),
        'image' => 'libraries/contents/icons/contents.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return base_path() . 'libraries/contents/plugin.js';
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
