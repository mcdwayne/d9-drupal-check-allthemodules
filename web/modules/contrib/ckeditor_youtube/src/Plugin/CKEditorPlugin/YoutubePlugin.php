<?php

namespace Drupal\ckeditor_youtube\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Youtube Button" plugin.
 *
 * @CKEditorPlugin(
 *   id = "youtube",
 *   label = @Translation("Youtube Plugin")
 * )
 */
class YoutubePlugin extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Youtube' => array(
        'label' => t('YouTube'),
        'image' => 'libraries/youtube/images/icon.png',
      ),
    );
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
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/youtube/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
