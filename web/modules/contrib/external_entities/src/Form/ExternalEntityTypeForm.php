<?php

namespace Drupal\external_entities\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\external_entities\ExternalEntityInterface;
use Drupal\external_entities\Entity\ExternalEntity;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Cache\Cache;
use Drupal\external_entities\Entity\ExternalEntityType;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Form handler for the external entity type add and edit forms.
 */
class ExternalEntityTypeForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The external storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * Constructs an ExternalEntityTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_client_manager
   *   The external storage client manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, MessengerInterface $messenger, DateFormatterInterface $date_formatter, PluginManagerInterface $storage_client_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;
    $this->storageClientManager = $storage_client_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('messenger'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.external_entities.storage_client')
    );
  }

  /**
   * Provides an edit external entity type title callback.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return string
   *   The title for the entity type edit page.
   */
  public function title($entity_type_id = NULL) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $entity_type->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $external_entity_type = $this->getEntity();

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add external entity type');
    }
    else {
      $form['#title'] = $this->t('Edit %label external entity type', ['%label' => $external_entity_type->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $external_entity_type->label(),
      '#description' => $this->t('The human-readable name of this external entity type. This name must be unique.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $external_entity_type->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$external_entity_type->isNew(),
    ];

    $form['label_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name (plural)'),
      '#maxlength' => 255,
      '#default_value' => $external_entity_type->getPluralLabel(),
      '#description' => $this->t('The plural human-readable name of this external entity type.'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t("Description of the external entity type."),
      '#default_value' => $external_entity_type->getDescription(),
    ];

    $form['read_only'] = [
      '#title' => $this->t('Read only'),
      '#type' => 'checkbox',
      '#default_value' => $external_entity_type->isReadOnly(),
      '#description' => $this->t('Whether or not this external entity type is read only.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['field_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Field mappings'),
      '#description' => $this->t('Map Drupal properties and fields with the raw data returned by the storage client. Nested values can be accessed by using the forward slash (/) separator. Use the /*/ syntax for mapping on multivalued fields. If you want to provide a default value instead of a data mapping, use the + character at the beginning of the entered value.'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];

    $base_fields = ExternalEntity::defaultBaseFieldDefinitions();
    $fields = $external_entity_type->getDerivedEntityType()
      ? $this->entityFieldManager->getFieldDefinitions($external_entity_type->getDerivedEntityTypeId(), $external_entity_type->getDerivedEntityTypeId())
      : $base_fields;
    // Filter out fields from the annotation entity.
    foreach ($fields as $key => $field) {
      if (strpos($key, ExternalEntityInterface::ANNOTATION_FIELD_PREFIX) === 0 || $key === ExternalEntityInterface::ANNOTATION_FIELD || $field->isComputed()) {
        unset($fields[$key]);
      }
    }

    foreach ($fields as $field_name => $field) {
      $property_definitions = $field->getFieldStorageDefinition()->getPropertyDefinitions();
      foreach ($property_definitions as $property_name => $property_definition) {
        if (!$property_definition->isReadOnly()) {
          $parents = [$field_name, $property_name];
          $title = $field->getLabel();
          if (count($property_definitions) > 1) {
            $title .= ' » ' . $property_definition->getLabel();
          }

          $element = [
            '#title' => $title,
            '#type' => 'textfield',
            '#default_value' => $external_entity_type->getFieldMapping($field_name, $property_name),
            '#required' => in_array($field_name, ['id', 'title']),
          ];

          NestedArray::setValue($form['field_mappings'], $parents, $element);
        }
      }
    }

    $form['storage'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $this->buildStorageClientSelectForm($form, $form_state);
    $this->buildStorageClientConfigForm($form, $form_state);

    $form['caching'] = [
      '#type' => 'details',
      '#title' => $this->t('Caching'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[ExternalEntityType::CACHE_DISABLED] = '<' . $this->t('no caching') . '>';
    $period[Cache::PERMANENT] = $this->t('Permanent');
    $persistent_cache_max_age = $form_state->getValue(['caching', 'entity_cache_max_age'], ExternalEntityType::CACHE_DISABLED)
        ?: $external_entity_type->getPersistentCacheMaxAge();
    $form['caching']['persistent_cache_max_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Persistent cache maximum age'),
      '#description' => $this->t('The maximum time the external entity can be persistently cached.'),
      '#options' => $period,
      '#default_value' => $persistent_cache_max_age,
    ];

    $form['annotations'] = [
      '#type' => 'details',
      '#title' => $this->t('Annotations'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $annotations_enable = $form_state->getValue(['annotations', 'annotations_enable']);
    if ($annotations_enable === NULL) {
      $annotations_enable = $external_entity_type->isAnnotatable();
    }
    $form['annotations']['annotations_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable annotations on this external entity type'),
      '#description' => $this->t('Annotations allow to enrich external entities with locally stored data (such as Drupal fields). This is achieved by linking local entities with external entities using an entity reference field (on the local entity). Before enabling this option, you have to make sure an entity reference field (to the external entity type) is available on the local entity type and/or bundle.'),
      '#default_value' => $annotations_enable,
      '#ajax' => [
        'callback' => [$this, 'refreshAnnotationSettings'],
        'wrapper' => 'annotation-settings-wrapper',
      ],
    ];

    $form['annotations']['annotation_settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="annotation-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($annotations_enable) {
      $annotation_entity_type_id_options = $this->getAnnotationEntityTypeIdOptions();
      $annotation_entity_type_id = $form_state->getValue([
        'annotations',
        'annotation_settings',
        'annotation_entity_type_id',
      ]) ?: $external_entity_type->getAnnotationEntityTypeId();
      $form['annotations']['annotation_settings']['annotation_entity_type_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#description' => $this->t('Entity type used for annotations.'),
        '#options' => $annotation_entity_type_id_options,
        '#ajax' => [
          'callback' => [$this, 'refreshAnnotationBundleOptions'],
          'wrapper' => 'annotation-bundle-id-wrapper',
        ],
        '#default_value' => $annotation_entity_type_id,
        '#required' => TRUE,
      ];

      $annotation_bundle_id = $form_state->getValue([
        'annotations',
        'annotation_settings',
        'annotation_bundle_id',
      ]) ?: $external_entity_type->getAnnotationBundleId();
      $form['annotations']['annotation_settings']['annotation_bundle_id'] = [
        '#prefix' => '<div id="annotation-bundle-id-wrapper">',
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Bundle used for annotations.'),
        '#options' => $this->getAnnotationBundleIdOptions($annotation_entity_type_id),
        '#ajax' => [
          'callback' => [$this, 'refreshAnnotationFieldOptions'],
          'wrapper' => 'annotation-field-config-id-wrapper',
        ],
        '#default_value' => $annotation_bundle_id,
        '#disabled' => !$annotation_entity_type_id,
        '#required' => TRUE,
      ];

      $annotation_field_name = $form_state->getValue([
        'annotations',
        'annotation_settings',
        'annotation_field_name',
      ]) ?: $external_entity_type->getAnnotationFieldName();
      $annotation_field_options = $this->getAnnotationFieldOptions($annotation_entity_type_id, $annotation_bundle_id);
      $annotation_field_default_value = !empty($annotation_field_options[$annotation_field_name])
        ? $annotation_field_options[$annotation_field_name]
        : NULL;
      $form['annotations']['annotation_settings']['annotation_field_name'] = [
        '#prefix' => '<div id="annotation-field-config-id-wrapper">',
        '#suffix' => '</div></div>',
        '#type' => 'select',
        '#title' => $this->t('Entity reference field'),
        '#description' => $this->t('The entity reference field on the annotation entity which references the external entity.'),
        '#options' => $annotation_field_options,
        '#default_value' => $annotation_field_default_value,
        '#disabled' => !$annotation_entity_type_id || !$annotation_bundle_id,
        '#required' => TRUE,
      ];

      $inherits_annotation_fields = $form_state->getValue([
        'annotations',
        'annotation_settings',
        'inherits_annotation_fields',
      ], FALSE) ?: $external_entity_type->inheritsAnnotationFields();
      $form['annotations']['annotation_settings']['inherits_annotation_fields'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Inherit fields'),
        '#description' => $this->t('When enabled, fields and their values are automatically transferred from the annotation to the external entity. Inherited fields are regular entity fields and can be used as such.'),
        '#default_value' => $inherits_annotation_fields,
      ];
    }

    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * AJAX callback which refreshes the annotation settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form structure.
   */
  public function refreshAnnotationSettings(array $form) {
    return $form['annotations']['annotation_settings'] ?: [];
  }

  /**
   * AJAX callback which refreshes the annotation bundle options.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form structure.
   */
  public function refreshAnnotationBundleOptions(array $form) {
    return [
      'annotations' => [
        'annotation_settings' => [
          'annotation_bundle_id' => $form['annotations']['annotation_settings']['annotation_bundle_id'] ?: [],
          'annotation_field_name' => $form['annotations']['annotation_settings']['annotation_field_name'] ?: [],
        ],
      ],
    ];
  }

  /**
   * AJAX callback which refreshes the annotation field options.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form structure.
   */
  public function refreshAnnotationFieldOptions(array $form) {
    return $form['annotations']['annotation_settings']['annotation_field_name'] ?: [];
  }

  /**
   * Builds the storage client selection configuration.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function buildStorageClientSelectForm(array &$form, FormStateInterface $form_state) {
    $external_entity_type = $this->getEntity();
    $storage_client_options = [];
    $storage_client_descriptions = [];

    $storage_clients = $this->storageClientManager->getDefinitions();
    foreach ($storage_clients as $storage_client_id => $definition) {
      $config = $storage_client_id === $external_entity_type->getStorageClientId() ? $external_entity_type->getStorageClientConfig() : [];
      $storage_client = $this->storageClientManager->createInstance($storage_client_id, $config);
      $storage_client_options[$storage_client_id] = $storage_client->label();
      $storage_client_descriptions[$storage_client_id]['#description'] = $storage_client->getDescription();
    }
    asort($storage_client_options, SORT_NATURAL | SORT_FLAG_CASE);

    if ($storage_client_options) {
      $form['storage']['storage_client_id'] = [
        '#type' => 'radios',
        '#title' => $this->t('Storage client'),
        '#description' => $this->t('Choose a storage client to use for this type.'),
        '#options' => $storage_client_options,
        '#default_value' => $external_entity_type->getStorageClientId(),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [get_class($this), 'buildAjaxStorageClientConfigForm'],
          'wrapper' => 'external-entities-storage-client-config-form',
          'method' => 'replace',
          'effect' => 'fade',
        ],
      ];
      $form['storage']['storage_client_id'] += $storage_client_descriptions;
    }
  }

  /**
   * Builds the storage client-specific configuration form.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function buildStorageClientConfigForm(array &$form, FormStateInterface $form_state) {
    $external_entity_type = $this->getEntity();
    $form['storage']['storage_client_config'] = [];

    $storage_client_id = $form_state->getValue(['storage', 'storage_client_id']) ?: $external_entity_type->getStorageClientId();
    if ($storage_client_id) {
      $storage_client = !$external_entity_type->isNew() && $storage_client_id === $external_entity_type->getStorageClientId()
        ? $external_entity_type->getStorageClient()
        : $this->storageClientManager->createInstance($storage_client_id);
      if ($storage_client && $storage_client instanceof PluginFormInterface) {
        // Attach the storage client plugin configuration form.
        $storage_client_form_state = SubformState::createForSubform($form['storage']['storage_client_config'], $form, $form_state);
        $form['storage']['storage_client_config'] = $storage_client->buildConfigurationForm($form['storage']['storage_client_config'], $storage_client_form_state);

        // Modify the storage client plugin configuration container element.
        $form['storage']['storage_client_config']['#type'] = 'fieldset';
        $form['storage']['storage_client_config']['#title'] = $this->t('Configure %plugin storage client', ['%plugin' => $storage_client->label()]);
        $form['storage']['storage_client_config']['#open'] = TRUE;
      }
    }

    $form['storage']['storage_client_config'] += [
      '#type' => 'container',
    ];
    $form['storage']['storage_client_config']['#attributes']['id'] = 'external-entities-storage-client-config-form';
  }

  /**
   * Handles switching the selected storage client plugin.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public static function buildAjaxStorageClientConfigForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the storage client
    // configuration form based on that. So we just need to return the relevant
    // part of the form
    // here.
    return $form['storage']['storage_client_config'];
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save external entity type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('field_mappings', array_filter(NestedArray::filter($form_state->getValue('field_mappings', []))));
    $storage_client_id = $form_state->getValue(['storage', 'storage_client_id']);
    if ($storage_client_id) {
      $storage_client = $this->storageClientManager->createInstance($form_state->getValue(['storage', 'storage_client_id']));
      if ($storage_client instanceof PluginFormInterface) {
        $storage_client_form_state = SubformState::createForSubform($form['storage']['storage_client_config'], $form, $form_state);
        $storage_client->validateConfigurationForm($form['storage']['storage_client_config'], $storage_client_form_state);
      }
    }
    $form_state->setValue('storage_client_id', $form_state->getValue(['storage', 'storage_client_id']));
    $form_state->setValue('storage_client_config', $form_state->getValue(['storage', 'storage_client_config']));
    $form_state->setValue('persistent_cache_max_age', (int) $form_state->getValue(['caching', 'persistent_cache_max_age'], ExternalEntityType::CACHE_DISABLED));
    $form_state->setValue('annotation_entity_type_id', $form_state->getValue([
      'annotations',
      'annotation_settings',
      'annotation_entity_type_id',
    ], NULL));
    $form_state->setValue('annotation_bundle_id', $form_state->getValue([
      'annotations',
      'annotation_settings',
      'annotation_bundle_id',
    ], NULL));
    $form_state->setValue('annotation_field_name', $form_state->getValue([
      'annotations',
      'annotation_settings',
      'annotation_field_name',
    ], NULL));
    $form_state->setValue('inherits_annotation_fields', $form_state->getValue([
      'annotations',
      'annotation_settings',
      'inherits_annotation_fields',
    ], FALSE));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $external_entity_type = $this->getEntity();
    $status = $external_entity_type->save();

    if ($status) {
      $this->messenger->addMessage($this->t('Saved the %label external entity type.', [
        '%label' => $external_entity_type->label(),
      ]));
    }
    else {
      $this->messenger->addMessage($this->t('The %label external entity type was not saved.', [
        '%label' => $external_entity_type->label(),
      ]));
    }

    $form_state->setRedirect('entity.external_entity_type.collection');
  }

  /**
   * Checks if the entity type already exists.
   *
   * @param string $entity_type_id
   *   The entity type id to check.
   *
   * @return bool
   *   TRUE if already exists, FALSE otherwise.
   */
  public function exists($entity_type_id) {
    if ($this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the form entity.
   *
   * The form entity which has been used for populating form element defaults.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface
   *   The current form entity.
   */
  public function getEntity() {
    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $entity */
    $entity = $this->entity;
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getParameter($entity_type_id) !== NULL) {
      $entity_id = $route_match->getParameter($entity_type_id);
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      if ($entity) {
        return $entity;
      }
    }

    return parent::getEntityFromRouteMatch($route_match, $entity_type_id);
  }

  /**
   * Gets the annotation entity type id options.
   *
   * @return array
   *   Associative array of entity type labels, keyed by their ids.
   */
  public function getAnnotationEntityTypeIdOptions() {
    $options = [];

    $definitions = $this->entityTypeManager->getDefinitions();
    /* @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($definitions as $entity_type_id => $definition) {
      if ($entity_type_id !== 'external_entity' && $definition instanceof ContentEntityType) {
        $options[$entity_type_id] = $definition->getLabel();
      }
    }

    return $options;
  }

  /**
   * Gets the annotation bundle options.
   *
   * @param string $entity_type_id
   *   (optional) The entity type id.
   *
   * @return array
   *   Associative array of bundle labels, keyed by their ids.
   */
  public function getAnnotationBundleIdOptions($entity_type_id = NULL) {
    $options = [];

    if ($entity_type_id) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle) {
        $options[$bundle_id] = $bundle['label'];
      }
    }

    return $options;
  }

  /**
   * Gets the annotation field options.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_id
   *   The bundle id.
   *
   * @return array
   *   Associative array of field labels, keyed by their ids.
   */
  public function getAnnotationFieldOptions($entity_type_id, $bundle_id) {
    $options = [];

    if ($entity_type_id && $bundle_id) {
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
      foreach ($fields as $id => $field) {
        if ($field->getType() === 'entity_reference' && $field->getSetting('target_type') === $this->getEntity()->getDerivedEntityTypeId()) {
          $options[$id] = $field->getLabel() . ' (' . $field->getName() . ')';
        }
      }
    }

    return $options;
  }

}
