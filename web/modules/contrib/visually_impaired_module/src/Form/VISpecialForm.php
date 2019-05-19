<?php

namespace Drupal\visually_impaired_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class VISpecialForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visually_impaired_module_special';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['visually-impaired-block'] = [
      '#type' => 'submit',
      '#attributes' => [
        'itemprop' => 'copy',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    setcookie('visually_impaired', 'on', 0, '/');
  }

}
