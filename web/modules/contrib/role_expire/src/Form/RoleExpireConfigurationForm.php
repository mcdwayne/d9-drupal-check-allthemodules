<?php

namespace Drupal\role_expire\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure order for this site.
 */
class RoleExpireConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_expire_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'role_expire.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('role_expire.config');

    $excluded_roles = array('anonymous', 'authenticated');

    $parsed_roles = array();
    $roles = user_roles();
    foreach ($roles as $label => $role) {
      $parsed_roles[$role->id()] = $role->label();
    }

    $values_raw = $config->get('role_expire_default_roles');
    $values = empty($values_raw) ? array() : json_decode($values_raw, TRUE);

    $default = array(
      0 => $this->t('- None -')
    );
    // It is important to respect the keys on this array merge.
    $roles_select = $default + $parsed_roles;

    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => t('General settings'),
      '#weight' => 1,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    foreach ($parsed_roles as $rid => $role_name) {
      if (!in_array($rid, $excluded_roles)) {
        $form['general'][$rid] = array(
          '#type' => 'select',
          '#options' => $roles_select,
          '#title' => $this->t('Role to assign after the role ":r" expires', array(':r' => $role_name)),
          '#default_value' => isset($values[$rid]) ? $values[$rid] : 0,
        );
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $excluded_roles = array('anonymous', 'authenticated');

    $data = array();
    $parsed_roles = array();
    $roles = user_roles();
    foreach ($roles as $label => $role) {
      $parsed_roles[$role->id()] = $role->label();
    }
    foreach ($parsed_roles as $rid => $role_name) {
      if (!in_array($rid, $excluded_roles)) {
        $data[$rid] = $values[$rid];
      }
    }

    $this->config('role_expire.config')
      ->set('role_expire_default_roles', json_encode($data))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
