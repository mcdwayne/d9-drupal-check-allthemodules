<?php

namespace Drupal\transaction_workflow\Plugin\Transaction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\transaction\Plugin\Transaction\GenericTransactor;
use Drupal\transaction\TransactionInterface;
use Drupal\transaction_workflow\TransactionWorkflowServiceInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\transaction\TransactionServiceInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transactor for workflow type transactions.
 *
 * @Transactor(
 *   id = "transaction_workflow",
 *   title = @Translation("Workflow"),
 *   description = @Translation("Transactor for workflows."),
 *   transaction_fields = {
 *     {
 *       "name" = "state",
 *       "type" = "list_string",
 *       "title" = @Translation("State"),
 *       "description" = @Translation("The state in this transaction."),
 *       "required" = TRUE,
 *       "list" = TRUE,
 *     },
 *     {
 *       "name" = "log_message",
 *       "type" = "string",
 *       "title" = @Translation("Log message"),
 *       "description" = @Translation("A log message with details about the transaction."),
 *       "required" = FALSE,
 *     },
 *   },
 *   target_entity_fields = {
 *     {
 *       "name" = "last_transaction",
 *       "type" = "entity_reference",
 *       "title" = @Translation("Last transaction"),
 *       "description" = @Translation("A reference field in the user entity type to update with a reference to the last executed transaction of this type."),
 *       "required" = FALSE,
 *     },
 *     {
 *       "name" = "target_state",
 *       "type" = "list_string",
 *       "title" = @Translation("State"),
 *       "description" = @Translation("The current state from the last executed transaction."),
 *       "required" = FALSE,
 *     },
 *   },
 * )
 */
class WorkflowTransactor extends GenericTransactor {

  /**
   * Generic result code for failed execution.
   */
  const RESULT_ILLEGAL_TRANSITION = -2;

