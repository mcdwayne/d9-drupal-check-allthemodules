<?php

namespace Drupal\widencollective\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "templates" plugin.
 *
 * @CKEditorPlugin(
 *   id = "widencollective",
 *   label = @Translation("Widen Collective Button"),
 *   module = "widencollective"
 * )
 */
class Widencollective extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'widencollective') . '/js/plugins/widencollective/plugins.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Widencollective' => [
        'label' => t('Widen Collective'),
        'image' => drupal_get_path('module', 'widencollective') . '/js/plugins/widencollective/icons/widencollective.png',
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
