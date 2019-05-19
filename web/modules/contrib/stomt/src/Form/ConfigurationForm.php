<?php

namespace Drupal\stomt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stomt_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stomt.widget',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stomt.widget');

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your business page username'),
      '#description' => $this->t('The username of your business on STOMT.com (www.stomt.com/YOUR_NAME)'),
      '#size' => 30,
      '#maxlength' => 30,
      '#default_value' => $config->get('username'),
      '#required' => TRUE,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The label of your feedback button'),
      '#size' => 30,
      '#maxlength' => 30,
      '#default_value' => $config->get('label'),
    ];

    $form['color_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The color of your STOMT-button text'),
      '#description' => $this->t('Enter any valid css color e.g #FFFFFF, white, rgba(255, 255, 255, 1)'),
      '#size' => 30,
      '#maxlength' => 30,
      '#default_value' => $config->get('color_text'),
    ];

    $form['color_background'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The color of your STOMT-button background'),
      '#description' => $this->t('Enter any valid css color e.g #0091C9, blue, rgba(0, 145, 201, 1)'),
      '#size' => 30,
      '#maxlength' => 30,
      '#default_value' => $config->get('color_background'),
    ];

    $form['color_hover'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The color of your STOMT-button background hover-effect'),
      '#description' => $this->t('Enter any valid css color e.g #04729E, darkblue, rgba(4, 114, 158, 1)'),
      '#size' => 30,
      '#maxlength' => 30,
      '#default_value' => $config->get('color_hover'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('username')) < 3) {
      $form_state->setErrorByName('username', $this->t('The username is too short. Please check that you use your existing STOMT username.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('stomt.widget')
      // Set the submitted configuration setting.
      ->set('username', $form_state->getValue('username'))
      ->set('label', $form_state->getValue('label'))
      ->set('color_text', $form_state->getValue('color_text'))
      ->set('color_background', $form_state->getValue('color_background'))
      ->set('color_hover', $form_state->getValue('color_hover'))
      // Save the new values.
      ->save();

    parent::submitForm($form, $form_state);
  }

}
