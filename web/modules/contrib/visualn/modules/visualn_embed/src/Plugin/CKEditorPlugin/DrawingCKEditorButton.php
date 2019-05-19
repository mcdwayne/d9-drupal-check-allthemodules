<?php

namespace Drupal\visualn_embed\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
// @todo: see Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalImageCaption
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "drupalvisualndrawing" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "drupalvisualndrawing",
 *   label = @Translation("Drawing ckeditor button")
 * )
 */
class DrawingCKEditorButton extends CKEditorPluginBase implements CKEditorPluginCssInterface {
  // @todo: or extend EmbedCKEditorPluginBase class


  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    // Make sure that the path to the image matches the file structure of
    // the CKEditor plugin you are implementing.
    return [
      'Visualn-drawing-ckeditor-button' => [
        'label' => t('Drawing ckeditor button'),
        'image' => drupal_get_path('module', 'visualn_embed') . '/js/plugins/drupalvisualndrawing/images/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return drupal_get_path('module', 'visualn_embed') . '/js/plugins/drupalvisualndrawing/plugin.js';
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
    return [
      'embed/embed',
      'visualn_embed/ckeditor-styles',
      // @todo: this is required for some actions in ckeditor widget context menu (preview etc.)
      //'core/drupal.ajax',
      'core/drupal.dialog.ajax',
    ];
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
  public function getCssFiles(Editor $editor) {
    return [
      drupal_get_path('module', 'visualn_embed') . '/css/ckeditor-styles.css'
    ];
  }

}
