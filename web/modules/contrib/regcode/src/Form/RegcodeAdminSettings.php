<?php

/**
 * @file
 * Contains \Drupal\regcode\Form\RegcodeAdminSettings.
 */

namespace Drupal\regcode\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RegcodeAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['regcode.settings'];
  }  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'regcode_admin_settings';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $form['regcode_forms'] = [
      '#type' => 'fieldset',
      '#title' => t('Registration form field'),
    ];
    $form['regcode_forms']['regcode_field_title'] = [
      '#type' => 'textfield',
      '#title' => t('Field label'),
      '#description' => t('The label of the registration code textfield'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('regcode.settings')->get('regcode_field_title'),
    ];
    $form['regcode_forms']['regcode_field_description'] = [
      '#type' => 'textarea',
      '#title' => t('Field description'),
      '#description' => t('The description under the registration code textfield'),
      '#rows' => 2,
      '#default_value' => \Drupal::config('regcode.settings')->get('regcode_field_description'),
    ];
    $form['regcode_forms']['regcode_optional'] = [
      '#type' => 'checkbox',
      '#title' => t('Make registration code optional'),
      '#default_value' => \Drupal::config('regcode.settings')->get('regcode_optional'),
      '#description' => t('If checked, users can register without a registration code.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('regcode.settings')
      ->set('regcode_field_title', $form_state->getValue('regcode_field_title'))
      ->set('regcode_field_description', $form_state->getValue('regcode_field_description'))
      ->set('regcode_optional', $form_state->getValue('regcode_optional'))
      ->save();

  }

}
