<?php

namespace Drupal\entityreference_dragdrop\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'entityreference_dragdrop' widget.
 *
 * @FieldWidget(
 *   id = "entityreference_dragdrop",
 *   label = @Translation("Drag&Drop"),
 *   description = @Translation("A widget allowing use drag&drop to edit the field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceDragDropWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  const VIEW_MODE_TITLE = 'title'; // Display only entity title.

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * EntityReferenceDragDropWidget constructor.
   *
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManager $entity_type_manager, EntityDisplayRepository $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => static::VIEW_MODE_TITLE,
      'available_entities_label' => 'Available entities',
      'selected_entities_label' => 'Selected entities',
      'display_filter' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $this->viewModeOptions(),
      '#default_value' => $this->getSetting('view_mode'),
    ];

    $element['available_entities_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Available entities label'),
      '#default_value' => $this->getSetting('available_entities_label'),
      '#description' => $this->t('Enter a label that will be displayed above block with available entities.')
    ];

    $element['selected_entities_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S-elected entities label'),
      '#default_value' => $this->getSetting('selected_entities_label'),
      '#description' => $this->t('Enter a label that will be displayed above block with selected entities.')
    ];

    $element['display_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display item filter'),
      '#default_value' => $this->getSetting('display_filter'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $view_mode = $this->viewModeOptions()[$this->getSetting('view_mode')];
    $summary[] = $this->t('View mode: @view_mode', ['@view_mode' => $view_mode]);
    $summary[] = $this->t('Available entities label: @label', ['@label' => $this->getSetting('available_entities_label')]);
    $summary[] = $this->t('Selected entities label: @label', ['@label' => $this->getSetting('selected_entities_label')]);
    $summary[] = $this->t('Display filter: @filter', ['@filter' => $this->getSetting('display_filter') ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $entity_type = $items->getEntity()->getEntityTypeId();
    $bundle = $items->getEntity()->bundle();
    $entity_id = $items->getEntity()->id() ?: '0';
    $key = $entity_type . '_' . $bundle . '_' .  $field_name . '_' . $entity_id;
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $selected = $this->getSelectedOptions($items);
    $available = $this->getAvailableOptions($items);

    $element['label'] = [
      '#type' => 'item',
      '#title' => $element['#title'],
      '#required' => $element['#required'],
      '#value' => 'just some value so #required does not trigger validation error.'
    ];
    if ($cardinality != -1) {
      $element['message'] = [
        '#markup' => '<div class="entityreference-dragdrop-message" data-key="' . $key . '">'
          . $this->formatPlural($cardinality, 'This field cannot hold more than 1 value.', 'This field cannot hold more than @count values.') . '</div>',
      ];
    }
    $element['available'] = $this->availableOptionsToRenderableArray($available, $key);
    $element['selected'] = $this->selectedOptionsToRenderableArray($selected, $key);

    $element['target_id'] = [
      '#type' => 'hidden',
      '#default_value' => implode(',', array_keys($selected)),
      '#attached' => [
        'library' => ['entityreference_dragdrop/init'],
        'drupalSettings' => [
          'entityreference_dragdrop' => [
            $key => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['entityreference-dragdrop-values'],
        'data-key' => $key,
      ],
    ];

    if ($element['#description']) {
      $element['description'] = [
        '#type' => 'item',
        '#description' => $element['#description'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return empty($values['target_id']) ? [] : explode(',', $values['target_id']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));

    $selected_options = [];
    foreach ($items as $item) {
      $id = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$id])) {
        $selected_options[$id] = $flat_options[$id];
      }
    }

    return $selected_options;
  }

  /**
   * Gets a list of available entities for the field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @return array
   */
  protected function getAvailableOptions(FieldItemListInterface $items) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));
    $selected_options = $this->getSelectedOptions($items);

    $available_options = [];
    foreach ($flat_options as $id => $option) {
      if (!in_array($option, $selected_options)) {
        $available_options[$id] = $option;
      }
    }

    return $available_options;
  }

  /**
   * Converts list of options to renderable array.
   *
   * @param array $options
   * @param $key
   * @param $list_title
   * @param array $classes
   * @param array $wrapper_classes
   * @return array
   */
  protected function optionsToRenderableArray(array $options, $key, $list_title, array $classes = [], array $wrapper_classes = []) {
    // view mode is of the form 'node.full_content'
    $view_mode_name = $this->getSetting('view_mode');
    $view_mode_name = explode('.', $view_mode_name);
    $view_mode = end($view_mode_name);

    $target_type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $items = [];
    $entities = [];

    if ($view_mode !== static::VIEW_MODE_TITLE) {
      $entities = $this->entityTypeManager->getStorage($target_type_id)->loadMultiple(array_keys($options));
    }

    foreach ($options as $id => $entity_title) {
      $item = [
        '#wrapper_attributes' => [
          'data-key' => $key,
          'data-id' => $id,
          'data-label' => $entity_title,
        ],
      ];
      if ($view_mode !== static::VIEW_MODE_TITLE) {
        $item += $this->entityTypeManager->getViewBuilder($target_type_id)->view($entities[$id], $view_mode);
      }
      else {
        $item += [
          '#markup' => $options[$id],
        ];
      }
      $items[] = $item;
    }

    return [
      '#theme' => 'entityreference_dragdrop_options_list',
      '#items' => $items,
      '#title' => $list_title,
      '#display_filter' => $this->getSetting('display_filter'),
      '#attributes' => [
        'data-key' => $key,
        'class' => array_merge(['entityreference-dragdrop'], $classes),
      ],
      '#wrapper_attributes' => [
        'class' => array_merge(['entityreference-dragdrop-container'], $wrapper_classes),
      ],
    ];
  }

  /**
   * Converts list of selected options to renderable array.
   *
   * @param array $options
   * @param $key
   * @return array
   */
  protected function selectedOptionsToRenderableArray(array $options, $key) {
    return $this->optionsToRenderableArray($options, $key, $this->getSetting('selected_entities_label'), ['entityreference-dragdrop-selected'], ['entityreference-dragdrop-container-selected']);
  }

  /**
   * Converts list of available options to renderable array.
   *
   * @param array $options
   * @param $key
   * @return array
   */
  protected function availableOptionsToRenderableArray(array $options, $key) {
    return $this->optionsToRenderableArray($options, $key, $this->getSetting('available_entities_label'), ['entityreference-dragdrop-available'], ['entityreference-dragdrop-container-available']);
  }

  /**
   * Gets view mode options.
   *
   * @return array
   */
  protected function viewModeOptions() {
    $target_type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $view_modes = $this->entityDisplayRepository->getViewModes($target_type_id);
    $options = [static::VIEW_MODE_TITLE => $this->t('Title')];
    foreach ($view_modes as $view_mode) {
      $options[$view_mode['id']] = $view_mode['label'];
    }

    return $options;
  }

}
