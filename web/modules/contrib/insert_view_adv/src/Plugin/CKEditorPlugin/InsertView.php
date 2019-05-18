<?php

namespace Drupal\insert_view_adv\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * The plugin for insert_view_adv .
 *
 * @CKEditorPlugin(
 *   id = "insert_view_adv",
 *   label = @Translation("Advanced Insert View WYSIWYG")
 * )
 */
class InsertView extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'insert_view_adv') . '/plugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'insert_view_adv' => [
        'label' => $this->t('Advanced insert View'),
        'image' => drupal_get_path('module', 'insert_view_adv') . '/plugin/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['insert_view_adv'])) {
      $plugin_specific_settings = $settings['plugins']['insert_view_adv'];
    } else {
      $plugin_specific_settings = ['enable_live_preview' => TRUE];
    }
    return $plugin_specific_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    if (!empty($settings['plugins']['insert_view_adv'])) {
      $plugin_specific_settings = $settings['plugins']['insert_view_adv'];
    } else {
      $plugin_specific_settings = ['enable_live_preview' => TRUE];
    }
    $form['enable_live_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable live preview of the view token'),
      '#description' => $this->t('By default CKEditor displays only token with view details, if you want to see the exact results of the view leave this checkbox checked'),
      '#default_value' => (isset($plugin_specific_settings['enable_live_preview'])) ? $plugin_specific_settings['enable_live_preview'] : TRUE,
      '#return_value' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'insert_view_adv/preview',
    ];
  }
}
