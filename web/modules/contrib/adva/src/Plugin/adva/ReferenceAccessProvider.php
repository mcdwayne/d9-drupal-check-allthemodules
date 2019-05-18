<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines api layer for field access in AccessProviders plugins.
 */
class ReferenceAccessProvider extends AccessProvider implements ReferenceAccessProviderInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create a new AccessProvider.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Unique plugin id.
   * @param array|mixed $plugin_definition
   *   Plugin instance definition.
   * @param Drupal\adva\Plugin\adva\AccessConsumerInterface $consumer
   *   Associated Access Consumer Instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type manager to get entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $consumer);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get("entity_type.manager")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    $field_values = $this->getReferencedItems($entity);
    $field_configs = $this->configuration['enabled_fields'];
    if (count($field_values)) {
      $grants = [];
      $grant_keys = [];
      foreach ($field_configs as $field_name => $field_config) {
        if (!isset($field_values[$field_name])) {
          continue;
        }
        // If we're here, there values fields on the entity used for access.
        $values = $field_values[$field_name];

        foreach ($values as $value) {

          $grant_view = !empty($field_config['grants']['view']) ? 1 : 0;
          $grant_update = !empty($field_config['grants']['update']) ? 1 : 0;
          $grant_delete = !empty($field_config['grants']['delete']) ? 1 : 0;

          $grant = [
            'realm' => $this->getPluginId() . '_' . $value,
            'gid' => 1,
            'grant_view' => $grant_view,
            'grant_update' => $grant_update,
            'grant_delete' => $grant_delete,
            'priority' => 0,
          ];

          // Filter out duplicate grants.
          $grant_key = implode(':', $grant);
          if (in_array($grant_key, $grant_keys)) {
            continue;
          }
          $grant_keys[] = $grant_key;

          /*
           * If the entity is a node, we will only grant for published content,
           * unless the field has been set to grant for unpublished content.
           */
          $is_node = $entity->getEntityType()->id() === "node";
          $grant_unpublished = $field_config['unpublished'] ?: FALSE;
          $grant_for_current_node = $is_node && ($entity->isPublished() || $grant_unpublished);
          if (!$is_node || $grant_for_current_node) {
            $grants[] = $grant;
          }
        }
        return $grants;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getTargetType() {
    // Implementations should override this method.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getReferenceFields($entity_type_id, $target_type_id) {
    $type_fields = &drupal_static(__METHOD__);
    if (!isset($type_fields[$entity_type_id]) || !isset($type_fields[$entity_type_id][$target_type_id])) {
      $type_fields[$entity_type_id][$target_type_id] = [];
      $entityBundleManager = \Drupal::service('entity_type.bundle.info');

      $bundles = $entityBundleManager->getBundleInfo($entity_type_id);
      foreach (array_keys($bundles) as $bundle) {
        $bundle_fields = self::getBundleReferenceFields($entity_type_id, $bundle, $target_type_id);
        $type_fields[$entity_type_id][$target_type_id] = array_merge($type_fields[$entity_type_id][$target_type_id], $bundle_fields);
      }
    }
    return $type_fields[$entity_type_id][$target_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public static function getBundleReferenceFields($entity_type_id, $bundle, $target_type_id) {
    $bundle_fields = &drupal_static(__METHOD__);
    if (!isset($bundle_fields[$entity_type_id])
      || !isset($bundle_fields[$entity_type_id][$bundle])
      || !isset($bundle_fields[$entity_type_id][$bundle][$target_type_id])) {
      // Create an empty array, to ensure there is a value to return.
      $bundle_fields[$entity_type_id][$bundle][$target_type_id] = [];

      $reference_field_types = [
        'entity_reference',
        'entity_reference_revisions',
        'field_collection',
      ];

      $entityFieldManager = \Drupal::service('entity_field.manager');
      $fields = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
      foreach ($fields as $field_name => $field) {
        $field_type = method_exists($field, 'getType') ? $field->getType() : NULL;
        $target_type = method_exists($field, 'getSetting') ? $field->getSetting('target_type') : NULL;
        if (in_array($field_type, $reference_field_types) && $target_type === $target_type_id) {
          $bundle_fields[$entity_type_id][$bundle][$target_type_id][$field_name] = $field;
        }
      }
    }
    return $bundle_fields[$entity_type_id][$bundle][$target_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBundleFields($entity_type_id, $bundle = NULL) {
    if ($bundle === NULL) {
      $bundle = $entity_type_id;
    }

    $configuration = $this->getConfiguration();
    $field_config = isset($configuration['enabled_fields']) ? $configuration['enabled_fields'] : [];

    $enabled_field_names = array_keys($field_config);
    $bundle_fields = static::getBundleReferenceFields($entity_type_id, $bundle, static::getTargetType());
    $bundle_field_names = array_keys($bundle_fields);

    return array_intersect($bundle_field_names, $enabled_field_names);
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedItems(EntityInterface $entity) {
    static $item_ids = [];

    if (!$entity) {
      return [];
    }

    $id = $entity->id();
    $type = $entity->getEntityType();
    $type_id = $type->id();
    if (!isset($item_ids[$type_id]) || !isset($item_ids[$type_id][$id])) {
      // Ensure there will be a returnable value.
      $item_ids[$type_id][$id] = [];

      // Get list of fields.
      $bundle = $entity->bundle();
      $fields = $this->getEnabledBundleFields($type_id, $bundle);

      // Get values for each field.
      foreach ($fields as $field_name) {

        if (($field = $entity->get($field_name)) && ($items = $field->getValue())) {
          // Filter out empty values from items list.
          $items = array_filter($items);
          $type_key = ($field->getFieldDefinition()->getType() === 'field_collection') ? 'value' : 'target_id';
          $values = array_column($items, $type_key);
          // Filter out null values inserted by forms.
          $item_ids[$type_id][$id][$field_name] = array_filter($values);
        }
      }

      // Filter out any empty lists for a fields.
      $item_ids[$type_id][$id] = array_filter($item_ids[$type_id][$id]);
    }
    return isset($item_ids[$type_id][$id]) ? $item_ids[$type_id][$id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesToType(EntityTypeInterface $entityType) {
    if (!$entityType->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
      return FALSE;
    }

    return (count(static::getReferenceFields($entityType->id(), static::getTargetType())) > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm(array $form, FormStateInterface $form_state) {
    $consumer = $this->getConsumer();
    $entity_type_id = $consumer->getEntityTypeId();
    $configuration = $this->getConfiguration();
    $field_config = isset($configuration['enabled_fields']) ? $configuration['enabled_fields'] : [];

    // Expected return value location in the parent tree.
    $base_parents = [
      'consumers',
      $consumer->getPluginId(),
      'config',
      'providers',
      $this->getPluginId(),
    ];

    // Enabled Field list.
    $form['enabled_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Fields'),
      '#description' => $this->t('Choose the fields to use when checking access.'),
      '#default_value' => array_keys($field_config) ?: [],
      '#parents' => array_merge($base_parents, ['enabled_fields']),
      '#options' => [],
    ];
    $form['field_options'] = [
      '#type' => 'item',
    ];

    // Foreach field, add it to the list above and build the grant selection.
    $available_fields = static::getReferenceFields($entity_type_id, static::getTargetType());
    foreach ($available_fields as $field_name => $field) {
      $form['enabled_fields']['#options'][$field_name] = $field->label();
      // Add a field set for the field if enabled.
      $form['field_options'][$field_name] = [
        '#type' => 'fieldset',
        '#title' => $field->label(),
        '#states' => [
          'visible' => [
            ':input[name="consumers[' . $entity_type_id . '][config][providers][' . $this->getPluginId() . '][enabled_fields][' . $field_name . ']"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
      // Set the enabled grants.
      $form['field_options'][$field_name]['grants'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Operation Grants'),
        '#parents' => array_merge(
          $base_parents,
          [
            'field_options',
            $field_name,
            'grants',
          ]
        ),
        '#options' => [
          'view' => 'View',
          'update' => 'Update',
          'delete' => 'Delete',
        ],
        '#default_value' => isset($field_config[$field_name]['grants']) ? $field_config[$field_name]['grants'] : [],
        '#attributes' => ['class' => ['container-inline']],
      ];
      // If for nodes, let the user authorize access to unpublished by field.
      if ($entity_type_id === 'node') {
        $form['field_options'][$field_name]['unpublished'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Grant access to unpublished content.'),
          '#description' => $this->t('This will permit the above operations on unpublished content.'),
          '#default_value' => isset($field_config[$field_name]['unpublished']) ? $field_config[$field_name]['unpublished'] : FALSE,
          '#parents' => array_merge(
            $base_parents,
            [
              'field_options',
              $field_name,
              'unpublished',
            ]
          ),
        ];
      }
    }
    if (count($form['enabled_fields']['#options']) === 0) {
      $form['enabled_fields']['#access'] = FALSE;
      $entityTypeManager = $this->getEntityTypeManager();
      $context = [
        '%target_type' => $entityTypeManager->getDefinition(static::getTargetType())->getLabel(),
        '%label' => $this->getLabel(),
        '%entity_type' => $entityTypeManager->getDefinition($entity_type_id)->getLabel(),
      ];
      $form['message'] = [
        '#type' => 'item',
        '#title' => $this->t('Enabled Fields: %target_type', $context),
        '#markup' => $this->t('There are no available %target_type reference fields. To use %label, please add a %target_type field to the %entity_type entity.', $context),
      ];
    }
    elseif (count($form['enabled_fields']['#default_value']) > 0) {
      $form['#open'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $field_config = [];
    foreach ($values['enabled_fields'] as $field_name => $field_enabled) {
      if ($field_enabled && isset($values['field_options'][$field_name]['grants'])) {
        $field_config[$field_name]['grants'] = array_filter($values['field_options'][$field_name]['grants']);
        if (isset($values['field_options'][$field_name]['unpublished'])) {
          $field_config[$field_name]['unpublished'] = $values['field_options'][$field_name]['unpublished'];
        }
      }
    }
    $this->configuration['enabled_fields'] = $field_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizedEntityIds($operation, AccountInterface $account) {
    // Provide nothing by default.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    $grants = [];

    // Get the entities the user is granted for, and then build grants.
    $entity_ids = $this->getAuthorizedEntityIds($operation, $account);
    foreach ($entity_ids as $entity_id) {
      $grants[$this->getPluginId() . '_' . $entity_id] = [1];
    }

    if (count($grants)) {
      return $grants;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getHelperMessage(array $definition) {
    $entityType = \Drupal::service('entity_type.manager')->getDefinition(static::getTargetType());
    $context = [
      '%provider' => $definition['label'],
      '%target' => $entityType->getLabel(),
    ];
    $message = \Drupal::translation()->translate('<em>%provider</em> is supported on any field-able entity type.', $context);
    $message .= ' ' . \Drupal::translation()->translate('If you don\'t see the <em>%provider</em> listed about for an expected entity, ensure that there is a <em>%target</em> field on the on the entity type.', $context);
    return $message;
  }

}
