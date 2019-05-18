<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormSimpleAjax extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_simple_ajax';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('form handler called by past_testhidden_form_simple_ajax');
    $form['sample_property'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#required' => TRUE,
      '#default_value' => 'sample value',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['ajax_wrapper'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="past_testhidden_form_simple_ajax_wrapper">',
      '#suffix' => '</div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#ajax' => [
        'callback' => '::simpleAjaxCallback',
        'wrapper' => 'past_testhidden_form_simple_ajax_wrapper',
      ],
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

  /**
   * Form simple AJAX callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public function simpleAjaxCallback(array &$form, FormStateInterface $form_state) {
    // if( (isset($form_state['values']['sample_property'])) &&
    // ($form_state['values']['sample_property'] != '') ) {
    $form['ajax_wrapper']['#markup'] = new FormattableMarkup('ajax called by @form_id with sample_property containing: @sample_property_value', ['@form_id' => $form['#form_id'], '@sample_property_value' => $form_state->getValues()['sample_property']]);
    // }
    return $form['ajax_wrapper'];
  }

}
