<?php

namespace Drupal\default_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\default_paragraphs\Events\DefaultParagraphsAddEvent;
use Drupal\default_paragraphs\Events\DefaultParagraphsEvents;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "default_paragraphs",
 *   label = @Translation("Default paragraphs widget"),
 *   description = @Translation("Allows us to select multiple paragraph types as defaults."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class DefaultParagraphsWidget extends ParagraphsWidget implements ContainerFactoryPluginInterface {

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Constructs display plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Utility\Token $token_service.
   *   Token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EventDispatcherInterface $event_dispatcher, EntityDisplayRepositoryInterface $entity_display_repository, Token $token_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->eventDispatcher = $event_dispatcher;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->tokenService = $token_service;
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
      $container->get('event_dispatcher'),
      $container->get('entity_display.repository'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => t('Paragraph'),
      'title_plural' => t('Paragraphs'),
      'edit_mode' => 'closed',
      'closed_mode' => 'summary',
      'autocollapse' => 'none',
      'add_mode' => 'dropdown',
      'form_display_mode' => 'default',
      'default_paragraph_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Title'),
      '#description' => $this->t('Label to appear as title on the button as "Add new [title]", this label is translatable'),
      '#default_value' => $this->getSetting('title'),
      '#required' => TRUE,
    ];

    $elements['title_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural Paragraph Title'),
      '#description' => $this->t('Title in its plural form.'),
      '#default_value' => $this->getSetting('title_plural'),
      '#required' => TRUE,
    ];

    $elements['closed_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Closed mode'),
      '#description' => $this->t('How to display the paragraphs, when the widget is closed. Preview will render the paragraph in the preview view mode and typically needs a custom admin theme.'),
      '#options' => $this->getSettingOptions('closed_mode'),
      '#default_value' => $this->getSetting('closed_mode'),
      '#required' => TRUE,
    ];

    $elements['autocollapse'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocollapse'),
      '#description' => $this->t('When a paragraph is opened for editing, close others.'),
      '#options' => $this->getSettingOptions('autocollapse'),
      '#default_value' => $this->getSetting('autocollapse'),
      '#required' => TRUE,
    ];

    $elements['add_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Add mode'),
      '#description' => $this->t('The way to add new Paragraphs.'),
      '#options' => $this->getSettingOptions('add_mode'),
      '#default_value' => $this->getSetting('add_mode'),
      '#required' => TRUE,
    ];

    $elements['form_display_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getFormModeOptions($this->getFieldSetting('target_type')),
      '#description' => $this->t('The form display mode to use when rendering the paragraph form.'),
      '#title' => $this->t('Form display mode'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#required' => TRUE,
    ];

    $elements['default_paragraph_types'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Paragraph type'),
        $this->t('Machine name'),
        $this->t('Use as Default'),
        $this->t('Edit mode'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      '#element_validate' => [
        [$this,  'settingsFormDefaultParagraphsValidate']
      ]
    ];

    // We iterate over the allowed paragraph types, if nothing is selected yet.
    $defaults = $this->getSetting('default_paragraph_types');
    $allowed = $this->getAllowedTypes();

    if (!empty($defaults)) {
      // Make sure that defaults array contains all the allowed paragraph types
      // and not only the default ones. The allowed one should be shown at the
      // bottom of the list if they do not exist in the default array.
      foreach ($allowed as $key => $data) {
        if (!isset($defaults[$key])) {
          $defaults[$key] = [
            'value' => 0,
            'weight' => 1000,
          ];
        }
      }
    }
    else {
      $defaults = $allowed;
    }

    foreach ($defaults as $key => $bundle) {
      $elements['default_paragraph_types'][$key] = [
        'name' => [
          '#markup' => $allowed[$key]['label'],
        ],
        'machine_name' => [
          '#markup' => $key,
        ],
        'value' => [
          '#type' => 'checkbox',
          '#default_value' => isset($defaults[$key]['value']) ? $defaults[$key]['value'] : 0,
        ],
        'edit_mode' => [
          '#type' => 'select',
          '#options' => [
            'edit' => $this->t('Open'),
            'closed' => $this->t('Closed'),
          ],
          '#default_value' => isset($defaults[$key]['edit_mode']) ? $defaults[$key]['edit_mode'] : 'closed',
        ],

        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => 'First']),
          '#title_display' => 'invisible',
          '#attributes' => [
            'class' => [
              'table-sort-weight',
            ],
          ],
        ],
      ];

      $elements['default_paragraph_types'][$key]['#attributes']['class'][] = 'draggable';
      $elements['default_paragraph_types'][$key]['#weight'] = isset($defaults[$key]['weight']) ? $defaults[$key]['weight'] : 1000;

    }

    return $elements;
  }

  /**
   * Custom validate handler to check the default paragraph types.
   */
  public function settingsFormDefaultParagraphsValidate($element, FormStateInterface $form_state) {
    if (isset($element['#value'])) {
      $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
        ->getCardinality();
      $field_label = $this->fieldDefinition->getLabel();

      if ($cardinality !== -1) {
        $default_paragraph_count = 0;
        foreach ($element['#value'] as $key => $data) {
          if (!empty($data['value'])) {
            $default_paragraph_count++;
          }
        }

        if ($default_paragraph_count > $cardinality) {
          $form_state->setErrorByName('default_paragraph_types', t('@field field allows you to select not more than @total paragraph types as default.', [
            '@field' => $field_label,
            '@total' => $cardinality
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Title: @title', ['@title' => $this->getSetting('title')]);
    $summary[] = $this->t('Plural title: @title_plural', [
      '@title_plural' => $this->getSetting('title_plural'),
    ]);

    $closed_mode = $this->getSettingOptions('closed_mode')[$this->getSetting('closed_mode')];
    $autocollapse = $this->getSettingOptions('autocollapse')[$this->getSetting('autocollapse')];
    $add_mode = $this->getSettingOptions('add_mode')[$this->getSetting('add_mode')];

    $summary[] = $this->t('Closed mode: @closed_mode', ['@closed_mode' => $closed_mode]);
    $summary[] = $this->t('Autocollapse: @autocollapse', ['@autocollapse' => $autocollapse]);
    $summary[] = $this->t('Add mode: @add_mode', ['@add_mode' => $add_mode]);

    $summary[] = $this->t('Form display mode: @form_display_mode', [
      '@form_display_mode' => $this->getSetting('form_display_mode'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);

    $max = $field_state['items_count'];

    // Consider adding a default paragraph for new host entities.
    if ($max == 0 && $items->getEntity()->isNew()) {
      $default_types = $this->getDefaultParagraphTypes();
      $target_bundle = $this->fieldDefinition->getTargetBundle();

      foreach ($default_types as $delta => $default_type) {
        // Place the default paragraph.
        $default_type_name = $default_type['name'];
        $paragraphs_entity = Paragraph::create(['type' => $default_type_name]);

        // Allow other modules to set default value for each paragraph entity.
        $this->eventDispatcher->dispatch(DefaultParagraphsEvents::ADDED, new DefaultParagraphsAddEvent($paragraphs_entity, $target_bundle));

        $field_state['selected_bundle'] = $default_type_name;
        $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));
        $field_state['paragraphs'][$delta] = [
          'entity' => $paragraphs_entity,
          'display' => $display,
          'mode' => isset($default_type['edit_mode']) ? $default_type['edit_mode'] : 'closed',
          'original_delta' => 1,
        ];
        $max++;
      }
      $field_state['items_count'] = $max;
    }

    $this->realItemCount = $max;
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $field_title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create($this->tokenService->replace($this->fieldDefinition->getDescription()));

    $elements = [];
    $tabs = '';
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, [$field_name]));
    $this->fieldWrapperId = Html::getUniqueId($this->fieldIdPrefix . '-add-more-wrapper');

    // If the parent entity is paragraph add the nested class if not then add
    // the perspective tabs.
    $field_prefix = strtr($this->fieldIdPrefix, '_', '-');
    if (count($this->fieldParents) == 0) {
      if ($items->getEntity()->getEntityTypeId() != 'paragraph') {
        $tabs = '<ul class="paragraphs-tabs tabs primary clearfix"><li id="content" class="tabs__tab"><a href="#' . $field_prefix . '-values">Content</a></li><li id="behavior" class="tabs__tab"><a href="#' . $field_prefix . '-values">Behavior</a></li></ul>';
      }
    }
    if (count($this->fieldParents) > 0) {
      if ($items->getEntity()->getEntityTypeId() === 'paragraph') {
        $form['#attributes']['class'][] = 'paragraphs-nested';
      }
    }
    $elements['#prefix'] = '<div class="is-horizontal paragraphs-tabs-wrapper" id="' . $this->fieldWrapperId . '">' . $tabs;
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $header_actions = $this->buildHeaderActions($field_state, $form_state);
    if ($header_actions) {
      $elements['header_actions'] = $header_actions;
      // Add a weight element so we guaranty that header actions will stay in
      // first row. We will use this later in
      // paragraphs_preprocess_field_multiple_value_form().
      $elements['header_actions']['_weight'] = [
        '#type' => 'weight',
        '#default_value' => -100,
      ];
    }

    if (!empty($field_state['dragdrop'])) {
      $elements['#attached']['library'][] = 'paragraphs/paragraphs-dragdrop';
      $elements['dragdrop'] = $this->buildNestedParagraphsFoDragDrop($form_state, NULL, []);
      return $elements;
    }

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the
        // wrapping.
        // table.
        $element = [
          '#title' => $is_multiple ? '' : $field_title,
          '#description' => $is_multiple ? '' : $description,
        ];
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
              // Note: this 'delta' is the FAPI #type 'weight' element's
              // property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            ];
          }

          // Access for the top element is set to FALSE only when the paragraph
          // was removed. A paragraphs that a user can not edit has access on
          // lower level.
          if (isset($element['#access']) && !$element['#access']) {
            $this->realItemCount--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['real_item_count'] = $this->realItemCount;
    $field_state['add_mode'] = $this->getSetting('add_mode');
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $this->fieldDefinition->isRequired(),
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    if ($this->realItemCount > 0) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#cardinality_multiple' => $is_multiple,
        '#title' => $field_title,
        '#description' => $description,
      ];

    }
    else {
      $classes = $this->fieldDefinition->isRequired() ? ['form-required'] : [];
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#cardinality_multiple' => TRUE,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $field_title,
          '#attributes' => ['class' => $classes],
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('No @title added yet.', ['@title' => $this->getSetting('title')]),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
        ],
      ];

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    $host = $items->getEntity();
    $this->initIsTranslating($form_state, $host);

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && !$this->isTranslating) {
      $elements['add_more'] = $this->buildAddActions();
    }

    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.widget';
    $elements['#attached']['library'][] = 'default_paragraphs/drupal.default_paragraphs.widget';

    return $elements;
  }

  /**
   * Get the machine names of the default paragraph types.
   */
  protected function getDefaultParagraphTypes() {
    $defaults = [];

    $delta = 0;
    foreach ($this->getSetting('default_paragraph_types') as $machine_name => $data) {
      if (!empty($data['value'])) {
        $defaults[$delta]['name'] = $machine_name;
        $defaults[$delta]['edit_mode'] = !empty($data['edit_mode']) ? $data['edit_mode'] : 'closed';
      }

      $delta++;
    }

    return $defaults;
  }

}
