<?php

namespace Drupal\pbf\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Plugin implementation of the 'pbf' field type.
 *
 * @FieldType(
 *   id = "pbf",
 *   label = @Translation("Permissions by field"),
 *   description = @Translation("Permissions by field"),
 *   default_widget = "pbf_widget",
 *   default_formatter = "pbf_formatter_default",
 *   list_class = "\Drupal\pbf\Plugin\Field\PbfFieldItemList",
 * )
 */
class Pbf extends EntityReferenceItem {

  /**
   * The access rights operations.
   *
   * @var array
   */
  protected static $operations = [
    'grant_public' => 'Public',
    'grant_view' => 'Grant View',
    'grant_update' => 'Grant Update',
    'grant_delete' => 'Grant Delete',
  ];

  /**
   * The Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'priority' => 0,
      'user_method' => 'user',
      'synchronized_with' => '',
      'synchronized_from_target' => 0,
      'synchronized_by' => '',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add operations properties.
    foreach (self::$operations as $key => $value) {
      $properties[$key] = DataDefinition::create('boolean')
        ->setLabel(new TranslatableMarkup('@operation access', ['@operation' => $key]));
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    // Add operations columns.
    foreach (self::$operations as $key => $value) {
      $column_value = [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ];

      $schema['columns'][$key] = $column_value;
    }

    return $schema;

  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // We support only user_role, node, taxonomy_term and user.
    $entity_type = \Drupal::entityManager()->getEntityTypeLabels();
    $options_supported = [
      'user_role' => 'user_role',
      'node' => 'node',
      'taxonomy_term' => 'taxonomy_term',
      'user' => 'user',
    ];
    $options = array_intersect_key($entity_type, $options_supported);

    $element['target_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $form_state->getFormObject()->getEntity();
    $entity_type = $field->getTargetEntityTypeId();
    $bundle = $field->getTargetBundle();
    $target_entity_type_id = $field->getSetting('target_type');
    $cardinality = $field->getFieldStorageDefinition()->getCardinality();

    $form['synchronization'] = [
      '#type' => 'details',
      '#title' => $this->t('Synchronization'),
      '#description' => '',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[get_class($this), 'formProcessMergeParent']],
      '#weight' => 10,
    ];
    $form['synchronization']['synchronized_by'] = [
      '#type' => 'value',
      '#value' => $field->getSetting('synchronized_by') ? $field->getSetting('synchronized_by') : '',
    ];

    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      // Only fields attached to user entity type can be synchronized by.
      if ($field->getSetting('synchronized_by') && empty($field->getSetting('synchronized_from_target'))) {
        $form['synchronization']['info'] = [
          '#markup' => $this->t('Field synchronized by @field_id', ['@field_id' => $field->getSetting('synchronized_by')]),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      elseif ($field->getSetting('synchronized_by') && $field->getSetting('synchronized_from_target')) {
        $form['synchronization']['info'] = [
          '#markup' => $this->t('Field synchronized by @field_id and allowed to synchronize it', ['@field_id' => $field->getSetting('synchronized_by')]),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      elseif (empty($field->getSetting('synchronized_by')) && $entity_type == 'user') {
        $form['synchronization']['info'] = [
          '#markup' => $this->t('Field not synchronized'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      elseif ($target_entity_type_id !== 'user') {
        $form['synchronization']['info'] = [
          '#markup' => $this->t('Field which reference user entity type can be synchronized if eligible fields are found.'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
    }
    else {
      $form['synchronization']['info'] = [
        '#markup' => $this->t('Only field with an unlimited cardinality can be synchronized'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    // No need to display theses options that are only relevant on Node entity
    // type.
    if ($entity_type !== 'node') {
      return $form;
    }

    // Priority parameter has been removed in Drupal 8, and will not be used
    // except by using Node access priority Module. See
    // https://www.drupal.org/project/napriority
    $form['priority'] = [
      '#type' => 'details',
      '#title' => $this->t('Permissions priority'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[get_class($this), 'formProcessMergeParent']],
      '#weight' => 1,
    ];
    $form['priority']['priority'] = [
      '#type' => 'number',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('The priority to apply on permissions. 
      If not sure about this, let the default priority to 0. If you have some 
      issues with the permissions set, because you use multiple modules which 
      handle node access, try to increase the priority applied to 
      the permissions. Priority will be only used if the module Node access 
      priority is installed. Permissions with the higher priority will be then 
      used.'),
      '#default_value' => $field->getSetting('priority') ? $field->getSetting('priority') : 0,
    ];

    if ($target_entity_type_id == 'user') {
      $options = [
        'user' => $this->t('Grant access directly to users referenced'),
        'ref_user' => $this->t('Grant access directly to users referenced and 
         grant access to users who reference those users from the field 
         <em>@field_name</em> attached to user entity type',
         ['@field_name' => $field->getName()]),
      ];
      $form['user_method'] = [
        '#type' => 'details',
        '#title' => $this->t('Handle permissions for users'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#process' => [[get_class($this), 'formProcessMergeParent']],
        '#weight' => 2,
      ];
      $form['user_method']['user_method'] = [
        '#type' => 'radios',
        '#title' => $this->t('Choose method for grant access to users'),
        '#options' => $options,
        '#default_value' => $field->getSetting('user_method') ? $field->getSetting('user_method') : 'user',
      ];

      if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // We support for referenced user to synchronize them if they have a Pbf
        // field attached whose target bundle is the current bundle.
        // We get eligible fields to be synchronized.
        $fields = $this->getPbfEligibleFields($target_entity_type_id, $bundle);
        $eligible_fields = [];
        if ($fields) {
          foreach ($fields as $field_id => $field_data) {
            $eligible_fields[$field_id] = $field_data['label'] . ' (' . $field_data['field_name'] . ')';
          }
        }
        $form['synchronization']['synchronized_with'] = [
          '#type' => 'select',
          '#title' => $this->t('Select the field attached to users to synchronize'),
          '#options' => $eligible_fields,
          "#empty_option" => $this->t('No synchronization'),
          '#default_value' => $field->getSetting('synchronized_with') ? $field->getSetting('synchronized_with') : [],
        ];

        $form['synchronization']['synchronized_from_target'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow targeted field from users to synchronize this field'),
          '#default_value' => $field->getSetting('synchronized_from_target') ? $field->getSetting('synchronized_from_target') : 0,
          '#return_value' => (int) 1,
          '#empty' => 0,
          '#states' => [
            'invisible' => [
              'select[data-drupal-selector="edit-settings-synchronized-with"]' => ['value' => ''],
            ],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Return the pbf fields eligible to be synchronized.
   *
   * Eligible fields we can synchronize are those whose target bundle match
   * the bundle of the current field.
   *
   * @return array
   *   The list pbf fields
   */
  private function getPbfEligibleFields($entity_type_id, $target_bundle) {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('pbf');
    if (!isset($fields[$entity_type_id])) {
      return [];
    }
    $return = [];
    foreach ($fields[$entity_type_id] as $field_name => $field_data) {
      foreach ($field_data['bundles'] as $bundle) {
        $instance = $this->entityFieldManager
          ->getFieldDefinitions($entity_type_id, $bundle)[$field_name];

        // Only fields with an unlimited cardinality can be synchronized.
        if ($instance instanceof FieldConfigInterface &&
          $instance->getFieldStorageDefinition()->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          $instance_target_bundle = $this->getTargetBundles($instance);
          if (in_array($target_bundle, $instance_target_bundle)) {
            $data['entity_type'] = $instance->getTargetEntityTypeId();
            $data['bundle'] = $instance->getTargetBundle();
            $data['label'] = $instance->getLabel();
            $data['field_name'] = $field_name;
            $return[$instance->id()] = $data;
          }
        }
      }
    }
    return $return;
  }

  /**
   * Get the target bundle of a Field Pbf.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field
   *   The field instance on which we want the target bundles.
   *
   * @return array
   *   The list of target bundles.
   */
  private function getTargetBundles(FieldConfigInterface $field) {
    $target_bundles = [];
    $settings = $field->getSettings();
    if (isset($settings['handler_settings']['target_bundles'])) {
      $target_bundles = $settings['handler_settings']['target_bundles'];
    }
    return $target_bundles;
  }

  /**
   * Return the list of operations available.
   *
   * @return array
   *   The list of operations.
   */
  public function getOperations() {
    $operations = [
      'grant_public' => $this->t('Public'),
      'grant_view' => $this->t('Grant View'),
      'grant_update' => $this->t('Grant Update'),
      'grant_delete' => $this->t('Grant Delete'),
    ];
    return $operations;
  }

  /**
   * Check if the field is tag as public.
   *
   * @return bool
   *   The field is tag or not as public.
   */
  public function isPublic() {
    if ($this->grant_public) {
      return TRUE;
    }
    return FALSE;
  }

}
