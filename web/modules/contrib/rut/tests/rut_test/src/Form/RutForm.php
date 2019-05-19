<?php

namespace Drupal\rut_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'rut_field' elements.
 */
class RutForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rut_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['rut'] = [
      '#type' => 'rut_field',
      '#title' => 'Rut',
      '#description' => 'A rut.',
    ];
    $form['rut_required'] = [
      '#type' => 'rut_field',
      '#title' => 'Rut',
      '#required' => TRUE,
      '#description' => 'A required rut field.',
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }
}
