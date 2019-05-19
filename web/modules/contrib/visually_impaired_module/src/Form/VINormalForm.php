<?php

namespace Drupal\visually_impaired_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class VINormalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visually_impaired_module_normal';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['visually-impaired-normal-block'] = [
      '#type' => 'submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    setcookie('visually_impaired', 'off', 0, '/');
  }

}
