<?php

namespace Drupal\maestro\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MaestroProcessVariablesSettingsForm.
 *
 * @package Drupal\maestro\Form
 *
 * @ingroup maestro
 */
class MaestroProcessVariablesSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'maestro_process_variables_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['contact_settings']['#markup'] = 'Maestro Process Variable Entity Settings.';
    return $form;
  }

}
