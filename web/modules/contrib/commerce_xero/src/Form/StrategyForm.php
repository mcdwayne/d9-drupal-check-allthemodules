<?php

namespace Drupal\commerce_xero\Form;

use Drupal\commerce_xero\CommerceXeroDataTypeManager;
use Drupal\commerce_xero\CommerceXeroProcessorManager;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\xero\XeroQueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base form for modifying Commerce Xero strategy entities.
 */
class StrategyForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The Xero Query Factory service.
   *
   * @var \Drupal\xero\XeroQueryFactory
   */
  protected $queryFactory;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The commerce_xero cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The commerce_xero data type plugin manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroDataTypeManager
   */
  protected $dataTypeManager;

  /**
   * The commerce_xero processor plugin manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroProcessorManager
   */
  protected $processorManager;

  /**
   * The commerce_xero strategy configuration entity.
   *
   * @var \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface
   */
  protected $entity;

  /**
   * The cache expiration time for the commerce_xero account cache.
   *
   * This defaults to seven days.
   *
   * @var int
   */
  static public $cacheExpiration = 604800;

  /**
   * Initialize method.
   *
   * @param \Drupal\xero\XeroQueryFactory $queryFactory
   *   The xero.query service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.xero_query service.
   * @param \Drupal\commerce_xero\CommerceXeroDataTypeManager $dataTypeManager
   *   The commerce_xero_data_type.manager service.
   * @param \Drupal\commerce_xero\CommerceXeroProcessorManager $processorManager
   *   The commerce_xero_processor.manager service.
   */
  public function __construct(XeroQueryFactory $queryFactory, EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache, CommerceXeroDataTypeManager $dataTypeManager, CommerceXeroProcessorManager $processorManager) {
    $this->queryFactory = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
    $this->dataTypeManager = $dataTypeManager;
    $this->processorManager = $processorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xero.query.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache.xero_query'),
      $container->get('commerce_xero_data_type.manager'),
      $container->get('commerce_xero_processor.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_xero\Entity\CommerceXeroStrategy $strategy */
    $strategy = $this->getEntity();
    $bank_accounts = [];
    $accounts = $bank_accounts;
    $data_types = $bank_accounts;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $strategy->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#default_value' => $strategy->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
        'label' => $this->t('ID'),
      ],
      '#disabled' => !$strategy->isNew(),
    ];

    try {
      // Gets the bank and revenue accounts.
      $bank_accounts = $this->getAccountOptions();
      $accounts = $this->getAccountOptions(FALSE);
      $processors = $this->processorManager->getDefinitions();

      foreach ($this->dataTypeManager->getDefinitions() as $plugin_id => $definition) {
        $data_types[$plugin_id] = $definition['label'];
      }
    }
    catch (PluginNotFoundException $e) {
      $this->messenger->addError($this->t('An error occurred creating data from Xero.'));
    }
    catch (MissingDataException $e) {
      $this->messenger->addError($this->t('An error occurred setting Xero account data.'));
    }

    // Sets the bank account and account options if an error occurred above.
    $bank_accounts = isset($bank_accounts) ? $bank_accounts : [];
    $accounts = isset($accounts) ? $accounts : [];
    $processors = isset($processors) ? $processors : [];

    $form['bank_account'] = [
      '#type' => 'select',
      '#title' => $this->t('Bank Account'),
      '#options' => $bank_accounts,
      '#default_value' => $strategy->get('bank_account'),
      '#required' => TRUE,
    ];

    $form['revenue_account'] = [
      '#type' => 'select',
      '#title' => $this->t('Revenue Account'),
      '#options' => $accounts,
      '#default_value' => $strategy->get('revenue_account'),
      '#required' => TRUE,
    ];

    $form['xero_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Xero type'),
      '#description' => $this->t('Choose the Xero data type to use for this strategy such as Invoice, Bank Transaction, Order, Invoice-Payment.'),
      '#options' => $data_types,
      '#default_value' => $strategy->get('xero_type'),
      '#required' => TRUE,
    ];

    try {
      $gateways = $this->entityTypeManager
        ->getStorage('commerce_payment_gateway')
        ->loadMultiple();
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('An error occurred loading Commerce Payment gateways.'));
      $gateways = [];
    }
    $gateway_options = [];
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    foreach ($gateways as $id => $gateway) {
      $gateway_options[$gateway->id()] = $gateway->label();
    }

    $form['payment_gateway'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment Gateway'),
      '#description' => $this->t('Choose the Commerce Payment Gateway to associate with this strategy.'),
      '#options' => $gateway_options,
      '#default_value' => $strategy->get('payment_gateway'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        '0' => $this->t('Disabled'),
        '1' => $this->t('Enabled'),
      ],
      '#default_value' => $strategy->get('status'),
    ];

    // Processor status.
    $form['processors']['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Processors'),
    ];

    $form['processor_settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="processor-settings-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    // Processor order (tabledrag).
    $form['processor_settings']['order'] = [
      '#type' => 'table',
      '#attributes' => ['id' => 'processor-order'],
      '#title' => $this->t('Processor order'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'processor-order-weight',
        ],
      ],
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];

    // Processor settings.
    $form['processor_settings']['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Processor settings'),
    ];

    $this->setProcessors($form_state);

    foreach ($processors as $name => $processor_definition) {
      $info = $this->entity->getEnabledPlugin($name);
      $enabled = $info !== FALSE;
      $configuration = [
        'settings' => $enabled && !empty($info['settings']) ? $info['settings'] : [],
      ];

      /* @var $processor \Drupal\commerce_xero\CommerceXeroProcessorPluginInterface */
      $processor = $this->processorManager->createInstance($name, $configuration);
      $weight = $this->entity->getPluginWeight($processor);
      $form['processors']['status'][$name] = [
        '#type' => 'checkbox',
        '#title' => $processor_definition['label'],
        '#default_value' => $enabled || $processor_definition['required'] ? 1 : 0,
        '#parents' => ['processors', $name, 'status'],
        '#weight' => $weight,
        '#disabled' => $processor_definition['required'],
        '#ajax' => [
          'callback' => [$this, 'onStatusChanged'],
          'event' => 'change',
          'effect' => 'fade',
          'speed' => 'fast',
          'wrapper' => 'processor-settings-wrapper',
        ],
      ];

      if ($enabled) {
        $form['processor_settings']['order'][$name]['#attributes']['class'][] = 'draggable';
        $form['processor_settings']['order'][$name]['#weight'] = $weight;
        $form['processor_settings']['order'][$name]['processor'] = [
          '#markup' => $processor_definition['label'],
        ];
        $form['processor_settings']['order'][$name]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $processor_definition['label']]),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => $weight,
          '#parents' => ['processors', $name, 'weight'],
          '#attributes' => ['class' => ['processor-order-weight']],
        ];

        // Retrieve the settings form of the processor plugin.
        $settings_form = [
          '#parents' => ['processors', $name, 'settings'],
          '#tree' => TRUE,
        ];
        $settings_form = $processor->settingsForm($settings_form, $form_state);
        if (!empty($settings_form)) {
          $form['processor_settings']['settings'][$name] = [
            '#type' => 'details',
            '#title' => $processor_definition['label'],
            '#open' => TRUE,
            '#weight' => $weight,
            '#parents' => ['processors', $name, 'settings'],
            '#group' => 'processor_settings',
          ];
          $form['processor_settings']['settings'][$name] += $settings_form;
        }
      }
    }

    $form = parent::buildForm($form, $form_state);

    $form['#entity_builders'][] = [$this, 'mergePluginSettings'];

    return $form;
  }

  /**
   * Checks for existing strategy by entity ID.
   *
   * @param string $entity_id
   *   The entity ID.
   * @param array $element
   *   The form array element that requests validation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return bool
   *   TRUE if the entity exists by ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    $entity = $this->entityTypeManager
      ->getStorage('commerce_xero_strategy')
      ->load($entity_id);
    return $entity !== NULL;
  }

  /**
   * Ajax callback when processor status changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @return array
   *   Return the form element that should be modified.
   */
  public function onStatusChanged(array $form, FormStateInterface $formState) {
    $this->setProcessors($formState);
    return $form['processor_settings'];
  }

  /**
   * Sets the enabled processors and settings on the entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  protected function setProcessors(FormStateInterface $formState) {
    $processors = $formState->getValue('processors');
    $plugins = [];

    if (!empty($processors)) {
      foreach ($processors as $name => $info) {
        if ($info['status']) {
          $plugins[] = [
            'name' => $name,
            'settings' => isset($info['settings']) ? $info['settings'] : [],
          ];
        }
      }
      $this->entity->set('plugins', $plugins);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $strategy = $this->getEntity();

    try {
      $status = $strategy->save();

      if ($status === SAVED_UPDATED) {
        \drupal_set_message($this->t('Successfully updated strategy %name', ['%name' => $strategy->label()]));
      }
      else {
        \drupal_set_message($this->t('Successfully created strategy %name', ['%name' => $strategy->label()]));
      }

      $form_state->setRedirect('entity.commerce_xero_strategy.list');
    }
    catch (\Exception $e) {
      $this->logger('commerce_xero')->error($e->getMessage());
      \drupal_set_message($this->t('An error occurred while saving the strategy.'), 'error');
    }
  }

  /**
   * Get bank or revenue accounts from Xero.
   *
   * @param bool $bank
   *   Include or exclude bank accounts.
   *
   * @return array
   *   A list of xero accounts keyed by account code.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getAccountOptions($bank = TRUE) {
    $account_options = [];
    $operator = $bank ? '==' : '!=';
    $cache_key = $bank ? 'commerce_xero_bank_accounts' : 'commerce_xero_accounts';

    if ($cached = $this->cache->get($cache_key)) {
      // Data is cached.
      $accounts = $cached->data;
    }
    else {
      // Get bank account options from Xero API.
      $query = $this->queryFactory->get();

      $query
        ->setFormat('xml')
        ->setType('xero_account')
        ->addCondition('Type', 'BANK', $operator)
        ->setMethod('get');
      $accounts = $query->execute();

      if ($accounts) {
        $this->cache->set($cache_key, $accounts, self::$cacheExpiration, ['xero_account']);
      }
    }

    if ($accounts) {
      /** @var \Drupal\xero\Plugin\DataType\Account $account */
      foreach ($accounts as $key => $account) {
        $code = $account->get('Code')->getValue();
        $name = $account->get('Name')->getValue();
        $account_options[$code] = $name;
      }
    }
    return $account_options;
  }

  /**
   * Merge plugin settings with the entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The strategy entity.
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function mergePluginSettings($entity_type, CommerceXeroStrategyInterface $strategy, array &$form, FormStateInterface $formState) {
    $processors = $formState->getValue('processors', []);
    $plugins = [];

    if (!empty($processors)) {
      foreach ($processors as $name => $values) {
        if ($values['status']) {
          $plugins[] = [
            'name' => $name,
            'settings' => isset($values['settings']) ? $values['settings'] : [],
          ];
        }
      }

      // Sort the plugins by the weight.
      uasort($plugins, function ($a, $b) use ($formState) {
        $aWeight = $formState->getValue('processors[' . $a['name'] . '][weight]', 0);
        $bWeight = $formState->getValue('processors[' . $b['name'] . '][weight]', 0);

        if ($aWeight > $bWeight) {
          return 1;
        }
        elseif ($aWeight < $bWeight) {
          return -1;
        }
        return 0;
      });
    }

    $strategy->set('plugins', $plugins);
  }

}
