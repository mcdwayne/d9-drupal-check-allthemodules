<?php

namespace Drupal\crm_core_user_sync\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class RuleDeleteConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_core_user_sync_rule_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this rule?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('crm_core_user_sync.config');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rule_key = $this->getRequest()->get('rule_key');
    $rules = $this->configFactory()->getEditable('crm_core_user_sync.settings')->get('rules');
    unset($rules[$rule_key]);
    // Re-key.
    $rules = array_values($rules);
    $this->configFactory()->getEditable('crm_core_user_sync.settings')->set('rules', $rules)->save();

    $this->messenger()->addMessage('Rule was deleted');

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
