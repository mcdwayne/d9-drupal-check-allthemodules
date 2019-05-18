<?php

/**
 * @file
 * Contains \Drupal\accessibility_wysiwyg\Plugin\CKEditorPlugin\Accessibility.
 */

namespace Drupal\accessibility_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\Annotation\CKEditorPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "accessibility" plugin.
 *
 * @CKEditorPlugin(
 *   id = "accessibility",
 *   label = @Translation("Accessibility"),
 *   module = "accessibility_wysiwyg"
 * )
 */
class Accessibility extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'accessibility_wysiwyg') . '/js/ckeditor/accessibility/plugin.js';
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
      'Accessibility' => array(
        'label' => t('Accessibility'),
        'image' => drupal_get_path('module', 'accessibility_wysiwyg') . '/js/ckeditor/accessibility/img/button.png',
      ),
    );
  }

}