  /**
   * The transactor workflow service.
   *
   * @var \Drupal\transaction_workflow\TransactionWorkflowServiceInterface
   */
  protected $transactionWorkflowService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, EntityStorageInterface $transaction_storage, TransactionServiceInterface $transaction_service, EntityFieldManagerInterface $field_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory, TransactionWorkflowServiceInterface $transaction_workflow_service) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $string_translation,
      $transaction_storage,
      $transaction_service,
      $field_manager,
      $current_user,
      $config_factory
    );

    $this->transactionWorkflowService = $transaction_workflow_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('entity_type.manager')->getStorage('transaction'),
      $container->get('transaction'),
      $container->get('entity_field.manager'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('transaction_workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeTransaction(TransactionInterface $transaction, TransactionInterface $last_executed = NULL) {
    if (!parent::executeTransaction($transaction)) {
      return FALSE;
    }

    $settings = $transaction->getType()->getPluginSettings();
    $new_state = $transaction->get($settings['state'])->value;

    if ($last_executed) {
      // Check that the transition exists.
      $current_state = $last_executed->get($settings['state'])->value;
      if (array_search($new_state, $this->transactionWorkflowService->getAllowedTransitions($current_state, $transaction->getType())) === FALSE) {
        // Invalid transition.
        $transaction->setResultCode(WorkflowTransactor::RESULT_ILLEGAL_TRANSITION);
        return FALSE;
      }
    }

    // Update the workflow state in the target entity.
    if (isset($settings['target_state'])
      && ($target_entity = $transaction->getTargetEntity())
      && $target_entity->hasField($settings['target_state'])) {
      $target_entity->get($settings['target_state'])->setValue($transaction->get($settings['state'])->value);
      // Set the property indicating that the target entity was updated on
      // execution.
      $transaction->setProperty(TransactionInterface::PROPERTY_TARGET_ENTITY_UPDATED, TRUE);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionDescription(TransactionInterface $transaction, $langcode = NULL) {
    $transaction_type = $transaction->getType();
    $settings = $transaction_type->getPluginSettings();
    $states = $transaction_type->getThirdPartySetting('transaction_workflow', 'states', []);

    // Transaction state.
    $field = $transaction->get($settings['state']);
    $state_id = $field->value;
    $label = isset($states[$state_id]) ? $states[$state_id] : $state_id;

    $t_options = $langcode ? ['langcode' => $langcode] : [];
    $t_args = ['%state' => $label];
    if ($transaction->isPending()) {
      $description = $this->t('Transition to %state state (pending)', $t_args, $t_options);
    }
    else {
      $description = $this->t('Transition to %state state', $t_args, $t_options);
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultMessage(TransactionInterface $transaction, $langcode = NULL) {
    if (!$result_code = $transaction->getResultCode()) {
      return FALSE;
    }

    $t_options = $langcode ? ['langcode' => $langcode] : [];
    switch ($result_code) {
      case WorkflowTransactor::RESULT_ILLEGAL_TRANSITION:
        $message = $this->t('Illegal workflow transition.', [], $t_options);
        break;

      default:
        $message = parent::getResultMessage($transaction, $langcode);
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionIndications(TransactionInterface $transaction, $langcode = NULL) {
    $transaction_type = $transaction->getType();
    $settings = $transaction_type->getPluginSettings();
    $states = $transaction_type->getThirdPartySetting('transaction_workflow', 'states', []);

    // Transaction state.
    $field = $transaction->get($settings['state']);
    $state_id = $field->value;
    $label = isset($states[$state_id]) ? $states[$state_id] : $state_id;
    
    $t_options = $langcode ? ['langcode' => $langcode] : [];
    $t_args = ['%state' => $label];
    $indication = $this->t('The new workflow state will be %state.', $t_args, $t_options);

    return $indication;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $form_state->getFormObject()->getEntity();
    $transactor_settings = $transaction_type->getPluginSettings();
    $states = $transaction_type->getThirdPartySetting('transaction_workflow', 'states', []);

    // Allowed transitions.
    $default_value = '';
    foreach ($states as $key => $label) {
      $default_value .= $key . '|' . $label . "\n";
    }

    $form['workflow_states'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Workflow states'),
      '#description' => $this->t('The workflow states. Enter one value per line, in the format key|label as usually do in the allowed values settings for text list fields.')
        . $this->t('The entered values will override the current allowed values for the selected state field in the transaction type and in the target entity type if set.'),
      '#required' => TRUE,
      '#rows' => 5,
      // @todo read default value from the current selected field
      '#default_value' => $default_value,
    ];

    if ($transaction_type->isNew()) {
      $form['actions']['submit']['#value'] = $this->t('Save and set transitions');
      $form['actions']['submit']['#submit'][] = [$this, 'transitionsRedirectOnSubmit'];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Redirects just created workflow transactions to its transitions form.
   *
   * @param array $form
   *   The transaction type form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function transitionsRedirectOnSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('transaction_workflow.transitions', ['transaction_type' => $form_state->getFormObject()->getEntity()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (empty($states = $form_state->getValue('workflow_states', ''))) {
      return;
    }

    $keys = [];

    foreach (preg_split("(\r\n?|\n)", $states) as $state) {
      if (empty($state)) {
        continue;
      }

      $parts = explode('|', $state);
      if (count($parts) > 2) {
        $form_state->setErrorByName('workflow_states', $this->t('Invalid syntax.'));
      }
      else {
        $key = $parts[0];

        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
          $form_state->setErrorByName('workflow_states', $this->t('Invalid status identifier %id.', ['%id' => $key]));
        }

        if (isset($keys[$key])) {
          $form_state->setErrorByName('workflow_states', $this->t('Duplicate status identifier %id.', ['%id' => $key]));
        }

        $keys[$key] = $key;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $form_state->getFormObject()->getEntity();
    $transactor_settings = $transaction_type->getPluginSettings();

    // Update the states allowed values in transaction and the target entity type.
    $states = $form_state->getValue('workflow_states', '');
    $allowed_values = [];
    foreach (preg_split("(\r\n?|\n)", $states) as $state) {
      if (empty($state)) {
        continue;
      }

      $parts = explode('|', $state);
      $key = $parts[0];
      $label = isset($parts[1]) ? $parts[1] : $key;
      $allowed_values[$key] = $label;
    }
    $transaction_type->setThirdPartySetting('transaction_workflow', 'states', $allowed_values);

    // Update settings in the transaction and target entity state fields.
    $fields = [
      FieldStorageConfig::loadByName('transaction', $transactor_settings['state']),
      isset($transactor_settings['target_state']) ? FieldStorageConfig::loadByName($transaction_type->getTargetEntityTypeId(), $transactor_settings['target_state']) : NULL
    ];
    foreach ($fields as $field) {
      if ($field) {
        $field->setSetting('allowed_values', [])
          ->setSetting('allowed_values_function', 'transaction_workflow_field_state_allowed_values')
          ->setThirdPartySetting('transaction_workflow', 'transaction_type', $transaction_type->id())
          ->save();
      }
    }

    // @todo unset the allowed_values_function from existing fields no longer used by workflow transaction
  }

}
