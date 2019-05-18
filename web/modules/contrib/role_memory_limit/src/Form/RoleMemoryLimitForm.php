<?php

namespace Drupal\role_memory_limit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Role memory limit config form.
 */
class RoleMemoryLimitForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'role_memory_limit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_memory_limit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('role_memory_limit.config');
    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadMultiple();
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'] = ['role_memory_limit/admin'];
    $form['instructions']['#markup'] = $this->t('Enter the memory in megabytes or -1 for unlimited.<br />Leave empty for default.');
    $form['drush'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Drush'),
      '#default_value' => $config->get('drush'),
      '#suffix' => 'MB',
    ];


    foreach ($roles as $role) {
      $form[$role->id()] = [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $role->label(),
        '#default_value' => $config->get($role->id()),
        '#suffix' => 'MB',
        '#input_group' => TRUE,
      ];
      $form['#form_keys'][] = $role->id();
    }

    $form['#form_keys'][] = 'drush';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($form['#form_keys'] as $item) {
      if ($values[$item] === '' || $values[$item] === '-1') {
        continue;
      }
      if (!is_numeric($values[$item])) {
        $form_state->setErrorByName($item, $this->t('Numbers only please.'));
        continue;
      }
      if ($values[$item] < 64) {
        $form_state->setErrorByName($item, $this->t('Minimum allowed is 64 MB.'));
      }
      if ($values[$item] > 2048) {
        $form_state->setErrorByName($item, $this->t('Maximum allowed is 2048 MB.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('role_memory_limit.config');

    foreach ($form['#form_keys'] as $item) {
      if ($values[$item] === '') {
        continue;
      }
      $config->set($item, $values[$item]);
    }

    $config->save();
  }

}
