<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormMultiValidation extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_multi_validation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('form handler called by past_testhidden_form_multi_validation');
    $form['sample_property'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#required' => TRUE,
      '#default_value' => 'sample value',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['another_sample_property'] = [
      '#type' => 'checkbox',
      '#title' => t('Another Sample Property'),
      '#default_value' => 0,
      '#required' => TRUE,
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['sample_select'] = [
      '#type' => 'select',
      '#title' => t('Sample Select'),
      '#options' => [
        0 => 'No',
        1 => 'Yes',
        2 => 'Maybe',
      ],
      '#default_value' => 2,
      '#multipe' => FALSE,
      '#description' => 'Please enter a dummy value.',
      '#element_validate' => [[get_class($this), 'elementSampleSelectValidate']],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
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
   * Form element validation handler.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   * @param array $form
   *   The form.
   */
  public static function elementSampleSelectValidate(array $element, FormStateInterface &$form_state, array $form) {
    if ($element['#name'] == 'sample_select') {
      if ($element['#value'] == '') {
        $form_state->setErrorByName($element['#name'], $element['#title'] . ' field is required.');
      }
      elseif ($element['#value'] == '2') {
        $form_state->setErrorByName($element['#name'], $element['#title'] . ': says, don\'t be a maybe ..');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('global submit handler called by @form_id', ['@form_id' => $form['#form_id']]));
  }

}
