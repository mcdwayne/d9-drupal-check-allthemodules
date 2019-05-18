<?php

/**
 * @file
 * Contains \Drupal\ckeditor_videodetector\Plugin\CKEditorPlugin\VideoDetectorCKEditorButton.
 */

namespace Drupal\ckeditor_videodetector\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "videodetector" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "videodetector",
 *   label = @Translation("Video detector ckeditor button")
 * )
 */
class VideoDetectorCKEditorButton extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    return array(
      'VideoDetector' => array(
        'label' => t('Video detector ckeditor button'),
        'image' => 'libraries/videodetector/icons/videodetector.svg',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/videodetector/plugin.js';

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
