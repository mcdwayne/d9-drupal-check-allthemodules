<?php

namespace Drupal\user_delete_reassign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure user delete reassign settings for this site.
 */
class UserDeleteReassignConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_delete_reassign_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_delete_reassign.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_delete_reassign.settings');

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));

    $form['role_filter'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the user role you want to filter on.'),
      '#default_value' => $config->get('role_filter'),
      '#options' => $roles,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('user_delete_reassign.settings')
    // Set the submitted configuration setting.
      ->set('role_filter', $form_state->getValue('role_filter'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
