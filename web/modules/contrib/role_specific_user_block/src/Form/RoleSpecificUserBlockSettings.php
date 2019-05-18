<?php

namespace Drupal\role_specific_user_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Class RoleSpecificUserBlockSettings implements settings form.
 */
class RoleSpecificUserBlockSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['role_specific_user_block.settings'];
  }

  /**
   * Get the form_id.
   *
   * @inheritDoc
   */
  public function getFormId() {
    return 'role_specific_user_block_form_setting';
  }

  /**
   * Build the Form.
   *
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('role_specific_user_block.settings');
    $user_roles = Role::loadMultiple();
    $roles = [];
    foreach ($user_roles as $key => $value) {
      if ($key != 'anonymous' && $key != 'administrator' && $key != 'authenticated') {
        $roles[$key] = ucwords($key);
      }
    }
    $default_role = $config->get('role_specific_user_block');
    $form['role_specific_user_block'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Role'),
      '#description' => $this->t('Choosing Role will block all user related to this role.'),
      '#options' => $roles,
      '#default_value' => $default_role,
    ];

    $role_err_message = $config->get('role_specific_user_block_err_message');
    $form['role_specific_user_block_err_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter error message to show when user login.'),
      '#required' => TRUE,
      '#default_value' => $role_err_message,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Add submit handler.
   *
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input_values = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('role_specific_user_block.settings');
    $config->set('role_specific_user_block', $user_input_values['role_specific_user_block']);
    $config->set('role_specific_user_block_err_message', $user_input_values['role_specific_user_block_err_message']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
