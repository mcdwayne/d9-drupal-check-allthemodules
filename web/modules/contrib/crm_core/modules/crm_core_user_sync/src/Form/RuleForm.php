<?php

namespace Drupal\crm_core_user_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crm_core_contact\Entity\IndividualType;

/**
 * Configure CRM Core User Synchronization settings for this site.
 */
class RuleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_core_user_sync_rule_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['crm_core_user_sync.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rule_key = 'new') {
    $config = $this->config('crm_core_user_sync.settings');
    $rules = $config->get('rules');

    if ($rule_key === 'new') {
      $rule = [
        'role' => '',
        'contact_type' => '',
        'enabled' => TRUE,
        'weight' => 0,
      ];
      $form_state->set('rule_key', 'new');
    }
    else {
      if (isset($rules[$rule_key])) {
        $rule = $rules[$rule_key];
        $form_state->set('rule', $rule);
        $form_state->set('rule_key', $rule_key);
      }
      else {
        $rule = [
          'role' => '',
          'contact_type' => '',
          'enabled' => TRUE,
          'weight' => 0,
        ];
        $form_state->set('rule_key', 'new');
      }
    }

    $types_options = ['' => $this->t('- Select -')];
    foreach (IndividualType::loadMultiple() as $type) {
      $types_options[$type->id()] = $type->label();
    }

    $role_options = ['' => $this->t('- Select -')];
    foreach (user_roles(TRUE) as $role) {
      $role_options[$role->id()] = $role->label();
    }

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('User Role'),
      '#options' => $role_options,
      '#default_value' => isset($rule['role']) ? $rule['role'] : '',
      '#required' => TRUE,
    ];

    $form['contact_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact Type'),
      '#options' => $types_options,
      '#default_value' => isset($rule['contact_type']) ? $rule['contact_type'] : '',
      '#required' => TRUE,
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => isset($rule['weight']) ? $rule['weight'] : 0,
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => isset($rule['enabled']) ? $rule['enabled'] : TRUE,
      '#description' => $this->t('When checked, this rule will be used to synchronize user accounts. When unchecked, it will be ignored throughout the system.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rule = $form_state->get('rule');
    foreach (['role', 'contact_type', 'weight', 'enabled'] as $key) {
      $rule[$key] = $form_state->getValue($key);
    }

    $rules = $this
      ->config('crm_core_user_sync.settings')
      ->get('rules');

    $rule_key = $form_state->get('rule_key');
    if ($rule_key === 'new') {
      $rules[] = $rule;
    }
    else {
      $rules[$rule_key] = $rule;
    }

    $this->config('crm_core_user_sync.settings')
      ->set('rules', $rules)
      ->save();

    $form_state->setRedirect('crm_core_user_sync.config');

    parent::submitForm($form, $form_state);
  }

}
