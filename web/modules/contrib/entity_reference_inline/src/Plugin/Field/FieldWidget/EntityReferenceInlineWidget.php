<?php

/**
 * @file
 * Contains \Drupal\entity_reference_inline\Plugin\Field\FieldWidget\EntityReferenceInlineWidget.
 */

namespace Drupal\entity_reference_inline\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \QueryPath;

/**
 * Plugin implementation of the 'entity_reference_inline' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_inline",
 *   label = @Translation("Entity reference inline widget"),
 *   description = @Translation("An inline entity form of the referenced entities."),
 *   entity_deep_serialization = TRUE,
 *   field_types = {
 *     "entity_reference_inline"
 *   }
 * )
 */
class EntityReferenceInlineWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  const NEW_ELEMENT_RETURN_TABLE = 'return_table';

  const NEW_ELEMENT_RETURN_SINGLE_ELEMENT = 'return_single_row';

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form displays to be used a referenced entity bundle.
   *
   * An associative array, keyed by the entity form display id and valued by
   * the corresponding entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface[]
   */
  protected $referencedEntityFormDisplays;

  /**
   * The content translation controller.
   *
   * @var \Drupal\content_translation\Controller\ContentTranslationController
   */
  protected $contentTranslationController;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The referenced entity type.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $referencedEntityType;

  /**
   * A temporary storage of the field items argument of ::extractFormValues().
   *
   * We set this property in ::extractFormValues() before we call the parent in
   * order to able to access the field item list in ::massageFormValues() when
   * it is called from within ::extractFormValues() of the parent class.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $extractFormValuesFieldItemList;

  /**
   * A temporary storage for reused entities inside a form.
   *
   * This property will be used to hold the entity clones when the entity is
   * build from the user input. If inside the same form a reused entity occurs
   * then it will have at all the places the same entity reference, which will
   * be needed by the fields in their preSave to properly update the properties.
   *
   * @var array
   */
  public static $builtEntities = [];

  /**
   * Constructs a EntityReferenceInlineWidget object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\content_translation\Controller\ContentTranslationController
   *   The content translation controller.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   A content translation manager instance.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the entity reference inline form alter hook with.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct($plugin_id, $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              array $third_party_settings,
                              LanguageManagerInterface $language_manager,
                              EntityDisplayRepositoryInterface $entity_display_repository,
                              EntityTypeManagerInterface $entity_type_manager,
                              ContentTranslationController $content_translation_controller,
                              ContentTranslationManagerInterface $content_translation_manager,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info,
                              ModuleHandlerInterface $module_handler,
                              AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->languageManager = $language_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->contentTranslationController = $content_translation_controller;
    $this->contentTranslationManager = $content_translation_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('language_manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('entity_reference_inline.content_translation.controller'),
      $container->get('content_translation.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'form_mode' => 'default',
      'form_modes_bundles' => [],
      'new_element_return_mode' => static::NEW_ELEMENT_RETURN_SINGLE_ELEMENT
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * The only setting we support at the moment is the form mode, which should be
   * used to build the form of the referenced entities.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $bundle_options = $this->getBundleOptions();
    if (empty($bundle_options)) {
      $element['form_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('Form modes'),
        '#default_value' => $this->getSetting('form_mode'),
        '#options' => $this->getFormModeOptions(),
        '#description' => $this->t('Select the form mode to use for entity representation.'),
      ];
    }
    else {
      $element['form_modes_bundles'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Form modes per bundle'),
        '#description' => $this->t('Select the form mode to use for entity representation.'),
      ];
      $form_modes_bundles = $this->getSetting('form_modes_bundles');
      foreach ($bundle_options as $bundle_name => $bundle_label) {
        $element['form_modes_bundles'][$bundle_name] = [
          '#type' => 'select',
          '#title' => $this->t('Form modes of bundle "%bundle_label"', ['%bundle_label' => $bundle_label]),
          '#default_value' => isset($form_modes_bundles[$bundle_name]) ? $form_modes_bundles[$bundle_name] : 'default',
          '#options' => $this->getFormModeOptions($bundle_name),
        ];
      }
    }

    $element['new_element_return_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('New element return modes'),
      '#default_value' => $this->getSetting('new_element_return_mode'),
      '#options' => [
        static::NEW_ELEMENT_RETURN_SINGLE_ELEMENT => $this->t('Return and insert a single table row.'),
        static::NEW_ELEMENT_RETURN_TABLE => $this->t('Return and replace the whole field table.')
      ],
      '#description' => $this->t('Select new element return mode to use when creating new elements.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * We set the widget state by ourselves to prevent appending an empty item and
   * handle the removal of items. Additionally we provide meta information for
   * entity_reference_inline_preprocess_field_multiple_value_form.
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Store field information in $form_state.
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = $this->initializeFieldState($items);
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    else {
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
    }

    $widget_form = parent::form($items, $form, $form_state, $get_delta);

    // Remove field items, removed by the ajax callback "Remove".
    foreach (array_keys($field_state['deltas_removed']) as $delta) {
      unset($widget_form['widget'][$delta]);
    }

    $number_of_rows = $items->count() - count($field_state['deltas_removed']);
    $last_delta = $items->count() - 1;

    // Needed only for the remove button functionality.
    if ($this->isCardinalityUnlimited()) {
      $table_id = $this->getEntityReferenceFieldTableId($form);
      $widget_form['widget'] += [
        '#base_widget' => 'entity_reference_inline',
        '#form_parents' => implode('-', $parents),
        '#is_cardinality_unlimited' => TRUE,
        '#table_id' => $table_id
      ];
      $widget_form['widget']['add_more']['add_more']['#ajax']['table_id'] = $table_id;
      $widget_form['widget']['add_more']['add_more']['#ajax']['number_of_rows'] = $number_of_rows;
      $widget_form['widget']['add_more']['add_more']['#ajax']['last_delta'] = $last_delta;
    }

    $widget_form['#attached']['library'][] = 'entity_reference_inline/base-theme';

    return $widget_form;
  }

  /**
   * Prepares a table id based on the parents and the field name.
   *
   * @param $form
   *   The entity form.
   *
   * @return string
   *   A clean css identifier for the field table.
   */
  protected function getEntityReferenceFieldTableId($form) {
    $table_id_parts = $form['#parents'];
    $table_id_parts[] = $this->fieldDefinition->getName();
    $table_id_parts[] = 'entity-reference-inline-field-table';
    return Html::cleanCssIdentifier(implode('-', $table_id_parts));
  }

  /**
   * Prepares the initial field state.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   An array of the field values. When creating a new entity this may be NULL
   *   or an empty array to use default values.
   *
   * @return array
   *   The initial field state.
   */
  protected function initializeFieldState(FieldItemListInterface $items) {
    return [
      // Do not add an empty field item.
      'items_count' => count($items) - 1,
      'array_parents' => [],
      'deltas_removed' => [],
      'initial_delta_values' => [],
      'new_element_return_mode' => $this->getSetting('new_element_return_mode'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    // If initial_delta_values is emtpty then the addMoreSubmit is not the
    // triggering element.
    if ($field_state['initial_delta_values']) {
      // Determine the number of widgets to display.
      $max = $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED ? $field_state['items_count'] : $cardinality - 1;

      for ($delta = 0; $delta <= $max; $delta++) {
        if (!isset($items[$delta])) {
          $initial_delta_values = isset($field_state['initial_delta_values'][$delta]) ? $field_state['initial_delta_values'][$delta] : [];
          // Add the current form language code as the language of the new
          // entity being created.
          if ($this->getReferencedEntityType()->isTranslatable() && ($langcode_key = $this->getReferencedEntityType()->getKey('langcode'))) {
            if (!isset($initial_delta_values[$langcode_key]) && ($form_langcode = $form_state->getFormObject()->getFormLangcode($form_state))) {
              $initial_delta_values[$langcode_key] = $form_langcode;
            }
          }
          $initial_delta_values = ['entity' => $this->getReferencedEntityStorage()->create($initial_delta_values)];
          $items->appendItem($initial_delta_values);
        }
      }
    }

    $elements = parent::formMultipleElements($items, $form, $form_state);

    // We do not use unique id because this wrapper should not change on each
    // ajax call because it is used in addMoreAjax as a selector and if it
    // changes on each ajax call we we'll have the new name but the DOM will
    // still have the old one and we'll not be able to address it with the
    // returned ajax commands.
    $id_prefix = implode('-', array_merge($parents, [$field_name]));

    if (empty($elements)) {
      $title = $this->fieldDefinition->getLabel();
      $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        // max delta is needed in ::addMoreAjax and if there no elements at the
        // moment we want it to be < 0 as 0 means the first element.
        '#max_delta' => -1,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => $this->t('Add another item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($form['#parents'], [$field_name])],
          '#validate' =>  [],
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
          ],
        ];
      }
    }

    if (isset($elements['add_more'])) {

      $field_name_css = Html::cleanCssIdentifier($this->fieldDefinition->getName());
      $specific_class = "entity-reference-inline-add-more-container-{$field_name_css}";

      // Because of the theme for fields we could only use the add_more key to
      // add the bundle to it and therefore in order for the bundle list to be
      // in front of the add_more button we have to place them in a container
      // and put the bundle list before the add_more key.
      $elements['add_more'] = [
        '#type' => 'container',
        'add_more_bundle' => [],
        'add_more' => $elements['add_more'],
        '#attributes' => ['class' => ['entity-reference-inline-add-more-container', $specific_class]],
      ];

      $elements['add_more']['add_more']['#attributes']['class'][] = 'field-add-more-submit-' . strtr($field_name, ['_' => '-']);
      $elements['add_more']['add_more']['#attributes']['class'][] = 'entity-reference-inline-add-more-submit';

      // We have to place the bundle select under the 'add_more' key because
      // otherwise the template function will (try to) convert it to a table
      // row and will fail as the #row_id is not set on this element.
      // @see template_preprocess_field_multiple_value_form()
      // @see entity_reference_inline_preprocess_field_multiple_value_form()
      $this->addBundleOptions($elements['add_more']);
    }

    if ($this->getSetting('new_element_return_mode') == static::NEW_ELEMENT_RETURN_SINGLE_ELEMENT) {
      // We do not use the replace command but append our elements directly in
      // the table, so there is no need for adding a prefix and defining a
      // wrapper.
      unset($elements['add_more']['#ajax']['wrapper']);
      unset($elements['#prefix']);
      unset($elements['#suffix']);
    }

    $elements['add_more']['#ajax']['effect'] = 'slide';


    // Adding an empty array as #validate so that
    // ContentEntityForm::validateForm is not executed, which if executed will
    // call ContentEntityForm::buildEntity and for each field's widget
    // WidgetBase::extractFormValues will be called so that the parent entity
    // is build from the user input and then the entity is validated. However
    // if the form gets really big validation in ajax calls might only slow
    // down the system. Therefor we turn of the validation for this kind of
    // ajax calls, as the whole form and the entity will be validated when
    // submitting the form.
    if (!isset($elements['add_more']['#validate'])) {
     // $elements['add_more']['#validate'] =  [];
    }

    // Invoke the entity reference inline form_multiple_elements alter hooks.
    $context = [
      'items' => $items,
    ];
    $hooks = [
      'entity_reference_inline_form_multiple_elements',
      'entity_reference_inline_' . $this->getReferencedEntityType()->id() . '_form_multiple_elements',
      'entity_reference_inline_' . $this->getReferencedEntityType()->id() . '_' . $this->getSetting('form_mode') . '_form_multiple_elements',
    ];
    $this->moduleHandler->alter($hooks, $elements, $form_state, $context);

    return $elements;
  }

  /**
   * Sets available bundle options to the add more element.
   */
  protected function addBundleOptions(&$element) {
    if ($bundle_field_name = $this->getReferencedEntityType()->getKey('bundle')) {
      $bundle_options = $this->getBundleOptions();

      // Only show a select list if there is more than one bundle configured
      // to be used on this field.
      if (count($bundle_options) > 1) {
        // Using the bundle field name as a key we do not need to add extra
        // handling for the bundle field when creating the entity from the
        // submitted form values.
        $entity_type_label = $this->getReferencedEntityType()->getLabel();
        $field_name_css = Html::cleanCssIdentifier($this->fieldDefinition->getName());
        $specific_class = "entity-reference-inline-add-more-bundle-{$field_name_css}";
        $element['add_more_bundle'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['entity-reference-inline-add-more-submit-bundle', $specific_class]],
          $bundle_field_name => [
            '#type' => 'select',
            '#options' => $bundle_options,
            '#empty_option' => $this->t('- Select element type to add -'),
            '#entity_type_label' => $entity_type_label,
          ],
        ];
        $element['add_more']['#validate'][] = [static::class, 'addMoreValidateBundle'];
      }
      else {
        // We have to reset the array in order to access any key.
        reset($bundle_options);
        // We do not need a select list if only one bundle is available.
        $element['add_more_bundle'] = [
          '#type' => 'container',
          $bundle_field_name => [
            '#type' => 'value',
            '#value' => key($bundle_options),
          ],

        ];
      }
      $element['add_more']['#bundle_field_name'] = $bundle_field_name;
      $element['add_more']['#allowed_bundles'] = $bundle_options;
    }
  }

  /**
   * Returns the bundle options.
   *
   * @return array
   *   The bundle options, keyed by the bundle machine name, valued by the
   *   bundle label.
   */
  protected function getBundleOptions() {
    $bundle_options = [];
    if ($this->getReferencedEntityType()->hasKey('bundle') && ($handler_settings = $this->fieldDefinition->getSetting('handler_settings')) && !empty($handler_settings['target_bundles'])) {
      // Prepare the bundle options with labels.
      $available_bundles = $this->entityTypeBundleInfo->getBundleInfo($this->getFieldSetting('target_type'));
      // We use array flip in case a base field definition is not using for
      // key and value the referenced bundle but only for value.
      $bundle_options = array_intersect_key($available_bundles, array_flip($handler_settings['target_bundles']));
      array_walk($bundle_options, function (&$bundle_info) {
        $bundle_info = $bundle_info['label'];
      });
    }
    return $bundle_options;
  }

  /**
   * Validates that when adding a new entity a bundle is selected.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addMoreValidateBundle(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $parents[] = 'add_more_bundle';
    $parents[] = $triggering_element['#bundle_field_name'];
    $selected_bundle = $form_state->getValue($parents);

    if (!empty($selected_bundle)) {
      $name = array_shift($parents) . '[' . implode('][', $parents) . ']';
      $form_state->setTemporaryValue('entity_reference_inline_reset_bundle_select', $name);
    }
    else {
      $array_parents = $triggering_element['#array_parents'];
      array_pop($array_parents);
      $array_parents[] = 'add_more_bundle';
      $array_parents[] = $triggering_element['#bundle_field_name'];
      $add_more_bundle_element = NestedArray::getValue($form, $array_parents);
      $form_state->setError($add_more_bundle_element, t('Please select the type of the %entity_type_label to add.', ['%entity_type_label' => $add_more_bundle_element['#entity_type_label']]));
    }
  }

  /**
   * {@inheritdoc}
   *
   * If the element at this delta has been removed by an ajax call, there is no
   * need for it to be completely initialized and as a performance optimization
   * we return a dummy element which is going to be removed in ::form before
   * the widget form is returned.
   *
   * Note: If it is needed to extend from the widget and to alter the form
   * element of an entity then ::formElement() should be overridden and not
   * ::formSingleElement() in order for the changes to be available in the hook
   * invocations.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_state = static::getWidgetState($form['#parents'], $this->fieldDefinition->getName(), $form_state);
    if (isset($field_state['deltas_removed'][$delta])) {
      $element = ['#type' => 'dummy'];
    }
    else {
      $element = parent::formSingleElement($items, $delta, $element, $form, $form_state);

      if ($this->getReferencedEntityType()->isTranslatable()) {
        $referenced_entity = $items[$delta]->entity;
        // If the entity is new it should be already in the correct translation!
        $referenced_entity = $referenced_entity->isNew() ? $referenced_entity : $this->prepareTranslation($referenced_entity, $delta, $form['#parents'], $form_state);
      }
      else {
        $referenced_entity = $items[$delta]->entity;
      }

      $form_display = $this->getFormDisplay($referenced_entity->getEntityTypeId(), $referenced_entity->bundle());

      // Invoke the entity reference inline form alter hooks.
      $context = [
        'entity' => $referenced_entity,
        'form_display' => $form_display,
        'parent_item' => $items[$delta],
        'wrapped_entity_form' => &$element
      ];
      $hooks = [
        'entity_reference_inline_form',
        'entity_reference_inline_' . $referenced_entity->getEntityTypeId() . '_form',
        'entity_reference_inline_' . $referenced_entity->getEntityTypeId() . '_' . $this->getSetting('form_mode') . '_form',
      ];
      $this->moduleHandler->alter($hooks, $element['details_element'], $form_state, $context);
    }
    return $element;
  }

  /**
   * Checks if the provided element is only a dummy one, an replacement for
   * a removed delta element.
   *
   * @param array $element
   *   The element for which to check if it is removed.
   *
   * @return bool
   *   TRUE, if the element is removed, FALSE otherwise.
   */
  protected static function isElementRemoved(array $element) {
    return isset($element['#type']) && ($element['#type'] == 'dummy');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->getReferencedEntityType()->isTranslatable()) {
      $referenced_entity = $items[$delta]->entity;
      // If the entity is new it should be already in the correct translation!
      $referenced_entity = $referenced_entity->isNew() ? $referenced_entity : $this->prepareTranslation($referenced_entity, $delta, $form['#parents'], $form_state);
    }
    else {
      $referenced_entity = $items[$delta]->entity;
    }

    // The parents are not set by the parent class when this function is called,
    // but EntityFormDisplay::buildForm will set them to an empty array if not
    // already present. In order to not break the structure by calling
    // EntityFormDisplay we have to ensure the parents are set correctly.
    $element['#parents'] = array_merge($form['#parents'], [$this->fieldDefinition->getName(), $delta]);
    $form_display = $this->getFormDisplay($referenced_entity->getEntityTypeId(), $referenced_entity->bundle());
    $form_display->buildForm($referenced_entity, $element, $form_state);

    if ($referenced_entity->isNew()) {
      $entity_type = $referenced_entity->getEntityType();
      if ($bundle_field_name = $entity_type->getKey('bundle')) {
        $element[$bundle_field_name] = [
          '#type' => 'value',
          '#value' => $referenced_entity->bundle(),
        ];
      }
    }

    if ($referenced_entity instanceof EntityChangedInterface) {
      // Changed must be sent to the client, for later overwrite error checking.
      // TODO find a better way to include the changed timestamp.
      $element['changed'] = [
        '#type' => 'hidden',
        '#default_value' => $referenced_entity->getChangedTime(),
      ];
    }

    $this->addEntityMetaInformation($referenced_entity, $element);

    // Add attributes to the form element.
    $attributes = [
      'class' => [
        'entity-reference-inline-details',
        'entity-reference-inline-' . str_replace('_', '-', $referenced_entity->getEntityTypeId()),
        'entity-reference-inline-' . str_replace('_', '-', $referenced_entity->getEntityTypeId() . '--' . $referenced_entity->bundle()),
      ],
    ];
    $entity_id = $referenced_entity->id();
    if ($entity_id) {
      $attributes['entity-reference-inline-id'] = $entity_id;
    }

    // Wrap the entity form into details for a better structure of the form.
    $wrapped_entity_form = [
      '#type' => 'details',
      '#title' => $referenced_entity->label(),
      '#open' => TRUE,
      '#attributes' => $attributes,
      'details_element' => &$element,
    ];

    $this->addRemoveButton($wrapped_entity_form, $form);

    return $wrapped_entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    // We don't call the parent as the add more button is wrapped in a
    // container and widget element is two levels up instead of one.

    $button = $form_state->getTriggeringElement();

    // The widget form with all the elements.
    // Go two levels up in the form, to the widgets container.
    $elements = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // ----- parent::addMoreSubmit ---------
    $field_name = $elements['#field_name'];
    $parents = $elements['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    #static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
    // ----- parent::addMoreSubmit ---------

    if (isset($elements['add_more']['add_more_bundle']['#parents'])) {
      $field_state['initial_delta_values'][$field_state['items_count']] = $form_state->getValue($elements['add_more']['add_more_bundle']['#parents']);
    }
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    // Store the previous options which will be used in ::addMoreAjax.
    static::addMorePreserveTemporaryWeightElementPreviousOptions($elements, $form_state);
  }

  /**
   * Retrieves and stores the previous weight options into the form state.
   *
   * A helper method for ::addMoreSubmit which output will be used later in
   * ::addMoreAjax.
   *
   * @param $elements
   *   The elements of which to estimate the weight options.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function addMorePreserveTemporaryWeightElementPreviousOptions($elements, FormStateInterface $form_state) {
    // Store the previous options which will be used in ::addMoreAjax.
    $weight_element_previous_options = [];
    $form_state->set('weight_element_previous_options', []);
    foreach ($elements as $key => $child) {
      if (Element::child($key)) {
        if (!static::isElementRemoved($elements[$key]) && isset($elements[$key]['_weight']['#type']) && $elements[$key]['_weight']['#type'] == 'select') {
          $form_state->setTemporaryValue('weight_element_previous_options', $elements[$key]['_weight']['#options']);
          $weight_element_previous_options = $elements[$key]['_weight']['#options'];
          break;
        }
      }
    }
    $form_state->setTemporaryValue('weight_element_previous_options', $weight_element_previous_options);
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form, to the widgets container.
    $elements = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $elements['#field_name'];
    $parents = $elements['#field_parents'];
    $return_single_row = static::getNewElementReturnMode($parents, $field_name, $form_state) == static::NEW_ELEMENT_RETURN_SINGLE_ELEMENT;

    if ($return_single_row) {
      $response = new AjaxResponse();

      // If any errors occurred return them without replacing the element as it
      // might have not been properly initialized.
      if (static::addFormErrorsToAjaxResponse($response, $form_state)) {
        return $response;
      }

      $button = $form_state->getTriggeringElement();
      $table_id = '#' . $button['#ajax']['table_id'];
      $number_of_rows = $button['#ajax']['number_of_rows'];

      // Core replaces hole table and set ajax-new-content class only on new
      // content. Here only the new row is added and the added row still has
      // the class ajax-new-content, which needs to be removed now as its not
      // new anymore.
      $response->addCommand(new InvokeCommand($table_id . ' .ajax-new-content', 'removeClass', ['ajax-new-content']));

      // Add a DIV around the delta receifving the Ajax effect.
      $delta = $elements['#max_delta'];

      // A storage for the weight elements that have to be updated in case a
      // select element is used instead of a number element, which is decided
      // by Drupal\Core\Render\Element\Weight::processWeight.
      $_weight_elements = [];

      // Render as less as possible!
      if ($number_of_rows > 1) {
        foreach ($elements as $key => $child) {
          if (Element::child($key) && ($key != $delta)) {
            if (!static::isElementRemoved($elements[$key]) && isset($elements[$key]['_weight']['#type']) && ($elements[$key]['_weight']['#type'] == 'select')) {
              $_weight_elements[$key] = $elements[$key]['_weight'];
              unset($elements[$key]);
            }
            else {
              unset($elements[$key]);
            }
          }
        }
      }

      // Update the weight elements of the other rows if they are rendered as
      // select instead as number elements. We do insert the new options
      // instead of replacing only the previous weight elements as by doing so
      // and cutting them out through query path might get extremely slow with
      // a big html.
      // Updating the weight is necessary in order for tabledrag.js to work
      // properly after a new element is inserted and moved around.
      $new_options = array_diff_assoc($elements[$delta]['_weight']['#options'], $form_state->getTemporaryValue('weight_element_previous_options'));
      $form_state->setTemporaryValue('weight_element_previous_options', []);
      array_walk($new_options, function (&$value, $key) {$value = '<option value="' . $key . '">' . $value . '</option>';});
      foreach ($_weight_elements as $_weight_element) {
        foreach ($new_options as $new_option) {
          $response->addCommand(new AppendCommand('[name="' . $_weight_element['#name'] . '"]', $new_option));
        }
      }

      // Needed by entity_reference_inline_preprocess_field_multiple_value_form.
      $elements[$delta]['#new_ajax_row'] = TRUE;

      // The library is needed only if a new element has been added to make its
      // row draggable.
      $elements['#attached']['library'][] = 'entity_reference_inline/entity-reference-inline-make-draggable';

      // Now after we've minimized the content to be rendered we can render.
      $html = (string) static::drupalRenderRoot($elements);

      // If tbody already exists we just return the rendered row, otherwise we
      // have to append the tbody in the first command and in the second command
      // the row, so that no matter if it is the first row in the table or
      // following one we get in attach behaviors as context always just a row
      // and not e.g. "table > tbody > tr" or "tbody > tr".

      // Get only the last row.
      $last_table_row = QueryPath::withHTML5($html, $table_id . ' > tbody > tr:last')->html();

      // If the last delta is bigger than 0 it means tbody is already present.
      if ($delta > 0) {
        $response->addCommand(new AppendCommand($table_id . ' > tbody', $last_table_row));
      }
      else {
        // Two possibilities:
        // 1. Insert tbody along with the first row in one command
        // $tbody = qp($dom, $table_id_jquery_selector . ' > tbody')->html();
        // $response->addCommand(new AppendCommand($table_id_jquery_selector, $tbody));

        // 2. Insert first tbody and after that the row
        //    This unifies the insertion of rows and listening to changed context in
        //    js where with this implementation the changed context for each inserted
        //    row now will be the row and not like in option 1 at inserting the first
        //    row the tbody.
        $response->addCommand(new AppendCommand($table_id, '<tbody></tbody>'));
        $response->addCommand(new AppendCommand($table_id . ' > tbody', $last_table_row));
      }

      // Restripe the table.
      $response->addCommand(new RestripeCommand($table_id));

      // Add the attachments e.g. if we haven't initialized ckeditor yet it's
      // library will be returned.
      $response->setAttachments(isset($elements['#attached']) ? $elements['#attached'] : []);

      // Reset selected bundle.
      $identifier = "[name=\"{$form_state->getTemporaryValue('entity_reference_inline_reset_bundle_select')}\"]";
      $response->addCommand(new InvokeCommand($identifier, 'prop', array('selectedIndex', 0)));
      return $response;
    }
    else {
      // ----- parent::addMoreAjax ---------

      // Ensure the widget allows adding additional items.
      if ($elements['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        return;
      }

      // Add a DIV around the delta receiving the Ajax effect.
      $delta = $elements['#max_delta'];
      $elements[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($elements[$delta]['#prefix']) ? $elements[$delta]['#prefix'] : '');
      $elements[$delta]['#suffix'] = (isset($elements[$delta]['#suffix']) ? $elements[$delta]['#suffix'] : '') . '</div>';

      return $elements;
      // ----- parent::addMoreAjax ---------
    }
  }

  /**
   * Wraps the renderRoot function of the renderer service.
   */
  protected static function drupalRenderRoot(&$elements) {
    return \Drupal::service('renderer')->renderRoot($elements);
  }

  /**
   * Adds status messages to the ajax response if any errors occurred.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   Returns TRUE if the form state contains errors and they have been added
   *   to the ajax response, FALSE otherwise.
   */
  protected static function addFormErrorsToAjaxResponse(AjaxResponse $response, FormStateInterface $form_state) {
    if ($errors = $form_state->getErrors()) {
      $display = '';
      $status_messages = ['#type' => 'status_messages'];
      if ($messages = \Drupal::service('renderer')->renderRoot($status_messages)) {
        $display = '<div class="views-messages">' . $messages . '</div>';
      }
      $options = [
        'dialogClass' => 'views-ui-dialog',
        'width' => '50%',
      ];

      // Attach the library necessary for using the OpenModalDialogCommand and
      // set the attachments for this Ajax response.
      $status_messages['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($status_messages['#attached']);

      $response->addCommand(new OpenModalDialogCommand(t('Error Messages'), $display, $options));

      return TRUE;
    }
    return FALSE;
  }

  /**
   * Prepares the translation for the referenced entity, in case of being on a
   * entity translate page the target translation will added to the entity if
   * not yet present, otherwise the target entity translation will be returned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $referenced_entity
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  protected function prepareTranslation(ContentEntityInterface $referenced_entity, $delta, $parents, FormStateInterface $form_state) {
    // Add translation page.
    if (($source_language = $form_state->get(['content_translation', 'source'])) && ($target_language = $form_state->get(['content_translation', 'target']))) {
      $src_langcode = $source_language->getId();
      $target_langcode = $target_language->getId();

      if ($referenced_entity->hasTranslation($target_langcode)) {
        return $referenced_entity->getTranslation($target_langcode);
      }
      else {
        // If the referenced entity does not have the source language we are
        // translating the main entity from then use its current language as
        // source.
        if (!$referenced_entity->hasTranslation($src_langcode)) {
          $source_language = $this->getTranslationSourceLanguage($referenced_entity, $delta, $parents, $form_state);
          $src_langcode = $source_language->getId();
        }

        // Checks whether the entity is enabled for content translation.
        if ($this->contentTranslationManager->isEnabled($referenced_entity->getEntityTypeId(), $referenced_entity->bundle())) {
          $this->contentTranslationController->prepareTranslation($referenced_entity, $source_language, $target_language);
          $translation = $referenced_entity->getTranslation($target_language->getId());
          $metadata = $this->contentTranslationManager->getTranslationMetadata($translation);
          $metadata->setSource($src_langcode);
          return $translation;
        }
        else {
          return $this->translateEntity($referenced_entity, $src_langcode, $target_langcode);
        }
      }
    }

    // Target langcode is the langcode of the entity being displayed at the
    // moment. It might be as well the target translation if the entity is being
    // translated at the moment. We retrieve the language from the from object
    // as if the entity reference field is not translatable the parent entity
    // will be loaded for the current field in its default language even if
    // the parent entity form is shown in a different language.
    $form_lang_code = $form_state->getFormObject()->getFormLangcode($form_state);
    $target_langcode = $form_lang_code;

    if ($referenced_entity->hasTranslation($target_langcode)) {
      return $referenced_entity->getTranslation($target_langcode);
    }
    else {
      $source_language = $this->getTranslationSourceLanguage($referenced_entity, $delta, $parents, $form_state);

      // Checks whether the entity is enabled for content translation.
      if ($this->contentTranslationManager->isEnabled($referenced_entity->getEntityTypeId(), $referenced_entity->bundle())) {
        $target_language = $this->languageManager->getLanguage($target_langcode);

        $this->contentTranslationController->prepareTranslation($referenced_entity, $source_language, $target_language);
        $translation = $referenced_entity->getTranslation($target_language->getId());
        $metadata = $this->contentTranslationManager->getTranslationMetadata($translation);
        $metadata->setSource($source_language->getId());
        return $translation;
      }
      else {
        return $this->translateEntity($referenced_entity, $source_language->getId(), $target_langcode);
      }
    }

  }

  /**
   * Determines the source language from which the entity will be translated.
   *
   * A helper method for ::prepareTranslation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $referenced_entity
   * @param $delta
   * @param $parents
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return LanguageInterface
   */
  protected function getTranslationSourceLanguage(ContentEntityInterface $referenced_entity, $delta, $parents, FormStateInterface $form_state) {
    return $referenced_entity->language();
  }

  /**
   * Translates an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to translate.
   * @param $src_langcode
   *   The source language code.
   * @param $target_langcode
   *   The target language code.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The translated entity.
   */
  protected function translateEntity(ContentEntityInterface $entity, $src_langcode, $target_langcode) {
    $source_translation = $entity->getTranslation($src_langcode);
    $target_translation = $entity->addTranslation($target_langcode, $source_translation->toArray());
    // TODO find a better way.
    if (method_exists($target_translation, 'setCreatedTime')) {
      $target_translation->setCreatedTime(REQUEST_TIME);
    }
    // TODO find a better way.
    if (method_exists($target_translation, 'setAuthor')) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $target_translation->setAuthor($user);
    }

    // Make sure we do not inherit the affected status from the source values.
    if ($target_translation->getEntityType()->isRevisionable()) {
      $target_translation->setRevisionTranslationAffected(NULL);
    }

    return $target_translation;
  }

  /**
   * Adds the remove button to the given form element.
   *
   * @param array $element
   * @param array $form
   */
  protected function addRemoveButton(array &$element, array $form) {
    if ($this->isCardinalityUnlimited()) {
      $field_name = $this->fieldDefinition->getName();
      $parents = $element['details_element']['#parents'];
      $id_parts = $parents;
      $id_parts[] = 'entity-reference-inline-row';
      $id_prefix = Html::cleanCssIdentifier(implode('-', $parents));
      $element['#row_id'] = $id_prefix;

      $element['remove'] = [
        '#type' => 'submit',
        '#name' => $id_prefix . '-remove',
        '#value' => $this->t('Remove this item'),
        '#attributes' => ['class' => ['field-remove-submit', 'field-remove-submit-' . strtr($field_name, ['_' => '-'])]],
        '#limit_validation_errors' => [array_merge($parents, [$field_name])],
        '#submit' => [[get_class($this), 'removeSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'removeAjax'],
          'effect' => 'fade',
          'row_id' => $element['#row_id'],
          'table_id' => $this->getEntityReferenceFieldTableId($form),
        ],
        '#validate' => [],
        '#weight' => 1000,
      ];
    }
  }

  /**
   * Checks whether the cardinality of the field is unlimited.
   *
   * @return bool
   */
  protected function isCardinalityUnlimited() {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    return $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
  }

  /**
   * Adds entity meta information, which will be later used by
   * ::loadEntityFromMetaInformation in ::massageFormValues to load the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $element
   */
  protected function addEntityMetaInformation(ContentEntityInterface $entity, &$element) {
    $id_field_name = $entity->getEntityType()->getKey('id');
    $element[$id_field_name] = [
      '#type' => 'hidden',
      '#value' => $entity->id()
    ];
  }

  /**
   * Loads the entity from the given meta information as added by
   * ::addEntityMetaInformation.
   *
   * @param $values
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected function loadEntityFromMetaInformation($values) {
    $id_field_name = $this->getReferencedEntityType()->getKey('id');
    $entity = NULL;
    if (isset($values[$id_field_name])) {
      $entity = $this->getReferencedEntityStorage()->load($values[$id_field_name]);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Allow access to the items for ::massageFormValues().
    $this->extractFormValuesFieldItemList = $items;
    parent::extractFormValues($items, $form, $form_state);
    $this->extractFormValuesFieldItemList = NULL;

    // Flag the parent entity with the language for which we have edited the
    // entity in order to check only this language for translation changes on
    // save in the pre-save of the field item.
    // @see \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItem::preSave()
    $parent = $items->getEntity();
    // This method is called also when we are on the field config form, so we
    // have to explicitly check that we are on a content entity form.
    if (!isset($parent->inlineEditedLangcode) && ($form_object = $form_state->getFormObject()) && $form_object instanceof ContentEntityFormInterface) {
      $parent->inlineEditedLangcode = $form_object->getFormLangcode($form_state);
    }
  }

  /**
   * {@inheritdoc}
   *
   * We extract the information from the submitted values needed to rebuild the
   * referenced entities and then return the newly built entities instead of
   * the values of their fields.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $form_langcode = $form_state->getFormObject()->getFormLangcode($form_state);

    foreach ($values as $delta => &$delta_values) {
      if (isset($delta_values['remove'])) {
        unset($delta_values['remove']);
      }
      $original_delta = $delta_values['_original_delta'];

      // Retrieve the sub-form.
      if (isset($form[$this->fieldDefinition->getName()])) {
        $element_form = &$form[$this->fieldDefinition->getName()]['widget'][$original_delta];
      }
      // The field name will not be present in the form structure on the field
      // settings for its default value.
      elseif (isset($form['widget'])) {
        $element_form = &$form['widget'][$original_delta];
      }

      $element_form = $element_form['details_element'];

      // Load the entity.
      if (isset($this->extractFormValuesFieldItemList)) {
        $entity = $this->extractFormValuesFieldItemList[$original_delta]->entity;
      }
      else {
        // This should never happen.
        @trigger_error('The entity should be loaded from the field items instead from the meta information.', E_USER_DEPRECATED);
        $entity = $this->loadEntityFromMetaInformation($delta_values);
      }

      // ContentEntityForm::buildEntity is cloning the main form entity and
      // setting the submitted values on the cloned entity, so that the
      // original one is not altered and on form rebuild the form is rebuild
      // based on the form values and new field items are added. Entity
      // references are not cloned and this is why we clone the entity here
      // before setting it on the cloned field, this way the whole structure
      // will be cloned.
      if (!$entity->isNew()) {
        // We have to ensure that if an entity is reused inside the form state
        // that at all the places we'll be using the same entity object
        // reference instead of having different object references with the same
        // values mapped. This widget doesn't take care of ensuring that a
        // reused entity will be assigned the same form values - this has to be
        // taken care of from the widget extending from the current one and
        // offering this ability.
        $entity_type_id = $entity->getEntityTypeId();
        $entity_id = $entity->id();
        if (!$this->getBuiltEntity($entity_type_id, $entity_id)) {
          $this->setBuiltEntity(clone $entity);
        }
        $entity = $this->getBuiltEntity($entity_type_id, $entity_id);
      }
      else {
        $entity = clone $entity;
      }

      // Process entity translation and language.
      if ($this->getReferencedEntityType()->isTranslatable()) {
        // If we've created a new entity with a previous form language code and
        // now the form language code is changed then we want only to create the
        // entity for the language for which we are going to save the form.
        // The form language could be changed e.g. through the LanguageWidget.
        if ($entity->isNew() && ($entity->language()->getId() != $form_langcode) && (count($entity->getTranslationLanguages()) == 1)) {
          $entity_values = $entity->toArray();
          $entity_values[$this->getReferencedEntityType()->getKey('langcode')] = $form_langcode;
          // The bundle structure must be flat e.g. bundle=>bundle_type instead
          // as returned by toArray - bundle=>[0 => [target_id => bundle_type]].
          if ($bundle_field_name = $this->getReferencedEntityType()->getKey('bundle')) {
            $entity_values[$bundle_field_name] = $entity->bundle();
          }
          $entity = $this->getReferencedEntityStorage()->create($entity_values);
        }
        else {
          // If the entity is new it should be already in the correct translation!
          $entity = $this->prepareTranslation($entity, $original_delta, $form['#parents'], $form_state);
        }
      }

      // Build the entity.
      $this->buildEntity($delta_values, $element_form, $form_state, $entity);

      // Remove all the values which are entity fields, leave only the rest,
      // such as '_original_delta' and '_weight'.
      foreach ($entity as $name => $field) {
        unset($delta_values[$name]);
      }
      $delta_values['entity'] = $entity;
    }

    return $values;
  }

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * For building the updated entity object the submitted form values are
   * copied to entity properties and all the specified entity builders for
   * copying form values to entity properties are invoked.
   *
   * @param array $delta_values
   *   The submitted delta form values produced by the widget.
   * @param array $element_form
   *   The form structure of the delta element, a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity mapped to the delta values. This parameter is given by
   *   reference to allow extending the class and exchanging the entity object.
   */
  protected function buildEntity(array &$delta_values, array $element_form, FormStateInterface $form_state, ContentEntityInterface &$entity) {
    $this->mapDeltaFormValuesToEntity($delta_values, $element_form, $form_state, $entity);

    // Invoke all specified builders for copying form values to entity
    // properties. Here we create a dedicated form state containing only the
    // entity specific form values, as there are entity builders such
    // ContentTranslationHandler::entityFormEntityBuild, which expect that the
    // value are at the first level and does not search by field parents like
    // WidgetBase does.
    if (isset($element_form['#entity_builders'])) {
      $entity_form_state = new FormState();
      $entity_form_state->setValues($delta_values);
      $entity_form_state->setStorage($form_state->getStorage());
      $entity_form_state->setFormObject($form_state->getFormObject());
      $entity_form_state->setValidationComplete($form_state->isValidationComplete());
      $entity_form_state->setSubmitHandlers($form_state->getSubmitHandlers());
      $entity_form_state->setUserInput($form_state->getUserInput());

      // Add the form display to the form state as it might be needed by the
      // entity builders.
      $entity_form_display = $this->getFormDisplay($entity->getEntityTypeId(), $entity->bundle());
      /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
      $form_object = $form_state->getFormObject();
      $main_form_display = $form_object->getFormDisplay($form_state);
      $form_object->setFormDisplay($entity_form_display, $entity_form_state);

      foreach ($element_form['#entity_builders'] as $function) {
        call_user_func_array($function, [$entity->getEntityTypeId(), $entity, &$element_form, &$entity_form_state]);
      }
      // Replace the delta values with the ones from the entity form state, as
      // they could have changed in some of the entity builders functions.
      $delta_values = $entity_form_state->getValues();

      // As the entity builders might've altered the form state storage we have
      // to set back the updated storage to the parent form state.
      $form_state->setStorage($entity_form_state->getStorage());
      $form_object->setFormDisplay($main_form_display, $form_state);

      // A form builder might have altered the user input so we have to set it
      // back.
      $form_state->setUserInput($entity_form_state->getUserInput());
    }

    // Validate the generated entity. This shoudl be done after the entity
    // builders have run.
    $this->doValidate($element_form, $form_state, $entity);

    // Flag the entity with the language for which we have edited the entity in
    // order to check only this language for translation changes on save in the
    // pre-save of the field item.
    // @see \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItem::preSave()
    $entity->inlineEditedLangcode = $entity->language()->getId();
  }

  /**
   * Processes the delta form values and maps them to the corresponding entity.
   *
   * @param array $delta_values
   *   The submitted delta form values produced by the widget.
   * @param array $element_form
   *   The form structure of the delta element, a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity mapped to the delta values. This parameter is given by
   *   reference to allow extending the class and exchanging the entity object.
   */
  protected function mapDeltaFormValuesToEntity(array &$delta_values, array $element_form, FormStateInterface $form_state, ContentEntityInterface &$entity) {
    $entity_form_display = $this->getFormDisplay($entity->getEntityTypeId(), $entity->bundle());

    // First, extract values from widgets for the form display mode 'edit_by_user_text'.
    $extracted = $entity_form_display->extractFormValues($entity, $element_form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($delta_values as $name => $delta_value) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $delta_value);
      }
    }

    // Update the entity changed timestamp after it has been validated if a new
    // translation was added to it.
    if ($entity->isNewTranslation()) {
      // Checks whether the entity is enabled for content translation.
      if ($this->contentTranslationManager->isEnabled($entity->getEntityTypeId(), $entity->bundle())) {
        $metadata = $this->contentTranslationManager->getTranslationMetadata($entity);
        $metadata->setChangedTime(REQUEST_TIME);
      }
      elseif ($entity instanceof EntityChangedInterface) {
        $entity->setChangedTime(REQUEST_TIME);
      }
    }
  }

  /**
   * Validates the generated entity.
   *
   * @param array $element_form
   *   The form structure of the delta element, a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be validated.
   */
  protected function doValidate(array $element_form, FormStateInterface $form_state, ContentEntityInterface $entity) {
    // Only validate during form validation running and skip during submit!
    if (!$form_state->isValidationComplete()) {
      // Backup the errors so that we can estimate later  the new one added
      // by the validation and modify them.
      $errors_previous = $form_state->getErrors();
      $this->getFormDisplay($entity->getEntityTypeId(), $entity->bundle())->validateFormValues($entity, $element_form, $form_state);
      $errors_after = $form_state->getErrors();

      // Estimate the new errors and modify them.
      $new_errors = array_diff_key($errors_after, $errors_previous);

      foreach ($new_errors as $name => &$message) {
        $message = $this->t('Referenced entity (:ref_entity_type) ":ref_entity": @message', [':ref_entity_type' => $entity->getEntityType()->getLabel(), ':ref_entity' => $entity->label(), '@message' => $message]);
      }

      // TODO ensure the corresponding entities are marked with an error class.
      // Restore the errors.
      $form_state->clearErrors();
      foreach (array_merge($errors_previous, $new_errors) as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
    }
  }

  /**
   * Submission handler for the "Remove item" button.
   */
  public static function removeSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the element to be removed.
    $delta_parents = array_slice($button['#array_parents'], 0, -1);
    $delta = end($delta_parents);
    $field_parents = array_slice($button['#array_parents'], 0, -2);

    $element = NestedArray::getValue($form, $field_parents);
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Flag the delta item as removed.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['deltas_removed'] = isset($field_state['deltas_removed']) ? $field_state['deltas_removed'] : [];
    $field_state['deltas_removed'][$delta] = TRUE;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Remove item" button.
   */
  public static function removeAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $table_id = '#' . $button['#ajax']['table_id'];
    $row_id = '#' . $button['#ajax']['row_id'];

    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand($row_id));
    $response->addCommand(new RestripeCommand($table_id));
    return $response;
  }

  /**
   * Returns the referenced entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   */
  protected function getReferencedEntityType() {
    if (!isset($this->referencedEntityType)) {
      $target_type = $this->getFieldSetting('target_type');
      $this->referencedEntityType = $this->entityTypeManager->getDefinition($target_type);
    }
    return $this->referencedEntityType;
  }

  /**
   * Returns the entity storage for the referenced entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getReferencedEntityStorage() {
    $target_type = $this->getFieldSetting('target_type');
    return $this->entityTypeManager->getStorage($target_type);
  }

  /**
   * Returns the EntityFormDisplay for the referenced entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The entity form display for the given entity.
   */
  protected function getFormDisplay($entity_type_id, $bundle) {
    if ($this->getReferencedEntityType()->hasKey('bundle')) {
      $form_modes_bundles = $this->getSetting('form_modes_bundles');
      // Fallback to the default.
      $form_mode = isset($form_modes_bundles[$bundle]) ? $form_modes_bundles[$bundle] : $this->getSetting('form_mode');
    }
    else {
      $form_mode = $this->getSetting('form_mode');
    }

    $entity_form_display_id = implode('.', [$entity_type_id, $bundle, $form_mode]);

    if (!isset($this->referencedEntityFormDisplays[$entity_form_display_id])) {
      $this->referencedEntityFormDisplays[$entity_form_display_id] = EntityFormDisplay::load($entity_form_display_id);

      // The form display will not be present if not explicitly created, so
      // at this place we do it just like the core does and create it on the
      // fly without saving it.
      if (!isset($this->referencedEntityFormDisplays[$entity_form_display_id])) {
        $this->referencedEntityFormDisplays[$entity_form_display_id] = EntityFormDisplay::create([
          'targetEntityType' => $entity_type_id,
          'bundle' => $bundle,
          'mode' => $form_mode,
          'status' => TRUE,
        ]);
      }
    }

    return $this->referencedEntityFormDisplays[$entity_form_display_id];
  }

  /**
   * Returns the form display options.
   *
   * @param $bundle
   *   (optional) The bundle for which to retrieve the form mode options.
   * @return array
   *   List of form modes.
   */
  protected function getFormModeOptions($bundle = NULL) {
    $target_type = $this->getFieldSetting('target_type');
    $form_modes = isset($bundle) ? $this->entityDisplayRepository->getFormModeOptionsByBundle($target_type, $bundle) : $this->entityDisplayRepository->getFormModeOptions($target_type);

    return $form_modes;
  }

  /**
   * Get the new element return mode from the widget state.
   *
   * @return string
   *   The new element return mode.
   */
  protected static function getNewElementReturnMode($parents, $field_name, FormStateInterface $form_state) {
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    return $field_state['new_element_return_mode'];
  }

  /**
   * Returns the entity object that is used in case of reused entities.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The built entity object or NULL if not set.
   */
  protected function getBuiltEntity($entity_type_id, $entity_id) {
    return isset(static::$builtEntities[$entity_type_id][$entity_id]) ? static::$builtEntities[$entity_type_id][$entity_id] : NULL;
  }

  /**
   * Adds the entity object to the built entity list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function setBuiltEntity(EntityInterface $entity) {
    static::$builtEntities[$entity->getEntityTypeId()][$entity->id()] = $entity;
  }

}
