<?php

namespace Drupal\ckeditor_spoiler\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "spoiler" plugin.
 *
 * @CKEditorPlugin(
 *   id = "spoiler",
 *   label = @Translation("Spoiler"),
 *   module = "ckeditor_spoiler"
 * )
 */
class SpoilerCKEditorButton extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/spoiler/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = 'libraries/spoiler';
    return array(
      'Spoiler' => array(
        'label' => t('Spoiler'),
        'image' => $path . '/icons/spoiler.png',
      ),
    );
  }

}
