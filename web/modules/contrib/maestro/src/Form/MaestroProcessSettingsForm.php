<?php

namespace Drupal\maestro\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MaestroProcessSettingsForm.
 *
 * @package Drupal\maestro\Form
 *
 * @ingroup maestro
 */
class MaestroProcessSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'maestro_process_entity_settings';
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
    $form['contact_settings']['#markup'] = 'Settings form for ContentEntityExample. Manage field settings here.';
    return $form;
  }

}
