<?php

namespace Drupal\ckeditor_emojione\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "emojione" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "emojione",
 *   label = @Translation("Emojione ckeditor button")
 * )
 */
class EmojioneCKEditorButton extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = '/libraries/emojione';
    return [
      'Emojione' => [
        'label' => $this->t('Emoji ckeditor button'),
        'image' => $path . '/icons/emojione.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $path = '/libraries/emojione';
    return $path . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
