<?php

namespace Drupal\ckeditor_advanced_tab\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Advanced Tab" plugin.
 *
 * @CKEditorPlugin(
 *   id = "dialogadvtab",
 *   label = @Translation("Advanced Tab Dialog")
 * )
 */
class CKEditorAdvancedTab extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/dialogadvtab/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'CreateDiv' => [
        'label' => t('Advanced Tab'),
        'image' => 'libraries/ckeditor/plugins/dialogadvtab/icons/table.png',
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
