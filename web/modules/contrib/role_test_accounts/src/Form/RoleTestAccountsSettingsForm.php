<?php

namespace Drupal\role_test_accounts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class RoleTestAccountsSettingsForm.
 *
 * @package Drupal\role_test_accounts\Form
 */
class RoleTestAccountsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_test_accounts_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['role_test_accounts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('role_test_accounts.settings');

    $form['roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure role test account generation'),
      '#open' => TRUE,
    ];
    $form['roles']['selection_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Role selection method'),
      '#options' => [
        'exclude' => $this->t('Do not generate role test accounts for the roles below'),
        'include' => $this->t('Only generate role test accounts for the roles below'),
      ],
      '#default_value' => !empty($config->get('selection_method')) ? $config->get('selection_method') : 'exclude',
    ];

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    unset($roles[AccountInterface::AUTHENTICATED_ROLE]);
    $form['roles']['selected_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($config->get('selected_roles')) ? $config->get('selected_roles') : [],
      '#options' => $roles,
    ];

    $form['authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication settings'),
      '#open' => TRUE,
    ];
    $form['authentication']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Set a new password for all role test accounts'),
      '#description' => $this->t('Leave empty to keep the current password.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('role_test_accounts.settings');
    $config
      ->set('selection_method', $form_state->getValue('selection_method'))
      ->set('selected_roles', array_values(array_filter($form_state->getValue('selected_roles'))));
    if (!empty($form_state->getValue('password'))) {
      $config->set('password', $form_state->getValue('password'));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
