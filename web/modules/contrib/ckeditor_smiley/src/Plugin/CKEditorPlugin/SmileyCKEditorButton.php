<?php

/**
 * @file
 * Contains \Drupal\ckeditor_smiley\Plugin\CKEditorPlugin\SmileyCKEditorButton.
 */

namespace Drupal\ckeditor_smiley\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "smiley" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "smiley",
 *   label = @Translation("Smiley ckeditor button")
 * )
 */
class SmileyCKEditorButton extends CKEditorPluginBase {

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
    return array(
      'Smiley' => array(
        'label' => t('Smiley ckeditor button'),
        'image' => 'libraries/smiley/icons/smiley.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
      return 'libraries/smiley/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
