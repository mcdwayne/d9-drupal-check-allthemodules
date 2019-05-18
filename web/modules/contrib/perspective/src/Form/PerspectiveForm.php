<?php

namespace Drupal\perspective\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * PerspectiveForm class.
 */
class PerspectiveForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'perspective_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('perspective.settings');

    $form['google_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API URL:'),
      '#default_value' => $config->get('perspective.google_api_url'),
      '#description' => $this->t('Find the URL here: https://github.com/conversationai/perspectiveapi/blob/master/quickstart.md.'),
    ];

    $form['google_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API Key:'),
      '#default_value' => $config->get('perspective.google_api_key'),
      '#description' => $this->t('Get your api key here: https://console.cloud.google.com/apis/dashboard.'),
    ];

    $form['tolerance'] = [
      '#type' => 'number',
      '#title' => $this->t('Toxocity tolerance:'),
      '#default_value' => $config->get('perspective.tolerance'),
      '#attributes' => [
        'min' => 0,
        'max' => 100,
      ],
      '#description' => $this->t('From 0 to 100 (non-toxic to toxic).'),
    ];

    $form['error_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error text:'),
      '#default_value' => $config->get('perspective.error_text'),
      '#description' => $this->t('The error that will show up when the message fails the validation.'),
    ];

    $form['use_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Ajax'),
      '#default_value' => $config->get('perspective.use_ajax'),
      '#description' => $this->t('If you check this option, the validation will be done on JavaScript first.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('perspective.settings')
      ->set('perspective.google_api_url', $form_state->getValue('google_api_url'))
      ->set('perspective.google_api_key', $form_state->getValue('google_api_key'))
      ->set('perspective.tolerance', $form_state->getValue('tolerance'))
      ->set('perspective.error_text', $form_state->getValue('error_text'))
      ->set('perspective.use_ajax', $form_state->getValue('use_ajax'))
      ->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This function returns the name of the settings files we will
    // create / use.
    return [
      'perspective.settings',
    ];
  }

}
