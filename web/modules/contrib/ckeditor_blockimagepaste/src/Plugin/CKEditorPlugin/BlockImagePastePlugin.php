<?php

namespace Drupal\ckeditor_blockimagepaste\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "blockimagepaste" plugin.
 *
 * @CKEditorPlugin(
 *   id = "blockimagepaste",
 *   label = @Translation("Block image paste")
 * )
 */
class BlockImagePastePlugin extends PluginBase implements CKEditorPluginContextualInterface, CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    return !empty($settings['plugins']['blockimagepaste']['block_image_paste_enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
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
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_blockimagepaste') . '/js/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['block_image_paste_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Block image paste enabled'),
      '#default_value' => !empty($settings['plugins']['blockimagepaste']['block_image_paste_enabled']),
      '#description' => $this->t('Prevent users from pasting images in the editor.'),
    ];

    return $form;
  }

}
