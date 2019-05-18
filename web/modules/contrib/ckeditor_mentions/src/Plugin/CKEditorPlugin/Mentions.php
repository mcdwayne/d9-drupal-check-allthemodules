<?php

namespace Drupal\ckeditor_mentions\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "mentions" plugin.
 *
 * @CKEditorPlugin(
 *   id = "mentions",
 *   label = @Translation("Mentions")
 * )
 */
class Mentions extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    return [
      'mentions' => [
        'image' => !empty($settings['plugins']['mentions']['image']) ? $settings['plugins']['mentions']['image'] : FALSE,
        'charcount' => !empty($settings['plugins']['mentions']['charcount']) ? $settings['plugins']['mentions']['charcount'] : 3,
        'timeout' => !empty($settings['plugins']['mentions']['timeout']) ? $settings['plugins']['mentions']['timeout'] : 500,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_mentions') . '/js/plugins/mentions/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    return isset($settings['plugins']['mentions']) ? $settings['plugins']['mentions']['enable'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      drupal_get_path('module', 'ckeditor_mentions') . '/css/plugins/mentions/ckeditor_mentions.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'ckeditor_mentions/drupal.ckeditor.plugins.mentions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Mentions'),
      '#default_value' => !empty($settings['plugins']['mentions']['enable']) ? $settings['plugins']['mentions']['enable'] : FALSE,
    ];

    $form['image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable User Icon'),
      '#default_value' => !empty($settings['plugins']['mentions']['image']) ? $settings['plugins']['mentions']['image'] : FALSE,
    ];

    $form['charcount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Character Count'),
      '#description' => $this->t('Enter minimum number of characters that must be typed to trigger mention match.'),
      '#default_value' => !empty($settings['plugins']['mentions']['charcount']) ? $settings['plugins']['mentions']['charcount'] : 3,
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout (milliseconds)'),
      '#description' => $this->t('Enter time in milliseconds for mentions script to stop checking for matches.'),
      '#default_value' => !empty($settings['plugins']['mentions']['timeout']) ? $settings['plugins']['mentions']['timeout'] : 500,
    ];

    $form['charcount']['#element_validate'][] = [$this, 'isPositiveNumber'];
    $form['timeout']['#element_validate'][] = [$this, 'isPositiveNumber'];

    return $form;
  }

  /**
   * Check if value is positive.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState Object.
   */
  public function isPositiveNumber(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value']) || $element['#value'] < 1) {
      $form_state->setError($element, 'Value must be a positive integer.');
    }
  }

}
