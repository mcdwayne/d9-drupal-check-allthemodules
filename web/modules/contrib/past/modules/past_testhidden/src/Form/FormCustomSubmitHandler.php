<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormCustomSubmitHandler extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_custom_submit_handler';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('form handler called by past_testhidden_form_custom_submit_handler');
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
      '#submit' => [[get_class($this), 'customSubmitHandler']],
    ];
    return $form;
  }

  /**
   * Form submit handler.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public static function customSubmitHandler(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('custom submit handler called by @form_id', ['@form_id' => $form['#form_id']]));
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
    drupal_set_message(new FormattableMarkup('custom submit handler called by @form_id', ['@form_id' => $form['#form_id']]));
  }

}
