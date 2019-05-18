<?php

namespace Drupal\tweet_text\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "tweetable_text" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tweetabletext",
 *   label = @Translation("Tweet button"),
 *   module = "tweetable_text"
 * )
 */
class Tweetbutton extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return base_path() . 'libraries/tweetable_text/plugin.js';
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
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'TweetableText' => array(
        'label' => t('Tweet Button'),
        'image' => base_path() . 'libraries/tweetable_text/icons/tweetabletext.png',
      ),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }
}