<?php

namespace Drupal\field_group_table\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\field\FieldConfigInterface;
use Drupal\field_group\FieldGroupFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_group_table' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "field_group_table",
 *   label = @Translation("Table"),
 *   description = @Translation("This fieldgroup renders fields in a 2-column table with the label in the left column, and the value in the right column."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class FieldGroupTable extends FieldGroupFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Render API properties.
   *
   * @var array
   */
  protected $renderApiProperties = [
    '#theme',
    '#markup',
    '#prefix',
    '#suffix',
    '#type',
    'widget',
  ];

  /**
   * Denotes that the item should be hidden.
   */
  const DISPLAY_HIDDEN = 1;

  /**
   * Denotes that the item should be displayed above.
   */
  const DISPLAY_ABOVE = 2;

  /**
   * Denotes that the item should be displayed as a table caption.
   */
  const DISPLAY_CAPTION = 3;

  /**
   * Denotes that the item should be displayed below.
   */
  const DISPLAY_BELOW = 4;

  /**
   * Denotes that an empty label should be kept.
   */
  const EMPTY_LABEL_KEEP = 1;

  /**
   * Denotes that if label is empty cells should be merged.
   */
  const EMPTY_LABEL_MERGE = 2;

  /**
   * Denotes that additional content type is "header".
   */
  const ADD_CONTENT_HEADER = 'header';

  /**
   * Denotes that additional content type is "footer".
   */
  const ADD_CONTENT_FOOTER = 'footer';

  /**
   * Constructs a Popup object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param object $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $group, $settings, $label);
    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['group'],
      $configuration['settings'],
      $configuration['label'],
      $container->get('module_handler'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'label_visibility' => self::DISPLAY_ABOVE,
      'desc' => '',
      'desc_visibility' => self::DISPLAY_ABOVE,
      'first_column' => '',
      'second_column' => '',
      'empty_label_behavior' => self::EMPTY_LABEL_KEEP,
      'table_row_striping' => FALSE,
      'always_show_field_label' => FALSE,
      'always_show_field_value' => FALSE,
      'empty_field_placeholder' => '',
    ] + parent::defaultSettings($context);

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['label_visibility'] = [
      '#title' => $this->t('Label visibility'),
      '#description' => $this->t('This option determines how to display the Field group label.'),
      '#type' => 'select',
      '#options' => [
        self::DISPLAY_HIDDEN => $this->t('Hidden'),
        self::DISPLAY_ABOVE => $this->t('Above table'),
        self::DISPLAY_CAPTION => $this->t('Table caption'),
        self::DISPLAY_BELOW => $this->t('Below table'),
      ],
      '#default_value' => $this->getSetting('label_visibility'),
    ];
    $form['desc'] = [
      '#title' => $this->t('Description for the group.'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('desc'),
    ];
    $form['desc_visibility'] = [
      '#title' => $this->t('Description visibility'),
      '#description' => $this->t('This option determines how to display the Field group description.'),
      '#type' => 'select',
      '#options' => [
        self::DISPLAY_HIDDEN => $this->t('Hidden'),
        self::DISPLAY_ABOVE => $this->t('Above table'),
        self::DISPLAY_BELOW => $this->t('Below table'),
      ],
      '#default_value' => $this->getSetting('desc_visibility'),
    ];
    $form['first_column'] = [
      '#title' => $this->t('First column header'),
      '#description' => $this->t('Use this field to add a first column table header.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('first_column'),
    ];
    $form['second_column'] = [
      '#title' => $this->t('Second column header'),
      '#description' => $this->t('Use this field to add a second column table header.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('second_column'),
    ];
    $form['empty_label_behavior'] = [
      '#title' => $this->t('Empty label behavior'),
      '#type' => 'select',
      '#options' => [
        self::EMPTY_LABEL_KEEP => $this->t('Keep empty label cell'),
        self::EMPTY_LABEL_MERGE => $this->t('Merge cells'),
      ],
      '#default_value' => $this->getSetting('empty_label_behavior'),
    ];
    $form['table_row_striping'] = [
      '#title' => $this->t('Table row striping'),
      '#description' => $this->t('Adds zebra striping on the table rows.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('table_row_striping'),
    ];
    $form['always_show_field_label'] = [
      '#title' => $this->t('Always show field label'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('always_show_field_label'),
    ];
    $form['always_show_field_value'] = [
      '#title' => $this->t('Always show field value'),
      '#description' => $this->t('Forces row to display even if field have an empty value.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('always_show_field_value'),
      '#attributes' => ['class' => ['fgt-always-show-field-value']],
    ];
    $form['empty_field_placeholder'] = [
      '#title' => $this->t('Empty field placeholder'),
      '#description' => $this->t('What to display as a content of empty field.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('empty_field_placeholder'),
      '#states' => ['visible' => ['.fgt-always-show-field-value' => ['checked' => TRUE]]],
    ];

    switch ($this->context) {

      case 'view':
        $form['always_show_field_label']['#description'] = $this->t('Forces the field label to always display in the first column and renders the field normally with label display option which was selected for current display.<br>Set Label display "Above" or "Hidden" to hide field label in second column.');
        break;

      case 'form':
        $form['always_show_field_label']['#description'] = $this->t('Forces to duplicate field label in a second column.');
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display results as a 2 column table.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $element['#mode'] = $this->context;
    // Allow modules to alter the rows, useful for removing empty rows.
    $children = Element::children($element, TRUE);
    $this->moduleHandler->alter('field_group_table_rows', $element, $children);

    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'field-group-table';
    $element['#attributes']['class'][] = $this->group->group_name;

    $element['header'] = $this->buildAdditionalContent(self::ADD_CONTENT_HEADER);

    $element['table'] = [
      '#type' => 'table',
      '#caption' => $this->getSetting('label_visibility') == self::DISPLAY_CAPTION ? $this->group->label : NULL,
      '#header' => $this->getTableHeader(),
      '#attributes' => [
        'class' => array_merge(
          $this->getTableCssClasses($element),
          explode(' ', $this->getSetting('classes'))
        ),
      ],
    ];

    $element['footer'] = $this->buildAdditionalContent(self::ADD_CONTENT_FOOTER);

    foreach ($children as $key => $field_name) {
      if ($row = $this->buildRow($element, $field_name)) {
        $element['table']['#rows'][$key] = $row;
      }
      unset($element[$field_name]);
    }
  }

  /**
   * Return table CSS classes list.
   *
   * @param array $element
   *   Rendering array of an element.
   *
   * @return array
   *   Table CSS classes list.
   */
  protected function getTableCssClasses(array $element) {
    $css_classes = ['table'];
    $parts = [];

    if (isset($element['#entity_type'])) {
      $parts[] = $element['#entity_type'];
    }
    if (isset($element['#bundle'])) {
      $parts[] = $element['#bundle'];
    }
    if (isset($element['#mode'])) {
      $parts[] = $element['#mode'];
    }
    if ($parts) {
      $css_classes[] = Html::cleanCssIdentifier(implode('-', $parts));
    }

    return $css_classes;
  }

  /**
   * Build table row for requested element.
   *
   * @param array $element
   *   Rendering array of an element.
   * @param string $field_name
   *   The name of currently handling field.
   *
   * @return array
   *   Table row definition on success or an empty array otherwise.
   */
  protected function buildRow(array $element, $field_name) {
    $item = $this->getRowItem($element, $field_name);
    $build = [];

    if (!$item) {
      return $build;
    }

    switch ($this->context) {

      case 'view':
        $build = $this->buildRowView($item);
        break;

      case 'form':
        $build = $this->buildRowForm($item);
        break;
    }

    $build['class'][] = 'table-row';
    $build['no_striping'] = !$this->getSetting('table_row_striping');

    return $build;
  }

  /**
   * Return item definition array.
   *
   * @param array $element
   *   Rendering array.
   * @param string $field_name
   *   Item field machine name.
   *
   * @return array
   *   Item definition array on success or empty array otherwise.
   */
  protected function getRowItem(array $element, $field_name) {
    $item = isset($element[$field_name]) ? $element[$field_name] : [];
    $is_empty = !is_array($item) || !array_intersect($this->renderApiProperties, array_keys($item));

    if ($is_empty && $this->getSetting('always_show_field_value') && isset($element['#entity_type'], $element['#bundle'])) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($element['#entity_type'], $element['#bundle']);
      $field_definition = isset($field_definitions[$field_name]) ? $field_definitions[$field_name] : NULL;

      if ($field_definition instanceof FieldConfigInterface) {
        $is_empty = FALSE;

        $item = [
          '#title' => $field_definition->label(),
          '#label_display' => 'above',
          '#markup' => Xss::filter($this->getSetting('empty_field_placeholder')),
        ];
      }
    }

    return $is_empty ? [] : $item;
  }

  /**
   * Build table row for a "view" context.
   *
   * @param array $element
   *   Rendering array of an element.
   *
   * @return array
   *   Table row for a "view" context.
   */
  protected function buildRowView(array $element) {
    $label_display = isset($element['#label_display']) ? $element['#label_display'] : '';
    $title_data = $this->getElementTitleData($element);
    $build = [];

    // Display the label in the first column,
    // if 'always show field label' is set.
    if ($this->getSetting('always_show_field_label')) {
      $build['data'] = [
        [
          'data' => ['#markup' => $title_data['title']],
          'header' => TRUE,
        ],
        [
          'data' => $element,
        ],
      ];
    }

    // Display the label in the first column,
    // if it's set to "above" and the title isn't empty.
    elseif ($title_data['title'] && $label_display === 'above') {
      $this->hideElementTitle($element);
      $build['data'] = [
        [
          'data' => ['#markup' => $title_data['title']],
          'header' => TRUE,
        ],
        [
          'data' => $element,
        ],
      ];
    }

    // Display an empty cell if we won't display the title and
    // 'empty label behavior' is set to keep empty label cells.
    elseif ($this->getSetting('empty_label_behavior') == self::EMPTY_LABEL_KEEP) {
      $build['data'] = [
        [
          'data' => ['#markup' => ''],
          'header' => TRUE,
        ],
        [
          'data' => $element,
        ],
      ];
    }

    // Otherwise we merge the cells.
    else {
      $build['data'] = [
        [
          'data' => [$element],
          'colspan' => 2,
        ],
      ];
    }

    if (isset($element['#field_name'])) {
      $build['class'][] = Html::cleanCssIdentifier($element['#field_name']);
    }
    if (isset($element['#field_type'])) {
      $build['class'][] = 'type-' . Html::cleanCssIdentifier($element['#field_type']);
    }

    return $build;
  }

  /**
   * Build table row for a "form" context.
   *
   * @param array $element
   *   Rendering array of an element.
   *
   * @return array
   *   Table row for a "form" context.
   */
  protected function buildRowForm(array $element) {
    $title_data = $this->getElementTitleData($element);
    $build = [];

    if ($title_data['title'] || $this->getSetting('empty_label_behavior') == self::EMPTY_LABEL_KEEP) {
      if (!$this->getSetting('always_show_field_label')) {
        $this->hideElementTitle($element);
      }
      $build['data'] = [
        [
          'data' =>
          [
            '#type' => 'item',
            '#title' => $title_data['title'],
            '#required' => $title_data['required'],
          ],
          'header' => TRUE,
        ],
        [
          'data' => $element,
        ],
      ];
    }
    else {
      $build['data'] = [
        [
          'data' => [$element],
          'colspan' => 2,
        ],
      ];
    }

    if (isset($element['widget'], $element['widget']['#field_name'])) {
      $build['class'][] = Html::cleanCssIdentifier($element['widget']['#field_name']);
    }

    return $build;
  }

  /**
   * Return table header.
   *
   * @return array
   *   Table header.
   */
  protected function getTableHeader() {
    $header = [];

    if ($this->getSetting('first_column') || $this->getSetting('second_column')) {
      $header = [
        $this->getSetting('first_column'),
        $this->getSetting('second_column'),
      ];
    }

    return $header;
  }

  /**
   * Build header content (before table).
   *
   * @param string $type
   *   Type of additional content.
   *
   * @return array
   *   Table header content.
   */
  protected function buildAdditionalContent($type) {
    $build = ['#type' => 'container'];

    switch ($type) {

      case self::ADD_CONTENT_HEADER:
        $build['#attributes']['class'][] = 'table-header';
        $visibility = self::DISPLAY_ABOVE;
        break;

      case self::ADD_CONTENT_FOOTER:
        $build['#attributes']['class'][] = 'table-footer';
        $visibility = self::DISPLAY_BELOW;
        break;

      default:
        $visibility = self::DISPLAY_HIDDEN;
        break;
    }

    if ($this->getSetting('label_visibility') == $visibility) {
      $build['label'] = [
        '#type' => 'label',
        '#title' => $this->group->label,
        '#title_display' => 'above',
        '#attributes' => ['class' => 'table-label'],
      ];
    }

    if ($this->getSetting('desc_visibility') == $visibility && $this->getSetting('desc')) {
      $build['desc'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => Xss::filter($this->getSetting('desc')),
        '#attributes' => ['class' => ['table-desc']],
      ];
    }

    return count($build) > 2 ? $build : [];
  }

  /**
   * Return title of a requested element.
   *
   * @param array $element
   *   Element definition.
   * @param int $lvl
   *   Current depth.
   *
   * @return array
   *   Title and Required status of a requested element.
   */
  protected function getElementTitleData(array $element, $lvl = 0) {
    $title = isset($element['#title']) ? $element['#title'] : '';
    $required = isset($element['#required']) ? $element['#required'] : FALSE;

    if (!$title && $lvl < 9) {
      $children = Element::children($element);
      $lvl++;

      foreach ($children as $child) {
        if ($result = $this->getElementTitleData($element[$child], $lvl)) {
          return $result;
        }
      }
    }

    return [
      'title' => $title,
      'required' => $required,
    ];
  }

  /**
   * Hide title of a requested element.
   *
   * @param array $element
   *   Element definition.
   * @param int $lvl
   *   Current depth.
   */
  protected function hideElementTitle(array &$element, $lvl = 0) {

    if (isset($element['#title'])) {
      switch ($this->context) {

        case 'view':
          if (isset($element['#label_display']) && $element['#label_display'] === 'above') {
            $element['#label_display'] = 'hidden';
          }
          break;

        case 'form':
          if (!isset($element['#type']) || !in_array($element['#type'], ['radio', 'checkbox'])) {
            $element['#title_display'] = 'invisible';
          }
          break;
      }
    }

    if (($children = Element::children($element)) && $lvl < 9) {
      $lvl++;

      foreach ($children as $child) {
        $this->hideElementTitle($element[$child], $lvl);
      }
    }
  }

}
