<?php

namespace Drupal\entity_switcher\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Plugin implementation of the 'switcher_reference' field type.
 *
 * @FieldType(
 *   id = "switcher_reference",
 *   label = @Translation("Switcher"),
 *   description = @Translation("Stores two entity references to toggle between then and other more with the formatter settings."),
 *   default_widget = "switcher_reference_autocomplete_widget",
 *   default_formatter = "switcher_reference_formatter",
 *   list_class = "\Drupal\entity_switcher\SwitcherReferenceFieldItemList"
 * )
 */
class SwitcherReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type_data_off' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'target_type_data_on' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'target_type_switcher' => 'entity_switcher_setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'handler_data_off' => 'default',
      'handler_settings_data_off' => [],
      'handler_data_on' => 'default',
      'handler_settings_data_on' => [],
      'handler_switcher' => 'default:entity_switcher_setting',
      'handler_settings_switcher' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();

    $properties = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $target_type_info = \Drupal::entityTypeManager()->getDefinition($settings['target_type_' . $item]);

      $target_id_data_type = 'string';
      if ($target_type_info->entityClassImplements(FieldableEntityInterface::class)) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $id_definition */
        $id_definition = \Drupal::service('entity_field.manager')
          ->getBaseFieldDefinitions($settings['target_type_' . $item])[$target_type_info->getKey('id')];
        if ($id_definition->getType() === 'integer') {
          $target_id_data_type = 'integer';
        }
      }

      if ($target_id_data_type === 'integer') {
        $target_id_definition = DataReferenceTargetDefinition::create('integer')
          ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]))
          ->setSetting('unsigned', TRUE);
      }
      else {
        $target_id_definition = DataReferenceTargetDefinition::create('string')
          ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]));
      }
      $target_id_definition->setRequired(TRUE);
      $properties[$item . '_id'] = $target_id_definition;

      $properties[$item] = DataReferenceDefinition::create('entity')
        ->setLabel($target_type_info->getLabel())
        ->setDescription(new TranslatableMarkup('The referenced @type entity', ['@type' => $item]))
        // The entity object is computed out of the entity ID.
        ->setComputed(TRUE)
        ->setReadOnly(FALSE)
        ->setTargetDefinition(EntityDataDefinition::create($settings['target_type_' . $item]))
        // We can add a constraint for the target entity type. The list of
        // referenceable bundles is a field setting, so the corresponding
        // constraint is added dynamically in ::getConstraints().
        ->addConstraint('EntityType', $settings['target_type_' . $item]);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $target_type = $field_definition->getSetting('target_type_' . $item);
      $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);
      $properties = static::propertyDefinitions($field_definition)[$item . '_id'];
      if ($target_type_info->entityClassImplements(FieldableEntityInterface::class) && $properties->getDataType() === 'integer') {
        $columns[$item . '_id'] = [
          'description' => 'The ID of the target '. $item . ' entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ];
      }
      else {
        $columns[$item . '_id'] = [
          'description' => 'The ID of the target '. $item . ' entity.',
          'type' => 'varchar_ascii',
          // If the target entities act as bundles for another entity type,
          // their IDs should not exceed the maximum length for bundles.
          'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
        ];
      }
    }

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'data_off_id' => ['data_off_id'],
        'data_on_id' => ['data_on_id'],
        'switcher_id' => ['switcher_id'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    FieldItemBase::setValue($values, FALSE);

    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      // Support setting the field item with only one property, but make sure
      // values stay in sync if only property is passed.
      // NULL is a valid value, so we use array_key_exists().
      if (is_array($values) && array_key_exists($item. '_id', $values) && !isset($values[$item])) {
        $this->onChange($item . '_id', FALSE);
      }
      elseif (is_array($values) && !array_key_exists($item . '_id', $values) && isset($values[$item])) {
        $this->onChange($item, FALSE);
      }
      elseif (is_array($values) && array_key_exists($item . '_id', $values) && isset($values[$item])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_id will be different, due to the new entity
        // marker.
        $entity_id = $this->get($item)->getTargetIdentifier();
        // If the entity has been saved and we're trying to set both the
        // target_id and the entity values with a non-null target ID, then the
        // value for target_id should match the ID of the entity value. The
        // entity ID as returned by $entity->id() might be a string, but the
        // provided target_id might be an integer - therefore we have to do a
        // non-strict comparison.
        if (!$this->{$item}->isNew() && $values[$item . '_id'] !== NULL && ($entity_id != $values[$item . '_id'])) {
          throw new \InvalidArgumentException('The target id and @item passed to the entity reference item do not match.', ['@item' => $item]);
        }
      }
    }

    // Notify the parent if necessary.
    if ($notify && $this->parent) {
      $this->parent->onChange($this->getName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = Map::getValue();

    // If there is an unsaved entity, return it as part of the field item values
    // to ensure idempotency of getValue() / setValue().
    if ($this->hasNewEntity()) {
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        if ($this->{$item}->isNew()) {
          $values[$item] = $this->{$item};
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      // Make sure that the target ID and the target property stay in sync.
      if ($property_name == $item) {
        $property = $this->get($item);
        $target_id = $property->isTargetNew() ? NULL : $property->getTargetIdentifier();
        $this->writePropertyValue($item . '_id', $target_id);
      }
      elseif ($property_name == $item . '_id') {
        $this->writePropertyValue($item, $this->{$item . '_id'});
      }
    }

    Map::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->data_off_id !== NULL && $this->data_on_id !== NULL && $this->switcher_id !== NULL) {
      return FALSE;
    }

    if (($this->data_off && $this->data_off instanceof EntityInterface) &&
      ($this->data_on && $this->data_on instanceof EntityInterface) &&
      ($this->switcher && $this->switcher instanceof EntityInterface)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->hasNewEntity()) {
      // Save the entity if it has not already been saved by some other code.
      foreach (['data_off', 'data_on', 'switcher'] as $item) {
        if ($this->{$item}->isNew()) {
          $this->{$item}->save();
        }

        // Make sure the parent knows we are updating this property so it can
        // react properly.
        $this->{$item . '_id'} = $this->{$item}->id();

        if (!$this->isEmpty() && $this->{$item . '_id'} === NULL) {
          $this->{$item . '_id'} = $this->{$item}->id();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');

    $values = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      // Instead of calling $manager->getSelectionHandler($field_definition)
      // replicate the behavior to be able to override the sorting settings.
      $options['target_type_' . $item] = $field_definition->getFieldStorageDefinition()->getSetting('target_type_' . $item);
      $options['handler_' . $item] = $field_definition->getSetting('handler_' . $item);
      $options['handler_settings_' . $item] = $field_definition->getSetting('handler_settings_' . $item) ?: [];
      $options[$item] = NULL;

      $entity_type = \Drupal::entityTypeManager()->getDefinition($options['target_type_' . $item]);
      $options['handler_settings_' . $item]['sort'] = [
        'field' => $entity_type->getKey('id'),
        'direction' => 'DESC',
      ];
      $selection_handler = $manager->getInstance($options);

      // Select a random number of references between the last 50 referenceable
      // entities created.
      if ($referenceable = $selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 50)) {
        $group = array_rand($referenceable);
        $values[$item . '_id'] = array_rand($referenceable[$group]);
      }
    }

    if (count($values)) {
      return $values;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $targets = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);

    $element['target_type_data_off'] = [
      '#type' => 'select',
      '#title' => t('Type of data_off to reference'),
      '#options' => $targets,
      '#default_value' => $this->getSetting('target_type_data_off'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];
    $element['target_type_data_on'] = [
      '#type' => 'select',
      '#title' => t('Type of data_on to reference'),
      '#options' => $targets,
      '#default_value' => $this->getSetting('target_type_data_on'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];
    $element['target_type_switcher'] = [
      '#type' => 'value',
      '#value' => $this->getSetting('target_type_switcher'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $form_state->getFormObject()->getEntity();

    $form = [
      '#type' => 'container',
      '#process' => [[get_class($this), 'fieldSettingsAjaxProcess']],
      '#element_validate' => [[get_class($this), 'fieldSettingsFormValidate']],

    ];

    foreach (['data_off', 'data_on'] as $item) {
      // Get all selection plugins for this entity type.
      $selection_plugins = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionGroups($this->getSetting('target_type_' . $item));
      $handlers_options = [];
      foreach (array_keys($selection_plugins) as $selection_group_id) {
        if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
          $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
        }
        elseif (array_key_exists($selection_group_id . ':' . $this->getSetting('target_type_' . $item), $selection_plugins[$selection_group_id])) {
          $selection_group_plugin = $selection_group_id . ':' . $this->getSetting('target_type_' . $item);
          $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
        }
      }

      $form['handler_' . $item] = [
        '#type' => 'details',
        '#title' => t('Reference @entity type', ['@entity' => $item]),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#process' => [[get_class($this), 'formProcessMergeParent']],
      ];
      $form['handler_' . $item]['handler_' . $item] = [
        '#type' => 'select',
        '#title' => t('Reference method'),
        '#options' => $handlers_options,
        '#default_value' => $field->getSetting('handler_' . $item),
        '#required' => TRUE,
        '#ajax' => TRUE,
        '#limit_validation_errors' => [],
      ];
      $form['handler_' . $item]['handler_' . $item . '_submit'] = [
        '#type' => 'submit',
        '#value' => t('Change handler'),
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => ['js-hide'],
        ],
        '#submit' => [[get_class($this), 'settingsAjaxSubmit']],
      ];
      $form['handler_' . $item]['handler_settings_' . $item] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['entity_reference-settings']],
      ];
      $options = [
        'target_type' => $field->getFieldStorageDefinition()->getSetting('target_type_' . $item),
        'handler' => $field->getSetting('handler_' . $item),
        'handler_settings' => $field->getSetting('handler_settings_' . $item) ?: [],
        'entity' => NULL,
      ];
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      // @TODO Add support to auto-create.
      $form['handler_' . $item]['handler_settings_' . $item] += $handler->buildConfigurationForm([], $form_state);
      $form['handler_' . $item]['handler_settings_' . $item]['auto_create']['#access'] = FALSE;
      $form['handler_' . $item]['handler_settings_' . $item]['auto_create_bundle']['#access'] = FALSE;
    }

    $form['handler_switcher'] = [
      '#type' => 'value',
      '#value' => 'default:entity_switcher_setting',
    ];
    $form['handler_settings_switcher'] = [
      '#type' => 'value',
      '#value' => [],
    ];

    return $form;
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $form_state->getFormObject()->getEntity();

    foreach (['data_off', 'data_on'] as $item) {
      $options = [
        'target_type' => $field->getFieldStorageDefinition()->getSetting('target_type_' . $item),
        'handler' => $field->getSetting('handler_' . $item),
        'handler_settings' => $field->getSetting('handler_settings_' . $item) ?: [],
        'entity' => NULL,
      ];
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      $handler->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * Determines whether the item holds an unsaved entity.
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return (!$this->isEmpty() && $this->data_off_id === NULL && $this->data_off->isNew()) ||
      (!$this->isEmpty() && $this->target_id === NULL && $this->data_on->isNew()) ||
      (!$this->isEmpty() && $this->switcher_id === NULL && $this->switcher->isNew());
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    $dependencies = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $manager = \Drupal::entityTypeManager();
      $target_entity_type = $manager->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type_' . $item));

      // Depend on default values entity types configurations.
      if ($default_value = $field_definition->getDefaultValueLiteral()) {
        foreach ($default_value as $value) {
          if (is_array($value) && isset($value[$item . '_uuid'])) {
            $entity = \Drupal::service('entity.repository')->loadEntityByUuid($target_entity_type->id(), $value[$item . '_uuid']);
            // If the entity does not exist do not create the dependency.
            // @see \Drupal\Core\Field\EntityReferenceFieldItemList::processDefaultValue()
            if ($entity) {
              $dependencies[$target_entity_type->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
            }
          }
        }
      }

      // Depend on target bundle configurations. Dependencies for 'target_bundles'
      // also covers the 'auto_create_bundle' setting, if any, because its value
      // is included in the 'target_bundles' list.
      $handler = $field_definition->getSetting('handler_settings_' . $item);
      if (!empty($handler['target_bundles'])) {
        if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
          if ($storage = $manager->getStorage($bundle_entity_type_id)) {
            foreach ($storage->loadMultiple($handler['target_bundles']) as $bundle) {
              $dependencies[$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
            }
          }
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(FieldStorageDefinitionInterface $field_definition) {
    $dependencies = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $target_entity_type = \Drupal::entityTypeManager()->getDefinition($field_definition->getSetting('target_type_' . $item));
      $dependencies['module'][] = $target_entity_type->getProvider();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(FieldDefinitionInterface $field_definition, array $dependencies) {
    $changed = FALSE;
    $entity_manager = \Drupal::entityTypeManager();
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $bundles_changed = $changed_item = FALSE;
      $target_entity_type = $entity_manager->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type_' . $item));

      // Try to update the default value config dependency, if possible.
      if ($default_value = $field_definition->getDefaultValueLiteral()) {
        foreach ($default_value as $key => $value) {
          if (is_array($value) && isset($value[$item . '_uuid'])) {
            $entity = \Drupal::service('entity.repository')->loadEntityByUuid($target_entity_type->id(), $value[$item . '_uuid']);
            // @see \Drupal\entity_switcher\SwitcherReferenceFieldItemList::processDefaultValue()
            if ($entity && isset($dependencies[$entity->getConfigDependencyKey()][$entity->getConfigDependencyName()])) {
              unset($default_value[$key][$item]);
              $changed = $changed_item = TRUE;
            }
          }
        }
        if ($changed_item) {
          $field_definition->setDefaultValue($default_value);
        }
      }

      // Update the 'target_bundles' handler setting if a bundle config dependency
      // has been removed.
      $handler_settings = $field_definition->getSetting('handler_settings_' . $item);
      if (!empty($handler_settings['target_bundles'])) {
        if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
          if ($storage = $entity_manager->getStorage($bundle_entity_type_id)) {
            foreach ($storage->loadMultiple($handler_settings['target_bundles']) as $bundle) {
              if (isset($dependencies[$bundle->getConfigDependencyKey()][$bundle->getConfigDependencyName()])) {
                unset($handler_settings['target_bundles'][$bundle->id()]);

                // If this bundle is also used in the 'auto_create_bundle'
                // setting, disable the auto-creation feature completely.
                $auto_create_bundle = !empty($handler_settings['auto_create_bundle']) ? $handler_settings['auto_create_bundle'] : FALSE;
                if ($auto_create_bundle && $auto_create_bundle == $bundle->id()) {
                  $handler_settings['auto_create'] = NULL;
                  $handler_settings['auto_create_bundle'] = NULL;
                }

                $bundles_changed = TRUE;

                // In case we deleted the only target bundle allowed by the field
                // we have to log a critical message because the field will not
                // function correctly anymore.
                if ($handler_settings['target_bundles'] === []) {
                  \Drupal::logger('entity_reference')->critical('The %target_bundle bundle (entity type: %target_entity_type) was deleted. As a result, the %field_name entity reference field (entity_type: %entity_type, bundle: %bundle) no longer has any valid bundle it can reference. The field is not working correctly anymore and has to be adjusted.', [
                    '%target_bundle' => $bundle->label(),
                    '%target_entity_type' => $bundle->getEntityType()->getBundleOf(),
                    '%field_name' => $field_definition->getName(),
                    '%entity_type' => $field_definition->getTargetEntityTypeId(),
                    '%bundle' => $field_definition->getTargetBundle()
                  ]);
                }
              }
            }
          }
        }
      }

      if ($bundles_changed) {
        $field_definition->setSetting('handler_settings_' . $item, $handler_settings);
      }

      $changed |= $bundles_changed;
    }

    return $changed;
  }

}
