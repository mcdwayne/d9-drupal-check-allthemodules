<?php

namespace Drupal\memory_limit_policy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MemoryLimitPolicySettingsForm.
 */
class MemoryLimitPolicySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'memory_limit_policy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['memory_limit_policy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('memory_limit_policy.settings');

    $form['header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add memory information in response headers'),
      '#default_value' => $config->get('header'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('memory_limit_policy.settings');
    $config->set('header', $form_state->getValue('header'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
