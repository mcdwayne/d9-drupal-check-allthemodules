<?php

namespace Drupal\alexa_quiz\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alexa_quiz_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'alexa_quiz.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('alexa_quiz.settings');
    $quiz_name = $config->get('quiz_name');
    $form['quiz_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quiz name'),
      '#required' => TRUE,
      '#description' => $this->t('Set Alexa quiz name.'),
      '#default_value' => isset($quiz_name) ? $quiz_name : 'Barcelona Quiz',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('alexa_quiz.settings')
      ->set('quiz_name', $values['quiz_name'])
      ->save();
  }

}
