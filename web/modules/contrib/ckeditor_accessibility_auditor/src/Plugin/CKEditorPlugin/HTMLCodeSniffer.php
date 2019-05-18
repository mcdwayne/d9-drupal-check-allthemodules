<?php

namespace Drupal\ckeditor_accessibility_auditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Defines the "html_codesniffer" plugin.
 *
 * @CKEditorPlugin(
 *   id = "html_codesniffer",
 *   label = @Translation("Accessibility Auditor (HTML_CodeSniffer)"),
 *   module = "ckeditor_html_codesniffer"
 * )
 */
class HTMLCodeSniffer extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginButtonsInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();
    $base_url = !empty($settings['plugins']['html_codesniffer']['base_url']) ? $settings['plugins']['html_codesniffer']['base_url'] : '//squizlabs.github.io/HTML_CodeSniffer/build/';
    $standard = !empty($settings['plugins']['html_codesniffer']['standard']) ? $settings['plugins']['html_codesniffer']['standard'] : 'WCAG2AA';
    return [
      'html_codesniffer_base_url' => $base_url,
      'html_codesniffer_standard' => $standard,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_accessibility_auditor') . '/js/plugins/html_codesniffer/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'HTML_CodeSniffer' => [
        'label' => $this->t('Accessibility Auditor (HTML_CodeSniffer)'),
        'image' => drupal_get_path('module', 'ckeditor_accessibility_auditor') . '/js/plugins/html_codesniffer/icons/html_codesniffer.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#description' => $this->t('Enter the URL to use as a base path for loading the HTML_CodeSniffer files. Default: //squizlabs.github.io/HTML_CodeSniffer/build/'),
      '#default_value' => !empty($settings['plugins']['html_codesniffer']['base_url']) ? $settings['plugins']['html_codesniffer']['base_url'] : '//squizlabs.github.io/HTML_CodeSniffer/build/',
    ];

    $form['standard'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default standard'),
      '#description' => $this->t('Enter the default standard to be selected in the auditor (WCAG2A, WCAG2AA, WCAG2AAA, Section508)'),
      '#default_value' => !empty($settings['plugins']['html_codesniffer']['standard']) ? $settings['plugins']['html_codesniffer']['standard'] : 'WCAG2AA',
    ];

    // Prevent unprivileged users from changing the base path or standard.
    if (!\Drupal::currentUser()->hasPermission('administer site configuration')) {
      $form['base_url']['#disabled'] = TRUE;
      $form['base_url']['#description'] .= ' ' . $this->t('Only editable by Administrators!');
      $form['standard']['#disabled'] = TRUE;
      $form['standard']['#description'] .= ' ' . $this->t('Only editable by Administrators!');
    }

    $form['base_url']['#element_validate'][] = [$this, 'validateInput'];

    return $form;
  }

  /**
   * Validates the URL.
   *
   * @param array $element
   *   Element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateInput(array $element, FormStateInterface $form_state) {
    $input = $form_state->getValue([
      'editor',
      'settings',
      'plugins',
      'html_codesniffer',
      'base_url',
    ]);

    if (!UrlHelper::isValid($input)) {
      $form_state->setError($element, 'Please enter a valid Base URL for the Accessibility Auditor.');
    }
  }

}
