<?php

namespace Drupal\sendinblue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sendinblue\SendinblueManager;

/**
 * Class Form Transactionnal emails SMTP.
 */
class RegisteringUserForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER;
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
    $sendinblue_lists = SendinblueManager::getLists();
    $options = [];
    foreach ($sendinblue_lists as $mc_list) {
      $options[$mc_list['id']] = $mc_list['name'];
    }

    $form['sendinblue_put_registered_user'] = [
      '#tree' => TRUE,
    ];

    $form['sendinblue_put_registered_user']['active'] = [
      '#type' => 'radios',
      '#title' => t('Save SendInBlue User ?'),
      '#default_value' => \Drupal::config(SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER)
        ->get('sendinblue_put_registered_user', '')['active'],
      '#description' => t('Register the user in SendInBlue list during registration'),
      '#options' => [1 => t('Yes'), 0 => t('No')],
    ];

    $form['sendinblue_put_registered_user']['list'] = [
      '#type' => 'select',
      '#title' => t('List where subscribers are saved'),
      '#options' => $options,
      '#default_value' => \Drupal::config(SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER)
        ->get('sendinblue_put_registered_user', '')['list'],
      '#description' => t('Select the list where you want to add your new subscribers'),
      '#states' => [
        // Hide unless needed.
        'visible' => [
          ':input[name="sendinblue_put_registered_user[active]"]' => ['value' => 1],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Settings'),
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
    $sendinblue_put_registered_user = $form_state->getValue('sendinblue_put_registered_user');

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER);
    $config->set('sendinblue_put_registered_user', $sendinblue_put_registered_user)->save();

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
    return [SendinblueManager::CONFIG_SETTINGS_REGISTERING_USER];
  }

}
