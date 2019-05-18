<?php

namespace Drupal\sendinblue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sendinblue\SendinblueManager;

/**
 * Class Form Transactionnal emails SMTP.
 */
class LogoutForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'sendinblue_logout_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Logout Sendinblue'),
    ];

    return $form;
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
    \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS)->delete();

    \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS_SEND_EMAIL)->delete();

    \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER)->delete();
    
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['sendinblue_logout_form'];
  }

}
