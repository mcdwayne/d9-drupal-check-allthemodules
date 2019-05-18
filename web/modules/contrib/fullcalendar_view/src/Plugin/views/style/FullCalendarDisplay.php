<?php

namespace Drupal\fullcalendar_view\Plugin\views\style;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\fullcalendar_view\TaxonomyColor;
use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render content for FullCalendar.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "fullcalendar_view_display",
 *   title = @Translation("Full Calendar Display"),
 *   help = @Translation("Render contents in Full Calendar view."),
 *   theme = "views_view_fullcalendar",
 *   display_types = { "normal" }
 * )
 */
class FullCalendarDisplay extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected $taxonomyColorService;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\fullcalendar_view\TaxonomyColor $taxonomyColorService
   *   The Taxonomy Color Service object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TaxonomyColor $taxonomyColorService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->taxonomyColorService = $taxonomyColorService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('fullcalendar_view.taxonomy_color'));
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['default_date_source'] = ['default' => 'now'];
    $options['defaultDate'] = ['default' => ''];
    $options['start'] = ['default' => ''];
    $options['end'] = ['default' => ''];
    $options['des'] = ['default' => ''];
    $options['title'] = ['default' => ''];
    $options['use_entity_fields'] = ['default' => TRUE];
    $options['business_start'] = ['default' => ''];
    $options['business_end'] = ['default' => ''];
    $options['bundle_type'] = ['default' => ''];
    $options['tax_field'] = ['default' => ''];
    $options['color_bundle'] = ['default' => []];
    $options['color_taxonomies'] = ['default' => []];
    $options['vocabularies'] = ['default' => ''];
    $options['right_buttons'] = [
      'default' =>
        [
          'agendaWeek' => 'agendaWeek',
          'agendaDay' => 'agendaDay',
          'listYear' => 'listYear',
        ],
    ];
    $options['default_view'] = ['default' => 'month'];
    $options['nav_links'] = ['default' => 1];
    $options['defaultLanguage'] = ['default' => 'en'];
    $options['languageSelector'] = ['default' => 0];
    $options['alloweventOverlap'] = ['default' => 1];
    $options['updateConfirm'] = ['default' => 1];
    $options['createEventLink'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the grouping setting.
    if (isset($form['grouping'])) {
      unset($form['grouping']);
    }
    $form['default_date_source'] = [
      '#type' => 'radios',
      '#options' => [
        'now' => t('Current date'),
        'first' => t('Date of first view result'),
        'fixed' => t('Fixed value'),
      ],
      '#title' => t('Default date source'),
      '#default_value' => (isset($this->options['default_date_source'])) ? $this->options['default_date_source'] : '',
      '#description' => t('Source of the initial date displayed when the calendar first loads.'),
    ];
    // Default date of the calendar.
    $form['defaultDate'] = [
      '#type' => 'date',
      '#title' => t('Default date'),
      '#default_value' => (isset($this->options['defaultDate'])) ? $this->options['defaultDate'] : '',
      '#description' => t('Fixed initial date displayed when the calendar first loads.'),
      '#states' => [
        'visible' => [
          [':input[name="style_options[default_date_source]"]' => ['value' => 'fixed']],
        ],
      ],
    ];
    // All selected fields.
    $field_names = $this->displayHandler->getFieldLabels();
    $entity_type = $this->view->getBaseEntityType()->id();
    // Field name of start date.
    $form['start'] = [
      '#title' => $this->t('Start Date Field'),
      '#type' => 'select',
      '#options' => $field_names,
      '#default_value' => (!empty($this->options['start'])) ? $this->options['start'] : '',
    ];
    // Field name of end date.
    $form['end'] = [
      '#title' => $this->t('End Date Field'),
      '#type' => 'select',
      '#options' => $field_names,
      '#empty_value' => '',
      '#default_value' => (!empty($this->options['end'])) ? $this->options['end'] : '',
    ];
    // Field name of title.
    $form['title'] = [
      '#title' => $this->t('Title Field'),
      '#type' => 'select',
      '#options' => $field_names,
      '#default_value' => (!empty($this->options['title'])) ? $this->options['title'] : '',
    ];
    // Field for description.
    $form['des'] = [
      '#title' => $this->t('Description Field'),
      '#description' => t('Description for event tooltip. If select none, there will not be popup tooltip. For multiple content types, you can select multiple fields here.'),
      '#type' => 'select',
      '#options' => $field_names,
      '#empty_value' => '',
      '#default_value' => (!empty($this->options['des'])) ? $this->options['des'] : '',
      '#multiple' => TRUE,
    ];
    // Display settings.
    $form['display'] = [
      '#type' => 'details',
      '#title' => t('Display'),
      '#description' => t('Calendar display settings.'),
    ];
    $form['use_entity_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use entity fields'),
      '#description' => $this->t('Use entity fields for rendering rather than the raw view fields. This is checked by default to prevent legacy views from breaking. However, it will prevent using views rewriting and does not support non-standard date fields (fields other than timestamp, datetime and daterange).'),
      '#fieldset' => 'display',
      '#default_value' => $this->options['use_entity_fields'],
    ];
    // Right side buttons.
    $form['right_buttons'] = [
      '#type' => 'checkboxes',
      '#fieldset' => 'display',
      '#options' => [
        'agendaWeek' => $this->t('Week'),
        'agendaDay' => $this->t('Day'),
        'listYear' => $this->t('List'),
      ],
      '#default_value' => (empty($this->options['right_buttons'])) ? [] : $this->options['right_buttons'],
      '#title' => $this->t('Right side buttons'),
    ];
    // Default view.
    // Todo: filter out disabled view from options.
    $form['default_view'] = [
      '#type' => 'radios',
      '#fieldset' => 'display',
      '#options' => [
        'month' => $this->t('Month'),
        'agendaWeek' => $this->t('Week'),
        'agendaDay' => $this->t('Day'),
        'listYear' => $this->t('List'),
      ],
      '#default_value' => (empty($this->options['default_view'])) ? 'month' : $this->options['default_view'],
      '#title' => $this->t('Default view'),
    ];
    // Nav Links.
    $form['nav_links'] = [
      '#type' => 'checkbox',
      '#fieldset' => 'display',
      '#default_value' => (!isset($this->options['nav_links'])) ? 1 : $this->options['nav_links'],
      '#title' => $this->t('Day/Week are links'),
      '#description' => t('If this option is selected, day/week names will be linked to navigation views.'),
    ];
    // Allow/disallow event overlap.
    $form['alloweventOverlap'] = [
      '#type' => 'checkbox',
      '#fieldset' => 'display',
      '#default_value' => (!isset($this->options['alloweventOverlap'])) ? 1 : $this->options['alloweventOverlap'],
      '#title' => $this->t('Allow calendar events to overlap'),
      '#description' => t('If this option is selected, calendar events are allowed to overlap (default).'),
    ];
    // Event update JS confirmation dialog.
    $form['updateConfirm'] = [
      '#type' => 'checkbox',
      '#fieldset' => 'display',
      '#default_value' => (!isset($this->options['updateConfirm'])) ? 1 : $this->options['updateConfirm'],
      '#title' => $this->t('Event update confirmation pop-up dialog.'),
      '#description' => t('If this option is selected, a confirmation dialog will pop-up after dragging and dropping an event.'),
    ];
    // Lanugage and Localization.
    $locale = [
      'en' => 'English',
      'af' => 'Afrikaans',
      'ar-dz' => 'Arabic - Algeria',
      'ar-kw' => 'Arabic - Kuwait',
      'ar-ly' => 'Arabic - Libya',
      'ar-ma' => 'Arabic - Morocco',
      'ar-sa' => 'Arabic - Saudi Arabia',
      'ar-tn' => 'Arabic - Tunisia',
      'ar' => 'Arabic',
      'bg' => 'Bulgarian',
      'ca' => 'Catalan',
      'cs' => 'Czech',
      'da' => 'Danish',
      'de-at' => 'German - Austria',
      'de-ch' => 'German - Switzerland',
      'de' => 'German',
      'el' => 'Greek',
      'en-au' => 'English - Australia',
      'en-ca' => 'English - Canada',
      'en-gb' => 'English - United Kingdom',
      'en-ie' => 'English - Ireland',
      'en-nz' => 'English - New Zealand',
      'es-do' => 'Spanish - Dominican Republic',
      'es-us' => 'Spanish - United States',
      'es' => 'Spanish',
      'et' => 'Estonian',
      'eu' => 'Basque',
      'fa' => 'Farsi',
      'fi' => 'Finnish',
      'fr-ca' => 'French - Canada',
      'fr-ch' => 'French - Switzerland',
      'fr' => 'French',
      'gl' => 'Galician',
      'he' => 'Hebrew',
      'hi' => 'Hindi',
      'hr' => 'Croatian',
      'hu' => 'Hungarian',
      'id' => 'Indonesian',
      'is' => 'Icelandic',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'kk' => 'Kannada',
      'ko' => 'Korean',
      'lb' => 'Lebanon',
      'lt' => 'Lithuanian',
      'lv' => 'Latvian',
      'mk' => 'FYRO Macedonian',
      'ms-my' => 'Malay - Malaysia',
      'ms' => 'Malay',
      'nb' => 'Norwegian (Bokmål) - Norway',
      'nl-be' => 'Dutch - Belgium',
      'nl' => 'Dutch',
      'nn' => 'Norwegian',
      'pl' => 'Polish',
      'pt-br' => 'Portuguese - Brazil',
      'pt' => 'Portuguese',
      'ro' => 'Romanian',
      'ru' => 'Russian',
      'sk' => 'Slovak',
      'sl' => 'Slovenian',
      'sq' => 'Albanian',
      'sr-cyrl' => 'Serbian - Cyrillic',
      'sr' => 'Serbian',
      'sv' => 'Swedish',
      'th' => 'Thai',
      'tr' => 'Turkish',
      'uk' => 'Ukrainian',
      'vi' => 'Vietnamese',
      'zh-cn' => 'Chinese - China',
      'zh-tw' => 'Chinese - Taiwan',
    ];
    // Default Language.
    $form['defaultLanguage'] = [
      '#title' => $this->t('Default Language'),
      '#fieldset' => 'display',
      '#type' => 'select',
      '#options' => $locale,
      '#default_value' => (!empty($this->options['defaultLanguage'])) ? $this->options['defaultLanguage'] : 'en',
    ];
    // Language Selector Switch.
    $form['languageSelector'] = [
      '#type' => 'checkbox',
      '#fieldset' => 'display',
      '#default_value' => (empty($this->options['languageSelector'])) ? 0 : $this->options['languageSelector'],
      '#title' => $this->t('Allow client to select language.'),
    ];
    // Create new event link.
    $form['createEventLink'] = [
      '#type' => 'checkbox',
      '#fieldset' => 'display',
      '#default_value' => (empty($this->options['createEventLink'])) ? 0 : $this->options['createEventLink'],
      '#title' => $this->t('Create a new event via the Off-Canvas dialog.'),
      '#description' => t('If this option is selected, there wiil be an Add Event link below the calendar that provides the ability to create an event In-Place.'),
    ];
    // Legend colors.
    $form['colors'] = [
      '#type' => 'details',
      '#title' => t('Legend Colors'),
      '#description' => t('Set color value of legends for each content type or each taxonomy.'),
    ];
    // All vocabularies.
    $cabNames = taxonomy_vocabulary_get_names();
    // Taxonomy reference field.
    $tax_fields = [];
    // Find out all taxonomy reference fields of this View.
    foreach ($field_names as $field_name => $lable) {
      $field_conf = FieldStorageConfig::loadByName($entity_type, $field_name);
      if (empty($field_conf)) {
        continue;
      }
      if ($field_conf->getType() == 'entity_reference') {
        $tax_fields[$field_name] = $lable;
      }
    }
    // Field name of event taxonomy.
    $form['tax_field'] = [
      '#title' => $this->t('Event Taxonomy Field'),
      '#description' => t('In order to specify colors for event taxonomies, you must select a taxonomy reference field for the View.'),
      '#type' => 'select',
      '#options' => $tax_fields,
      '#empty_value' => '',
      '#disabled' => empty($tax_fields),
      '#fieldset' => 'colors',
      '#default_value' => (!empty($this->options['tax_field'])) ? $this->options['tax_field'] : '',
    ];
    // Color for vocabularies.
    $form['vocabularies'] = [
      '#title' => $this->t('Vocabularies'),
      '#type' => 'select',
      '#options' => $cabNames,
      '#empty_value' => '',
      '#fieldset' => 'colors',
      '#description' => t('Specify which vocabulary is using for calendar event color. If the vocabulary selected is not the one that the taxonomy field belonging to, the color setting would be ignored.'),
      '#default_value' => (!empty($this->options['vocabularies'])) ? $this->options['vocabularies'] : '',
      '#states' => [
        // Only show this field when the 'tax_field' is selected.
        'invisible' => [
          [':input[name="style_options[tax_field]"]' => ['value' => '']],
        ],
      ],
      '#ajax' => [
        'callback' => 'Drupal\fullcalendar_view\Plugin\views\style\FullCalendarDisplay::taxonomyColorCallback',
        'event' => 'change',
        'wrapper' => 'color-taxonomies-div',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
    ];

    if (!isset($form_state->getUserInput()['style_options'])) {
      // Taxonomy color input boxes.
      $form['color_taxonomies'] = $this->taxonomyColorService->colorInputBoxs($this->options['vocabularies'], $this->options['color_taxonomies']);
    }
    // Content type colors.
    $form['color_bundle'] = [
      '#type' => 'details',
      '#title' => t('Colors for Bundle Types'),
      '#description' => t('Specify colors for each bundle type. If taxonomy color is specified, this settings would be ignored.'),
      '#fieldset' => 'colors',
    ];
    // All bundle types.
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
    // Options list.
    $bundlesList = [];
    foreach ($bundles as $id => $bundle) {
      $label = $bundle['label'];
      $bundlesList[$id] = $label;
      // Content type colors.
      $form['color_bundle'][$id] = [
        '#title' => $label,
        '#default_value' => isset($this->options['color_bundle'][$id]) ? $this->options['color_bundle'][$id] : '#3a87ad',
        '#type' => 'color',
      ];
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('calendar_recurring_event')) {
      // Recurring event.
      $form['recurring'] = [
        '#type' => 'details',
        '#title' => t('Recurring event settings'),
          // '#description' =>  t('Settings for recurring event.'),.
      ];
      // Recurring business start time.
      $form['business_start'] = [
        '#type' => 'datetime',
        '#title' => t('Business start time'),
        '#description' => t('The time of a day when a recurring all day event starts. The recurring events whose start date include hour and minute will use their respective start time instead.'),
        '#fieldset' => 'recurring',
        '#default_value' => empty($this->options['business_start']) ? new DrupalDateTime('2018-02-24T08:00:00') : new DrupalDateTime($this->options['business_start']),
      // Hide date element.
        '#date_date_element' => 'none',
      // You can use text element here as well.
        '#date_time_element' => 'time',
        '#date_time_format' => 'H:i',
      ];
      // Recurring business end time.
      $form['business_end'] = [
        '#type' => 'datetime',
        '#title' => t('Business end time'),
        '#description' => t('The time of a day when a recurring event ends. The recurring events whose end date include hour and minute will use their respective end time instead.'),
        '#fieldset' => 'recurring',
        '#default_value' => empty($this->options['business_end']) ? new DrupalDateTime('2018-02-24T18:00:00') : new DrupalDateTime($this->options['business_end']),
      // Hide date element.
        '#date_date_element' => 'none',
      // You can use text element here as well.
        '#date_time_element' => 'time',
        '#date_time_format' => 'H:i',
      ];
    }
    // New event bundle type.
    $form['bundle_type'] = [
      '#title' => $this->t('Event bundle (Content) type'),
      '#description' => $this->t('The bundle (content) type of a new event. Once this is set, you can create a new event by double clicking a calendar entry.'),
      '#type' => 'select',
      '#options' => $bundlesList,
      '#default_value' => (!empty($this->options['bundle_type'])) ? $this->options['bundle_type'] : '',
    ];
    // Extra CSS classes.
    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => t('CSS classes'),
      '#default_value' => (isset($this->options['classes'])) ? $this->options['classes'] : '',
      '#description' => t('CSS classes for further customization of this view.'),
    ];
  }

  /**
   * Options form submit handle function.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::submitOptionsForm()
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $options = &$form_state->getValue('style_options');
    $input_value = $form_state->getUserInput();
    $input_colors = isset($input_value['style_options']['color_taxonomies']) ? $input_value['style_options']['color_taxonomies'] : [];
    // Save the input of colors.
    foreach ($input_colors as $id => $color) {
      if (!empty($color)) {
        $options['color_taxonomies'][$id] = $color;
      }
    }
    // Datetime fields in Drupal 8 are stored as strings.
    if (isset($options['business_start'])) {
      $options['business_start'] = $options['business_start']->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }
    if (isset($options['business_end'])) {
      $options['business_end'] = $options['business_end']->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Taxonomy colors Ajax callback function.
   */
  public static function taxonomyColorCallback(array &$form, FormStateInterface $form_state) {
    $options = $form_state->getValue('style_options');
    $vid = $options['vocabularies'];
    $taxonomy_color_service = \Drupal::service('fullcalendar_view.taxonomy_color');

    if (isset($options['color_taxonomies'])) {
      $defaultValues = $options['color_taxonomies'];
    }
    else {
      $defaultValues = [];
    }
    // Taxonomy color boxes.
    $form['color_taxonomies'] = $taxonomy_color_service->colorInputBoxs($vid, $defaultValues, TRUE);

    return $form['color_taxonomies'];
  }

}
