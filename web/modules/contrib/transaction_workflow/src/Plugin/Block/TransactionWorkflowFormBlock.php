<?php

namespace Drupal\transaction_workflow\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\transaction\Entity\TransactionType;
use Drupal\transaction_workflow\Form\TransactionWorkflowTransactionForm;
use Drupal\transaction\TransactionServiceInterface;
use Drupal\transaction_workflow\TransactionWorkflowServiceInterface;

/**
 * Transaction workflow form block.
 *
 * @Block(
 *   id = "transaction_workflow_form_block",
 *   admin_label = @Translation("Transaction workflow"),
 *   category = @Translation("Transaction workflow")
 * )
 */
class TransactionWorkflowFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The transaction service.
   *
   * @var \Drupal\transaction\TransactionServiceInterface
   */
  protected $transactionService;

  /**
   * The transactor workflow service.
   *
   * @var \Drupal\transaction_workflow\TransactionWorkflowServiceInterface
   */
  protected $transactionWorkflowService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TransactionWorkflowFormBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\transaction\TransactionServiceInterface $transaction_service
   *   The transaction service.
   * @param \Drupal\transaction_workflow\TransactionWorkflowServiceInterface $transaction_workflow_service
   *   The transaction workflow service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransactionServiceInterface $transaction_service, TransactionWorkflowServiceInterface $transaction_workflow_service, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, AccountInterface $current_user, EntityDisplayRepositoryInterface $entity_display_repository, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionService = $transaction_service;
    $this->transactionWorkflowService = $transaction_workflow_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transaction'),
      $container->get('transaction_workflow'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('entity_display.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'transaction_type' => '',
      'form_mode' => 'default',
      'submit_label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // create <transaction_type> transaction
    $result = AccessResult::allowedIfHasPermission($account, 'create ' . $this->configuration['transaction_type'] . ' transaction');

    if ($result->isAllowed()) {
      // Verify contextual transaction type and target entity.
      /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
      $transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($this->configuration['transaction_type']);
      $result->addCacheableDependency($transaction_type);
      $request = $this->requestStack->getCurrentRequest();
      $entity = $request->get('target_entity') ? : $request->get($transaction_type->getTargetEntityTypeId());
      $result = $result->andIf(AccessResult::allowedIf($entity && $entity instanceof ContentEntityInterface));

      // Is applicable?
      if ($result->isAllowed()) {
        $result = $result->andIf(AccessResult::allowedIf($transaction_type->isApplicable($entity)))
          ->addCacheableDependency($entity);
      }

      // Check that the current state admit some transition.
      if ($result->isAllowed()
        && $current_state = $this->transactionWorkflowService->getCurrentState($entity, $transaction_type)) {
        $target_states = $this->transactionWorkflowService->getAllowedTransitions($current_state, $transaction_type);
        $result = $result->andIf(AccessResult::allowedIf(!empty($target_states)));
      }
    }

    return $result->cachePerPermissions()->addCacheableDependency($account);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // The transaction type.
    $options = [];
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    foreach ($this->entityTypeManager->getStorage('transaction_type')->loadMultiple() as $transaction_type) {
      if ($transaction_type->getPluginId() == 'transaction_workflow') {
        $options[$transaction_type->id()] = $transaction_type->label();
      }
    }
    if (count($options) > 1) {
      asort($options, SORT_STRING);
    }

    $form['transaction_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transaction workflow'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => !empty($this->configuration['transaction_type']) ? $this->configuration['transaction_type'] : key($options),
    ];

    // Form display mode.
    $options = $this->entityDisplayRepository->getFormModeOptions('transaction');
    $form['form_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Form mode'),
      '#default_value' => $this->configuration['form_mode'],
    ];

    // Submit label.
    $form['submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button label'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => $this->configuration['submit_label'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['transaction_type'] = $form_state->getValue('transaction_type');
    $this->configuration['form_mode'] = $form_state->getValue('form_mode');
    $this->configuration['submit_label'] = $form_state->getValue('submit_label');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form_object = $this->entityTypeManager->getFormObject('transaction', 'transaction_workflow_block');
    $form_object->setEntity($this->entityTypeManager->getStorage('transaction')->create([
      'type' => $this->configuration['transaction_type']
    ]));
    $form_object->setOperation($this->configuration['form_mode']);
    return $this->formBuilder->getForm($form_object, NULL, $this->configuration);
  }

}
