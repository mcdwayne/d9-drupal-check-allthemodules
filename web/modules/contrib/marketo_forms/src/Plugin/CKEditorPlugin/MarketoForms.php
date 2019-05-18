<?php

/**
 * @file
 * Definition of \Drupal\marketo_forms\Plugin\CKEditorPlugin\MarketoForms.
 */

namespace Drupal\marketo_forms\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "MarketoForms" plugin.
 *
 * @CKEditorPlugin(
 *   id = "marketo_forms",
 *   label = @Translation("Marketo Forms")
 * )
 */
class MarketoForms extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
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
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'marketo_forms') . '/assets/marketo_forms.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'marketo_forms' => [
        'label' => t('Marketo Forms'),
        'image' => drupal_get_path('module', 'marketo_forms') .  '/assets/icon.png',
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'marketo_forms_dialog_title' => t('Marketo Forms'),
    ];
  }
}
