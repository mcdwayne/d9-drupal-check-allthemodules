<?php

namespace Drupal\eloqua_app_cloud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EloquaAppCloudServiceSettingsForm.
 *
 * @package Drupal\eloqua_app_cloud\Form
 *
 * @ingroup eloqua_app_cloud
 */
class EloquaAppCloudServiceSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'EloquaAppCloudService_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for Eloqua AppCloud Service entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['EloquaAppCloudService_settings']['#markup'] = 'Settings form for Eloqua AppCloud Service entities. Manage field settings here.';
    return $form;
  }

}
