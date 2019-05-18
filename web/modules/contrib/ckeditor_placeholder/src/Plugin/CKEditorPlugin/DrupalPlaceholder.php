<?php

namespace Drupal\ckeditor_placeholder\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalplaceholder" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalplaceholder",
 *   label = @Translation("Placeholder"),
 *   module = "ckeditor_placeholder"
 * )
 */
class DrupalPlaceholder extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_placeholder') . '/js/plugins/drupalplaceholder/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'drupalPlaceholder_dialogTitleAdd' => $this->t('Insert Placeholder'),
      'drupalPlaceholder_dialogTitleEdit' => $this->t('Edit Placeholder'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'DrupalPlaceholder' => [
        'label' => $this->t('Placeholder'),
        'image' => drupal_get_path('module', 'ckeditor_placeholder') . '/js/plugins/drupalplaceholder/icons/drupalplaceholder.png',
      ],
    ];
  }

}
