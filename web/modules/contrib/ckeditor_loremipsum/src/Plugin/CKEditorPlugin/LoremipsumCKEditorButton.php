<?php

/**
 * @file
 * Contains \Drupal\ckeditor_loremipsum\Plugin\CKEditorPlugin\LoremipsumCKEditorButton.
 */

namespace Drupal\ckeditor_loremipsum\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "loremipsum" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "loremipsum",
 *   label = @Translation("Loremipsum ckeditor button")
 * )
 */
class LoremipsumCKEditorButton extends CKEditorPluginBase {

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
    $path = 'libraries/loremipsum';
    return array(
      'Loremipsum' => array(
        'label' => t('Loremipsum ckeditor button'),
        'image' => $path . '/icons/loremipsum.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
      return 'libraries/loremipsum/plugin.js';
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
