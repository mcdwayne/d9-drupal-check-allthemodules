<?php

namespace Drupal\tweetext\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "tweetext" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tweetabletext",
 *   label = @Translation("Tweet button"),
 *   module = "tweetext"
 * )
 */
class Tweetbutton extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return 'libraries/tweetable_text/plugin.js';
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
      'TweetableText' => [
        'label' => t('Tweet Button'),
        'image' => base_path() . 'libraries/tweetable_text/icons/tweetabletext.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
