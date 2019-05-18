<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormMixedSubmitHandlers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_mixed_submit_handlers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('form handler called by past_testhidden_form_mixed_submit_handlers');
    $form['sample_property'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#required' => TRUE,
      '#default_value' => 'sample value',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#submit' => [[FormCustomSubmitHandler::class, 'customSubmitHandler']],
    ];
    return $form;
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
    drupal_set_message(new FormattableMarkup('global submit handler called by @form_id', ['@form_id' => $form['#form_id']]));
  }

}
