<?php

namespace Drupal\quizard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QuizardConfigForm.
 *
 * @package Drupal\quizard\Form
 */
class QuizardConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'quizard.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quizard_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $quiz_config = $this->config('quizard.config');

    $form['pass_level'] = array(
      '#type' => 'textfield',
      '#required' => FALSE,
      '#size' => 4,
      '#field_suffix' => '%',
      '#title' => t('Pass/Fail level'),
      '#description' => t('Set the minimum score required to pass quiz\'s.'),
      '#default_value' => $quiz_config->get('pass_level'),
    );

    $form['retries'] = array(
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('Retries'),
      '#description' => t('Number of retries a user has on each quiz.'),
      '#default_value' => $quiz_config->get('retries'),
    );

    $form['success_message'] = array(
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('Success Message'),
      '#description' => t('Message shown to user when a quiz is passed.'),
      '#default_value' => $quiz_config->get('success_message'),
    );

    $form['failure_message'] = array(
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('Failure Message'),
      '#description' => t('Message shown to user when a quiz is passed.'),
      '#default_value' => $quiz_config->get('failure_message'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('quizard.config')
      ->set('pass_level', $form_state->getValue('pass_level'))
      ->set('success_message', $form_state->getValue('success_message'))
      ->set('failure_message', $form_state->getValue('failure_message'))
      ->set('retries', $form_state->getValue('retries'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
