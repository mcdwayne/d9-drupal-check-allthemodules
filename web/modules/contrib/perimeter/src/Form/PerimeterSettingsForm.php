<?php

/**
 * @file
 * Contains Drupal\perimeter\Form\PerimeterSettingsForm.
 */

namespace Drupal\perimeter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PerimeterSettingsForm.
 *
 * @package Drupal\perimeter\Form
 */
class PerimeterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'perimeter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'perimeter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('perimeter.settings');
    $form['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => t('Perimeter will ban any user that generates "404 - File not found" for requesting any of the following url pattern'),
    ];
    $form['not_found_exception_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form IDs'),
      '#default_value' => implode("\n", $config->get('not_found_exception_patterns')),
      '#description' => $this->t('A list of regex patterns, each pattern on a separate line'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('perimeter.settings')
      ->set('not_found_exception_patterns', explode("\n", $form_state->getValue('not_found_exception_patterns')))
      ->save();
  }

}
