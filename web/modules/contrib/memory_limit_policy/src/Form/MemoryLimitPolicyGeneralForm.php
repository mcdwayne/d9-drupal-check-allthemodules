<?php

namespace Drupal\memory_limit_policy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The general settings of the policy not tied to constraints.
 */
class MemoryLimitPolicyGeneralForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'memory_limit_policy_general_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\memory_limit_policy\Entity\MemoryLimitPolicy $policy */
    $policy = $cached_values['memory_limit_policy'];

    $form['memory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Memory'),
      '#description' => $this->t('The memory to set with this policy. Please add the unit too. (128M, 1G, ...)'),
      '#default_value' => $policy->getMemory(),
      '#size' => 8,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $policy->id() ? $policy->status() : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Validate the memory is a valid value.

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\memory_limit_policy\Entity\MemoryLimitPolicy $policy */
    $policy = $cached_values['memory_limit_policy'];
    $policy->set('memory', $form_state->getValue('memory'));
    $policy->set('status', $form_state->getValue('status'));
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
