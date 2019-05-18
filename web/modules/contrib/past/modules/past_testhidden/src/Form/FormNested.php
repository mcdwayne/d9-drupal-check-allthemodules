<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormNested extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_nested';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wrapper'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['wrapper']['field_1'] = [
      '#type' => 'textfield',
      '#title' => t('Subject 1'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['wrapper']['field_2'] = [
      '#type' => 'textfield',
      '#title' => t('Subject 2'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValues()['wrapper']['field_1'] != 'correct value') {
      $form_state->setErrorByName('wrapper][field_1', t("Field 1 doesn't contain the right value"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The form has been submitted.'));
  }

}
