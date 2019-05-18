<?php

namespace Drupal\ckeditor_iframe\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "iframe" plugin.
 *
 * @CKEditorPlugin(
 *   id = "iframe",
 *   label = @Translation("iFrame")
 * )
 */
class IFrame extends CKEditorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['fakeobjects'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/iframe/plugin.js';
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
  public function getButtons() {
    return [
      'Iframe' => array(
        'label' => $this->t('Embed iFrame'),
        'image' => 'libraries/iframe/icons/iframe.png',
      ),
    ];
  }
}