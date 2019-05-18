<?php

namespace Drupal\webform_rrn_nrn\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'webform_belgian_national_insurance_number'.
 *
 * @WebformElement(
 *   id = "webform_belgian_national_insurance_number",
 *   label = @Translation("Belgian National Insurance Number"),
 *   description = @Translation("Provides a field for a Belgian National Insurance Number"),
 *   category = @Translation("Custom"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformBelgianNationalInsuranceNumber extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'multiple' => '',
      'size' => '',
      'minlength' => '',
      'maxlength' => '',
      'placeholder' => '',
      'error_message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['rrn_nrn'] = [
      '#type' => 'details',
      '#title' => t('Belgian National Insurance Number'),
      '#open' => TRUE,
    ];

    $form['rrn_nrn']['error_message'] = [
      '#type' => 'textfield',
      '#title' => t('Error message'),
      '#description' => t('This message will be shown when the user fills in an invalid Belgian National Insurance Number.'),
      '#required' => TRUE,
    ];
    return $form;
  }

}
