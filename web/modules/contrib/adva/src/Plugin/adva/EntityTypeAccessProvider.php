<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines api layer for bundle based access in AccessProviders plugins.
 */
class EntityTypeAccessProvider extends AccessProvider implements EntityTypeAccessProviderInterface {

  /**
   * Indicates the subform is for a non bundled entity type.
   */
  const ENTITY_TYPE_OP = 'ENTITY_TYPE';

  /**
   * Indicates the subform is for a default.
   */
  const ENTITY_DEFAULT_OP = 'DEFAULT';

  /**
   * Indicates the subform is for a bundle.
   */
  const ENTITY_BUNDLE_OP = 'BUNDLE';

  /**
   * Access provider plugin manager.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface
   */
  private $entityTypeManager;

  /**
   * Access provider plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityBundleInfoManager;

  /**
   * Create a new EntityTypeAccessProvider.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Unique plugin id.
   * @param array|mixed $plugin_definition
   *   Plugin instance definition.
   * @param Drupal\adva\Plugin\adva\AccessConsumerInterface $consumer
   *   Associated Access Consumer Instance.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Nanager to get entity storage.
   * @param Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_manager
   *   The Entity Type Bundle Info Nanager to get entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $consumer);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfoManager = $bundle_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $consumer,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    static $record_cache = NULL;
    $entity_type = $entity->getEntityType();

    // If bundled entity.
    if ($entity_type->getBundleEntityType()) {
      $bundle_name = $entity->bundle();
      if (!isset($record_cache)) {
        $record_cache = [];
      }
      // If a bundle is enabled for specific overrides, use those settings.
      if (isset($this->configuration['bundles']['enabled']) && in_array($bundle_name, $this->configuration['bundles']['enabled'])) {
        if (!isset($record_cache['bundles'][$bundle_name])) {
          $record_cache['bundles'][$bundle_name] = $this->getEntityBundleAccessRecords($bundle_name);
        }
        $records = $record_cache['bundles'][$bundle_name];

        // For unpublished nodes, clear disabled op grants.
        if (isset($this->configuration['bundles']['override'][$bundle_name]['operations']) && $entity_type->id() === 'node' && !$entity->isPublished()) {
          $ops = ['view', 'update', 'delete'];
          $bundle_config = $this->configuration['bundles']['override'][$bundle_name]['operations'];
          foreach ($records as &$record) {
            foreach ($ops as $op) {
              if (!(isset($bundle_config[$op]['unpublished']) && $bundle_config[$op]['unpublished'])) {
                $record['grant_' . $op] = 0;
              }
            }
          }
        }
      }
      // For non enabled bundles, use the default config, if one is present.
      elseif (isset($this->configuration['default']['enabled']) && $this->configuration['default']['enabled']) {
        if (!isset($record_cache['default'])) {
          $record_cache['default'] = $this->getEntityDefaultAccessRecords();
        }
        $records = $record_cache['default'];

        // For unpublished nodes, clear disabled op grants.
        if (isset($this->configuration['default']['operations']) && $entity_type->id() === 'node' && !$entity->isPublished()) {
          $ops = ['view', 'update', 'delete'];
          $default_config = $this->configuration['default']['operations'];
          foreach ($records as &$record) {
            foreach ($ops as $op) {
              if (!(isset($default_config[$op]['unpublished']) && $default_config[$op]['unpublished'])) {
                $record['grant_' . $op] = 0;
              }
            }
          }
        }
      }
      // Otherwise, return no records.
      else {
        $records = [];
      }
    }
    // For non bundled entities.
    else {
      if (!isset($record_cache)) {
        $record_cache = $this->getEntityTypeAccessRecords();
      }
      $records = $record_cache;
    }

    if (!empty($records)) {
      foreach ($records as &$record) {
        if (!isset($record['langcode'])) {
          $record['langcode'] = $entity->language()->getId();
        }
      }
    }
    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    // Provide no grants by default.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm(array $form, FormStateInterface $form_state) {
    $consumer = $this->getConsumer();
    $entity_type_id = $consumer->getEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundles = $this->entityBundleInfoManager->getBundleInfo($entity_type_id);
    $operations = $this->getOperations();

    // Expected return value location in the parent tree.
    $base_parents = [
      'consumers',
      $consumer->getPluginId(),
      'config',
      'providers',
      $this->getPluginId(),
    ];
    $context = [
      '%label' => $this->getLabel(),
    ];

    // For bundled entities, display default and per bundle configurations.
    if ($entity_type->getBundleEntityType()) {
      $default_description = $this->t("Default value will be used for any bundles which don't have specific configuration.");
      $default_description .= ' ' . $this->t('If no default configuration is provided, this provider will not grant access to entities with bundle specific configuration below.');
      $form['default'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Default Configuration'),
        '#parents' => array_merge($base_parents, ['default']),
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Provide default configuration'),
          '#description' => $default_description,
          '#parents' => array_merge($base_parents, ['default', 'enabled']),
          '#default_value' => isset($this->configuration['default']['enabled']) ? $this->configuration['default']['enabled'] : FALSE,
        ],
        'operations' => [
          '#type' => 'item',
          '#states' => [
            'visible' => [
              ':input[name="consumers[' . $entity_type_id . '][config][providers][' . $this->getPluginId() . '][default][enabled]"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ],
      ];
      $form['#open'] = isset($this->configuration['default']['enabled']) && $this->configuration['default']['enabled'];
      foreach ($operations as $op) {
        $form['default']['operations'][$op] = [
          '#type' => 'item',
          '#title' => $op,
          '#entity_op' => $op,
          '#entity_op_type' => static::ENTITY_DEFAULT_OP,
          '#parents' => array_merge(
            $base_parents,
            [
              'default',
              'operations',
              $op,
            ]
          ),
        ];
        $subform_state = SubformState::createForSubform($form['default']['operations'][$op], $form, $form_state);
        $form['default']['operations'][$op] = $this->buildOperationConfigForm($form['default']['operations'][$op], $subform_state);
      }

      if (count($bundles)) {
        $bundle_description = $this->t('Bundle specific configuration will override the default configuration set above.', $context);
        $bundle_description .= ' ' . $this->t('If bundle specific configuration is left blank for a bundle, no access records will be generated by %label.', $context);
        $bundle_description .= ' ' . $this->t('This can be used to prevent the above defaults from being used for entities of that bundle.', $context);
        $form['bundles'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Bundle Configuration'),
          'enabled' => [
            '#type' => 'checkboxes',
            '#title' => $this->t('Provide bundle specific configuration for'),
            '#description' => $bundle_description,
            '#options' => [],
            '#default_value' => isset($this->configuration['bundles']['enabled']) ? $this->configuration['bundles']['enabled'] : [],
            '#parents' => array_merge($base_parents, ['bundles', 'enabled']),
          ],
        ];
        $form['#open'] |= isset($this->configuration['bundles']['enabled']) && !empty($this->configuration['bundles']['enabled']);
        foreach ($bundles as $bundle_name => $bundle) {
          $bundle_context = $context + [
            '%bundle' => $bundle['label'],
          ];
          $form['bundles']['enabled']['#options'][$bundle_name] = $bundle['label'];
          $form['bundles']['override'][$bundle_name] = [
            '#type' => 'details',
            '#title' => $this->t('<em>%bundle</em> bundle configuration', $bundle_context),
            '#parents' => array_merge(
              $base_parents,
              [
                'bundles',
                'override',
                $bundle_name,
              ]
            ),
            '#open' => !in_array($bundle_name, $form['bundles']['enabled']['#default_value']),
            '#states' => [
              'visible' => [
                ':input[name="consumers[' . $entity_type_id . '][config][providers][' . $this->getPluginId() . '][bundles][enabled][' . $bundle_name . ']"]' => [
                  'checked' => TRUE,
                ],
              ],
            ],
            'operations' => [
              '#type' => 'item',
            ],
          ];

          foreach ($operations as $op) {
            $form['bundles']['override'][$bundle_name]['operations'][$op] = [
              '#type' => 'item',
              '#title' => $op,
              '#entity_op' => $op,
              '#entity_bundle' => $bundle_name,
              '#entity_op_type' => static::ENTITY_BUNDLE_OP,
              '#parents' => array_merge(
                $base_parents,
                [
                  'bundles',
                  'override',
                  $bundle_name,
                  'operations',
                  $op,
                ]
              ),
            ];
            $subform_state = SubformState::createForSubform($form['bundles']['override'][$bundle_name]['operations'][$op], $form, $form_state);
            $form['bundles']['override'][$bundle_name]['operations'][$op] = $this->buildOperationConfigForm($form['bundles']['override'][$bundle_name]['operations'][$op], $subform_state);
          }
        }
      }
    }
    // Otherwise, just display operations.
    else {
      $form['#open'] = TRUE;
      $form['operations'] = [
        '#type' => 'item',
      ];
      foreach ($operations as $op) {
        $form['operations'][$op] = [
          '#type' => 'item',
          '#title' => $op,
          '#entity_op' => $op,
          '#entity_op_type' => static::ENTITY_TYPE_OP,
          '#parents' => array_merge($base_parents, ['operations', $op]),
        ];
        $subform_state = SubformState::createForSubform($form['operations'][$op], $form, $form_state);
        $form['operations'][$op] = $this->buildOperationConfigForm($form['operations'][$op], $subform_state);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigForm(array &$form, FormStateInterface $form_state) {
    $consumer = $this->getConsumer();
    $entity_type_id = $consumer->getEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundles = $this->entityBundleInfoManager->getBundleInfo($entity_type_id);
    $operations = $this->getOperations();

    $form_values = $form_state->getValues();

    // For bundled entities, unmap the nested values.
    if ($entity_type->getBundleEntityType()) {
      $this->configuration['default']['enabled'] = $form_values['default']['enabled'];
      if ($form_values['default']['enabled']) {
        foreach ($operations as $op) {
          $subform_state = SubformState::createForSubform($form['default']['operations'][$op], $form, $form_state);
          $this->validateOperationConfigForm($form['default']['operations'][$op], $subform_state);
        }
      }
      foreach ($bundles as $bundle_name => $bundle) {
        if (in_array($bundle_name, $form_values['bundles']['enabled'])) {
          foreach ($operations as $op) {
            $subform_state = SubformState::createForSubform($form['bundles']['override'][$bundle_name]['operations'][$op], $form, $form_state);
            $this->validateOperationConfigForm($form['bundles']['override'][$bundle_name]['operations'][$op], $subform_state);
          }
        }
      }
    }
    // For non bundled entities, just check for operation values.
    else {
      foreach ($operations as $op) {
        $subform_state = SubformState::createForSubform($form['operations'][$op], $form, $form_state);
        $this->validateOperationConfigForm($form['operations'][$op], $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $consumer = $this->getConsumer();
    $entity_type_id = $consumer->getEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundles = $this->entityBundleInfoManager->getBundleInfo($entity_type_id);
    $operations = $this->getOperations();

    $form_values = $form_state->getValues();

    // For bundled entities, unmap the nested values.
    if ($entity_type->getBundleEntityType()) {
      // If enabled, update values.
      if ($form_values['default']['enabled']) {
        $this->configuration['default']['enabled'] = $form_values['default']['enabled'];
        foreach ($operations as $op) {
          $subform_state = SubformState::createForSubform($form['default']['operations'][$op], $form, $form_state);
          $this->submitOperationConfigForm($form['default']['operations'][$op], $subform_state);
        }
      }
      if (isset($form_values['bundles']['enabled'])) {
        $this->configuration['bundles']['enabled'] = array_filter($form_values['bundles']['enabled']);
        foreach ($bundles as $bundle_name => $bundle) {
          // Only submit sub form for enabled bundles.
          if (in_array($bundle_name, $form_values['bundles']['enabled'])) {
            foreach ($operations as $op) {
              $subform_state = SubformState::createForSubform($form['bundles']['override'][$bundle_name]['operations'][$op], $form, $form_state);
              $this->submitOperationConfigForm($form['bundles']['override'][$bundle_name]['operations'][$op], $subform_state);
            }
          }
        }
      }
    }
    // For non bundled entities, just check for operation values.
    else {
      foreach ($operations as $op) {
        $subform_state = SubformState::createForSubform($form['operations'][$op], $form, $form_state);
        $this->submitOperationConfigForm($form['operations'][$op], $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperationConfigForm(array $form, FormStateInterface $form_state) {
    $consumer = $this->getConsumer();
    $entity_type_id = $consumer->getEntityTypeId();
    if ($entity_type_id === 'node') {
      $op = $form['#entity_op'];
      $context = [
        '%op' => $op,
      ];

      $defaults = [];
      switch ($form['#entity_op_type']) {
        case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
          $config = isset($this->configuration['default']['operations']) ? $this->configuration['default']['operations'] : [];
          $defaults = isset($config[$op]) ? $config[$op] : [];
          break;

        case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
          if (isset($form['#entity_bundle'])) {
            $bundle = $form['#entity_bundle'];
            $config = isset($this->configuration['bundles']['override'][$bundle]['operations']) ? $this->configuration['bundles']['override'][$bundle]['operations'] : [];
            $defaults = isset($config[$op]) ? $config[$op] : [];
          }
          break;
      }

      $form_parents = $form['#parents'];
      $form['unpublished'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Grant <em>%op</em> on unpublished content.', $context),
        '#description' => $this->t('This will grant <em>%op</em> access on unpublished content based on the configuration above.', $context),
        '#default_value' => isset($defaults['unpublished']) && $defaults['unpublished'],
        '#parents' => array_merge($form_parents, ['unpublished']),
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOperationConfigForm(array $form, FormStateInterface $form_state) {
    // No default action.
  }

  /**
   * {@inheritdoc}
   */
  public function submitOperationConfigForm(array $form, FormStateInterface $form_state) {
    $new_config = array_filter($form_state->getValues());

    if (!isset($form['#entity_op']) || !isset($form['#entity_op_type'])) {
      return;
    }

    switch ($form['#entity_op_type']) {
      case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
        $this->configuration['default']['operations'][$form['#entity_op']]['unpublished'] = isset($new_config['unpublished']);
        break;

      case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
        if (isset($form['#entity_bundle'])) {
          $this->configuration['bundles']['override'][$form['#entity_bundle']]['operations'][$form['#entity_op']]['unpublished'] = isset($new_config['unpublished']);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getHelperMessage(array $definition) {
    $context = [
      '%provider' => $definition['label'],
    ];
    $message = \Drupal::translation()->translate('<em>%provider</em> is supported on any entity type.', $context);
    $message .= ' ' . \Drupal::translation()->translate('<em>%provider</em> allows mapping for basic access based on the entity type and bundle.', $context);
    $message .= ' ' . \Drupal::translation()->translate('For entity types with bundles, access can be configured per bundle.', $context);
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecordsFromConfig(array $config) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeAccessRecords() {
    if (isset($this->configuration['operations'])) {
      return $this->getAccessRecordsFromConfig($this->configuration['operations']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityDefaultAccessRecords() {
    if (isset($this->configuration['default']['operations'])) {
      return $this->getAccessRecordsFromConfig($this->configuration['default']['operations']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleAccessRecords($bundle_name) {
    if (isset($this->configuration['bundles']['override'][$bundle_name]['operations'])) {
      return $this->getAccessRecordsFromConfig($this->configuration['bundles']['override'][$bundle_name]['operations']);
    }
  }

}
