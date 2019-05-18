<?php

namespace Drupal\kashing_button\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "kashing" plugin.
 *
 * @CKEditorPlugin(
 *   id = "kashing_button",
 *   label = @Translation("CKEditor Kashing Button")
 * )
 */

/**
 * CKEditor settings page configuration and items visibility.
 */
class KashingButton extends CKEditorPluginBase implements
    CKEditorPluginInterface,
    CKEditorPluginButtonsInterface,
    CKEditorPluginConfigurableInterface {

  /**
   * Get Libraries.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   Editor.
   *
   * @return array
   *   Libraries
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * Is Internal.
   *
   * @return bool
   *   Boolean
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Get File.
   *
   * @return false|string
   *   False/String
   */
  public function getFile() {

    $path = drupal_get_path('module', 'kashing_button') . '/js/plugins/layoutmanager/plugin.js';

    return $path;
  }

  /**
   * Get Config.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   Editor.
   *
   * @return array
   *   Array
   */
  public function getConfig(Editor $editor) {

    $ids = \Drupal::entityQuery('block')
      ->condition('plugin', 'kashing_block')
      ->execute();

    $shortcode_ids = [[t('Code template'), '']];

    foreach ($ids as $id) {
      $shortcode_ids[] = [$id, $id];
    }

    return [
      'kashing_block_ids' => $shortcode_ids,
    ];
  }

  /**
   * Get buttons.
   *
   * @return array
   *   Array
   */
  public function getButtons() {

    $path = $path = drupal_get_path('module', 'kashing_button') . '/js/plugins/layoutmanager/icons';
    return [
      'kashing_button' => [
        'label' => t('Kashing shortcode'),
        'image' => $path . '/kashing_button.png',
      ],
    ];
  }

  /**
   * Adds a CKEditor plugins settings panel.
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {

    $form['Kashing'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Kashing shortcode settings'),
    ];

    return $form;
  }

  /**
   * Example field validation.
   */
  public function validateInput(array $element, FormStateInterface $form_state) {
    $input = $form_state->getValue([
      'editor',
      'settings',
      'plugins',
      'kashing_button',
      'Kashing',
    ]);

    if (preg_match('/([^A-F0-9,])/i', $input)) {
      $form_state->setError($element, 'Regex error');
    }
  }

}
