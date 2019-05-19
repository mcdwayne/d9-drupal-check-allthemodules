<?php

namespace Drupal\transaction_workflow\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\transaction\Form\TransactionForm;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\transaction_workflow\Plugin\Transaction\WorkflowTransactor;

/**
 * Transaction workflow transaction block form.
 *
 * The transaction block form:
 *   - do not saves the transaction if execution fails
 *   - executes the new transaction after created, ignoring the execution
 *     preferences set in the transaction type
 *   - after submit, it redirects to the target entity canonical URL
 */
class TransactionWorkflowTransactionForm extends TransactionForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $target_entity = NULL, $options = []) {
    $form = parent::buildForm($form, $form_state, $target_entity);
    // No advanced container in workflow transaction form blocks.
    unset($form['advanced']);
    unset($form['transaction_authoring']);
    unset($form['uid']['#group']);
    unset($form['created']['#group']);

    // Alter the default form submit button.
    if (!empty($options['submit_label'])
      && $form['actions']['submit']['#value'] == $this->t('Save')->render()) {
      $form['actions']['submit']['#value'] = $options['submit_label'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->buildEntity($form, $form_state);
    // Try to execute the transaction.
    if (!$transaction->execute(FALSE)) {
      $transactor_settings = $transaction->getType()->getPluginSettings();
      $form_state->setErrorByName(
        $transaction->getResultCode() == WorkflowTransactor::RESULT_ILLEGAL_TRANSITION ? $transactor_settings['state'] : '',
        $transaction->getResultMessage()
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->entity;
    // Redirect to the target entity canonical URL.
    $form_state->setRedirectUrl($transaction->getTargetEntity()->toUrl());
    // Display the execution result message.
    drupal_set_message($transaction->getResultMessage());
    return $saved;
  }

}
