<?php

/**
 * @file
 * Contains \Drupal\entity_reference_form\Plugin\Field\FieldWidget\EntityReferenceFormWidget.
 */

namespace Drupal\entity_reference_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\entity_reference_form\BrokenEntityReferenceRegistry;
use Drupal\entity_reference_form\Form\ChildFormState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_form_widget' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_form_widget",
 *   label = @Translation("Form"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceFormWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The default value for drilled down recursion.
   *
   * @see requireToState
   */
  const DEFAULT_MAX_RECURSION_COUNT = ChildFormState::DEFAULT_MAX_RECURSION_COUNT;

  /**
   * The widget defaults
   */
  const DEFAULT_FORM_DISPLAY_NAME = 'default';
  const DEFAULT_FORM_OPERATION = 'default';
  const DEFAULT_CREATE_NEW = TRUE;
  const DEFAULT_DRAGGABLE = TRUE;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $targetStorage;

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $targetBundleStorage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityFormDisplayStorage;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * @var \Drupal\entity_reference_form\BrokenEntityReferenceRegistry
   */
  protected $broken_reference;

  /**
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, BrokenEntityReferenceRegistry $broken_reference, ElementInfoManagerInterface $element_info_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->targetStorage = $entity_type_manager->getStorage($field_definition->getSetting('target_type'));
    $this->entityFormDisplayStorage = $entity_type_manager->getStorage('entity_form_display');
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    if ($this->targetStorage->getEntityType()->hasKey('bundle')) {
      $bundle_entity_type_id = $this->targetStorage->getEntityType()
        ->getBundleEntityType();
      $this->targetBundleStorage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
    }
    $this->broken_reference = $broken_reference;
    $this->elementInfoManager = $element_info_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('broken_entity_reference.registry'),
      $container->get('element_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'target_bundle' => NULL,
      'operation' => [
        'add' => static::DEFAULT_FORM_OPERATION,
        'edit' => static::DEFAULT_FORM_OPERATION
      ],
      'target_defaults' => [],
      'form_display_name' => static::DEFAULT_FORM_DISPLAY_NAME,
      'create_new_default' => static::DEFAULT_CREATE_NEW,
      'item_label' => NULL,
      'draggable' => static::DEFAULT_DRAGGABLE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $entity_type_id = $this->getFieldSetting('target_type');
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $elements['create_new_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('The <em>Create new</em> checkbox will be <b>checked</b> by default'),
      '#default_value' => $this->getSetting('create_new_default'),
    ];

    $elements['draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable items'),
      '#default_value' => $this->getSetting('draggable')
    ];

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = $this->bundleOptions($entity_type);
      $elements['target_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Target bundle'),
        '#options' => $bundle_options,
        '#default_value' => $this->getSetting('target_bundle'),
        '#required' => TRUE,
      ];
    }

    $form_display_names_options = $this->entityDisplayRepository->getFormModeOptions($entity_type_id);
    $elements['form_display_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Form display name'),
      '#options' => $form_display_names_options,
      '#default_value' => $this->getSetting('form_display_name'),
    ];

    if ($entity_type->hasFormClasses()) {
      $operation_options = [];
      foreach (array_keys($entity_type->getHandlerClasses()['form']) as $handler_key) {
        $operation_options[$handler_key] = $this->t(ucfirst($handler_key));
      }
      $elements['operation'] = [
        '#type' => 'details',
        '#title' => $this->t('Form operations'),
        '#open' => TRUE,
      ];
      $elements['operation']['add'] = [
        '#type' => 'select',
        '#title' => $this->t('Add operation'),
        '#options' => $operation_options,
        '#default_value' => $this->getSetting('operation')['add'],
      ];
      $elements['operation']['edit'] = [
        '#type' => 'select',
        '#title' => $this->t('Edit operation'),
        '#options' => $operation_options,
        '#default_value' => $this->getSetting('operation')['edit'],
      ];
    }

    $elements['item_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item label'),
      '#attributes' => [
        'placeholder' => $this->getItemLabel()
      ],
      '#description' => $this->t('The string to be used in the <em>Add new &lt;item&gt;</em> button.<br>Leave empty for the placeholder.'),
      '#default_value' => $this->getSetting('item_label'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('create_new_default')) {
      $summary[] = $this->t('The <em>Create new</em> checkbox will be <b>checked</b> by default.');
    }
    else {
      $summary[] = $this->t('The <em>Create new</em> checkbox will <b>not</b> be checked by default.');
    }

    if ($this->getSetting('draggable')) {
      $summary[] = $this->t('Multiple items will be draggable.');
    }
    else {
      $summary[] = $this->t('Multiple items will <b>not</b> be draggable.');
    }

    if ($target_bundle_id = $this->getSetting('target_bundle')) {
      $bundle = $this->targetBundleStorage->load($target_bundle_id);
      $summary[] = $this->t('Target bundle %bundle.', ['%bundle' => $bundle->label()]);
    }

    $entity_type_id = $this->getFieldSetting('target_type');
    $form_display_names_options = $this->entityDisplayRepository->getFormModeOptions($entity_type_id);
    $summary[] = $this->t('Form display name: %name.', [
      '%name' => $form_display_names_options[$this->getSetting('form_display_name')]
    ]);

    $summary[] = $this->t('Add operation: %operation.', ['%operation' => ucfirst($this->getSetting('operation')['add'])]);
    $summary[] = $this->t('Edit operation: %operation.', ['%operation' => ucfirst($this->getSetting('operation')['edit'])]);

    $summary[] = $this->t('Item label: %label.', ['%label' => $this->getItemLabel()]);

    return $summary;
  }

  /**
   * @param EntityTypeInterface $entity_type
   * @return string[]
   */
  protected function bundleOptions(EntityTypeInterface $entity_type) {
    $bundle_entity_type_id = $entity_type->getBundleEntityType();
    $bundle_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
    $options = [];

    foreach ($bundle_storage->loadMultiple() as $bundle) {
      if ($bundle->id() === $this->fieldDefinition->getTargetBundle()) {
        continue;
      }
      $options[$bundle->id()] = $bundle->label();
    }

    return $options;
  }

  protected function getItemLabel() {
    if (!is_numeric($this->getSetting('item_label')) && empty($this->getSetting('item_label'))) {
      $entity_type = $this->entityTypeManager->getDefinition($this->getFieldSetting('target_type'));
      if ($target_bundle = $this->getSetting('target_bundle')) {
        $bundle_entity_type_id = $entity_type->getBundleEntityType();
        $bundle_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
        $bundle = $bundle_storage->load($target_bundle);
        return $bundle->label();
      }
      else {
        return $entity_type->getLowercaseLabel();
      }
    }
    else {
      return $this->getSetting('item_label');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $items_count = $field_state['items_count'];

        // When the item count is empty but the field is required (or new items
        // need to be added by default) increase the item count.
        if ($items_count === 0 && ($this->fieldDefinition->isRequired() || $this->getSetting('create_new_default'))) {
          $items_count++;
          $field_state['items_count'] = $items_count;
          static::setWidgetState($parents, $field_name, $form_state, $field_state);
        }
        $is_multiple = TRUE;
        break;

      default:
        $items_count = $cardinality;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()
      ->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    $max = $items_count;

    for ($delta=0 ; $delta < $max ; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', [
            '@title' => $title,
            '@number' => $delta + 1
          ]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
          if (!$this->getSetting('draggable')) {
            $element['_weight']['#type'] = 'value';
          }
        }

        $element['enabled'] = [
          '#type' => 'value',
          '#value' => true,
          '#process' => $this->elementInfoManager->getInfoProperty('value', '#process')
        ];
        if ($items[$delta]->isEmpty()) {
          if ($delta > 0 || !$this->fieldDefinition->isRequired()) {
            $checkbox_process = $this->elementInfoManager->getInfoProperty('checkbox', '#process');
            $checkbox_process[] = [$this, 'processEnabled'];
            $element['enabled'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Create this item?'),
              '#attributes' => ['class' => ['field-item-enabled']],
              '#delta' => $delta,
              '#weight' => 99,
              '#default_value' => TRUE,
              '#process' => $checkbox_process
            ];
            $element['#attributes']['class'][] = 'js-form-item';
          }
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()
          ->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => $this->t('Add another %label', [
            '%label' => $this->getItemLabel(),
          ]),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#submit' => [[static::class, 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [static::class, 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    if (!$this->getSetting('draggable')) {
      unset($elements['#theme']);
    }

    return $elements;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param array $complete_form
   * @return array
   */
  public function processEnabled(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $parent_element = &NestedArray::getValue($complete_form, array_slice($element['#array_parents'], 0, -1));
    $enabled_name = $element['#name'];
    $disable_check = [":input[name=\"{$enabled_name}\"]" => ['checked' => FALSE]];
    $enable_check = [":input[name=\"{$enabled_name}\"]" => ['checked' => TRUE]];
    $parent_element['#states']['invisible'] = $disable_check;
    $parent_element['#states']['disabled'] = $disable_check;

    // Prevents drupal from validating the element on its own.
    // Drupal will otherwise recursively check if the child elements of this
    // child_form are empty and set an error of it.
    $this->requireToState($parent_element['target_id'], $enable_check);
    return $element;
  }

  /**
   * Recursively change the required children of $parent_element to use drupal
   * states instead of required: true.
   *
   * @param array $parent_element
   * @param array $enable_check
   * @param int $count
   */
  protected function requireToState(array &$parent_element, array $enable_check, $count = self::DEFAULT_MAX_RECURSION_COUNT) {
    if ($count > 0) {
      if (isset($parent_element['#type']) && isset($parent_element['#required']) && $parent_element['#required']) {
        $parent_element += ['#states' => []];
        $parent_element['#states'] += ['required' => []];
        $parent_element['#states']['required'] += $enable_check;
        $parent_element['#required'] = FALSE;
      }
      foreach (Element::children($parent_element) as $child_key) {
        $this->requireToState($parent_element[$child_key], $enable_check, $count - 1);
      }
    }
    else {
      throw new \LogicException('recursion detected');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the child form.
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $child_forms = NestedArray::getValue($form, $field_state['array_parents']);

    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    /**
     * @var int $delta
     * @var FieldItemInterface $item
     */
    foreach ($items as $delta => $item) {
      // We are changing the #parents attribute and don't want to affect the actual form.
      $child_form = $child_forms[$delta][$item->mainPropertyName()];

      /** @var EntityFormInterface $child_form_object */
      $child_form_object = $child_forms[$delta]['#form_object'];

      $child_form_state = new ChildFormState($form_state, $child_form_object, $child_form);

      // Don't handle skipped values.
      if (!isset($values[$delta]['enabled']) || $values[$delta]['enabled']) {
        $child_form_state->setValues($form_state->getValue($child_form['#parents']) ?: []);
        if (!$item->isEmpty()) {
          $child_entity = $this->getEntityFromField($item);
          $child_form_object->setEntity($child_entity);
        }

        $child_form_state->reduceParents($child_form);
        $child_entity = $child_form_object->buildEntity($child_form, $child_form_state);

        // Validation of the child entity occurs in \Drupal\entity_reference_form\Element\ChildForm::validateElement
        if ($child_entity instanceof FieldableEntityInterface) {
          $child_entity->setValidationRequired(FALSE);
        }

        if ($child_entity->isNew()) {
          // Register the sub-entity in the broken regerences
          $this->broken_reference->register($child_entity, $items->getEntity(), $field_name, $delta);
        }

        // Add the entity to the values array.
        $values[$delta]['entity'] = $child_entity;
        if ($child_entity->isNew()) {
          unset($values[$delta]['target_id']);
        }
        else {
          $values[$delta]['target_id'] = $child_entity->id();
        }

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        $values[$delta]['_original_delta'] = $delta;
        $values[$delta] += [
          '_weight' => $delta
        ];
      }
      else {
        unset($values[$delta]);
      }
    }

    // Account for drag-and-drop reordering if needed.
    if (!$this->handlesMultipleValues()) {
      // Remove the 'value' of the 'add more' button.
      unset($values['add_more']);

      usort($values, function ($a, $b) {
        return SortArray::sortByKeyInt($a, $b, '_weight');
      });
    }

    // Assign the values and remove the empty ones.
    $items->setValue($values);
    $items->filterEmptyItems();

    // Put delta mapping in $form_state, so that flagErrors() can use it.
    foreach ($items as $delta => $item) {
      $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
      unset($item->_original_delta, $item->_weight);
    }
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $parent_form_state) {
    /**
     * Retrieve needed variables.
     *
     * @var FieldItemInterface $item
     *   The field item being processed.
     * @var $field_name
     *   The name of the field being processed
     * @var $child_entity
     *   Either a new entity or the entity that is saved in the field.
     * @var $operation
     *   The form operation that was selected in the widget settings
     */
    $item = $items->get($delta);
    $field_name = $this->fieldDefinition->getName();
    $child_entity = $this->getEntityFromField($item);
    $operation = $this->getOperation($child_entity->isNew() ? 'add' : 'edit');
    $child_form_object = $this->entityTypeManager->getFormObject($this->getFieldSetting('target_type'), $operation);
    $child_form_object = clone $child_form_object;
    $child_form_object->setEntity($child_entity);

    $element['#parents'] = array_merge($form['#parents'], [
      $field_name,
      $delta
    ]);
    $element['#form_display_mode'] = $this->getSetting('form_display_name');
    $element['#submit'] = [[$this, 'submitSaveForm']];
    $element['#skip_handlers'] = [[$this, 'shouldSkip']];
    $element['#field_name'] = $field_name;

    $child_form_state = new ChildFormState($parent_form_state, $child_form_object, $element);

    $element += $child_form_object->buildForm($element, $child_form_state);

    $value = [
      '#type' => 'child_form',
      '#form_object' => $child_form_object,
      '#element_key' => $item->mainPropertyName(),
      $item->mainPropertyName() => $element,
      '#entity' => $child_entity
    ];
    if (!empty($this->getSetting('form_display_name'))) {
      $value['#form_display_mode'] = $this->getSetting('form_display_name');
    }
    return $value;
  }

  /**
   * @param array $form
   * @param \Drupal\entity_reference_form\Form\ChildFormState $form_state
   */
  public function submitSaveForm(array &$form, ChildFormState &$form_state) {
    $field_name = $form['#field_name'];
    $delta = $form['#delta'];
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      $form_object->submitForm($form, $form_state);
      $form_object->save($form, $form_state);
      $parent_form_object = $form_state->getParent()->getFormObject();
      if ($parent_form_object instanceof EntityFormInterface) {
        $parent_entity = $parent_form_object->getEntity();
        if ($parent_entity instanceof FieldableEntityInterface) {
          $parent_entity->get($field_name)
            ->set($delta, $form_object->getEntity());
        }
      }
    }
  }

  /**
   * Returns a single operation from the saved operations array.
   *
   * @param $type
   * @return string
   */
  protected function getOperation($type) {
    $operations = $this->getSetting('operation');
    return $operations[$type];
  }

  /**
   * If the field item contains an entity then return it.
   * If the field item is empty then create a new entity object.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getEntityFromField(FieldItemInterface $item) {
    if ($item->isEmpty()) {
      $entity = $this->targetStorage->create($this->getEntityDefaults());
    }
    else {
      $properties = $item->getProperties(TRUE);
      if (in_array('entity', $properties)) {
        /** @var DataReferenceInterface $entity_property */
        $entity_property = $properties['entity'];
        $entity = $entity_property->getTarget()->getValue();
      }
      else {
        /** @var PrimitiveInterface $target_id_property */
        $target_id_property = $properties['target_id'];
        $target_id = $target_id_property->getCastedValue();
        $entity = $this->targetStorage->load($target_id);
      }
    }
    return $entity;
  }

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function getEntityType() {
    return $this->targetStorage->getEntityType();
  }

  /**
   * The defaults values array for new entities
   *
   * @return array
   *
   * @see getEntityFromField
   */
  protected function getEntityDefaults() {
    $defaults = $this->getSetting('target_defaults');

    if ($this->getEntityType()->hasKey('bundle')) {
      $defaults[$this->getEntityType()
        ->getKey('bundle')] = $this->getSetting('target_bundle');
    }

    return $defaults;
  }

  /**
   * Checks if a sub-form representing a sub-entity should be skipped.
   *
   * @param array $element
   * @param ChildFormState $form_state
   *
   * @return bool
   */
  public function shouldSkip(array &$element, ChildFormState $form_state) {
    $user_input = $form_state->getParent()->getUserInput();
    /** @var EntityInterface $entity */
    $entity = $element['#entity'];
    $key_exist = FALSE;
    $enabled = NestedArray::getValue($user_input, array_merge($element['#parents'], ['enabled']), $key_exist);
    $required = $this->fieldDefinition->isRequired();
    $delta = $element[$element['#element_key']]['#delta'];

    //in case the entity parent is new and the child form is empty ->
    // don't check the child form
    return $entity->isNew() && !$enabled && !($required && $delta === 0);
  }

}
