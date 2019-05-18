<?php

namespace Drupal\ckeditor_autosave\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * @CKEditorPlugin(
 *   id = "autosave",
 *   label = @Translation("Auto Save")
 * )
 */
class Autosave extends CKEditorPluginBase implements CKEditorPluginContextualInterface, CKEditorPluginConfigurableInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_autosave') . '/js/plugins/autosave/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      drupal_get_path('module', 'ckeditor_autosave') . '/js/plugins/autosave/css/autosave.min.css'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return true;
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
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    // @see https://github.com/w8tcha/CKEditor-AutoSave-Plugin/tree/v0.18.1
    return [
      'autosave' => [
        'delay' => 60,
        'messageType' => 'statusbar',
        'diffType' => 'inline',
        'autoLoad' => false
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    return $form;
  }

}
