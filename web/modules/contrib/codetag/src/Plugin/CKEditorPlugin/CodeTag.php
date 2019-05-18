<?php

namespace Drupal\codetag\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "codeTag" plugin.
 *
 * @CKEditorPlugin(
 *   id = "codeTag",
 *   label = @Translation("CodeTag"),
 * )
 */
class CodeTag extends CKEditorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/codeTag/plugin.js';
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
    return array(
      'Code' => array(
        'label' => $this->t('CodeTag'),
        'image' => 'libraries/codeTag/icons/code.png',
      ),
    );
  }

}
