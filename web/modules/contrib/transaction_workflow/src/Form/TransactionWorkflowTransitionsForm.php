<?php

namespace Drupal\transaction_workflow\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transaction\TransactionTypeInterface;

/**
 * Provides the transitions workflow form.
 */
class TransactionWorkflowTransitionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transaction_workflow_transitions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TransactionTypeInterface $transaction_type = NULL) {
    $form['#transaction_type'] = $transaction_type;
    $transactor_settings = $transaction_type->getPluginSettings();

    // Allowed transitions.
    $initial = ['' => $this->t('- no state -')];
    $states = $initial
      + $transaction_type->getThirdPartySetting('transaction_workflow', 'states', []);
    foreach ($states as $key => $label) {
      $config_key = 'transitions_' . $key;
      $form[$config_key] = [
        '#type' => 'checkboxes',
        '#title' => $key ? $this->t('Transitions from %label', ['%label' => $label]) : $this->t('Allowed initial states'),
        '#options' => array_diff($states, $initial + [$key => $label]),
        '#default_value' => isset($transactor_settings[$config_key])
          ? explode(',', $transactor_settings[$config_key])
          : [''],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $form['#transaction_type'];
    $transactor_settings = $transaction_type->getPluginSettings();

    foreach ($form_state->getValues() as $config_key => $value) {
      if (strpos($config_key, 'transitions_') === 0) {
        $transactor_settings[$config_key] = implode(',', array_intersect(array_keys($value), $value));
      }
    }

    $transaction_type->setPluginSettings($transactor_settings);
    $transaction_type->save();

    drupal_set_message($this->t('Workflow transitions for the transaction type %label saved.', ['%label' => $transaction_type->label()]));
    $form_state->setRedirectUrl($transaction_type->toUrl('collection'));
  }

}
