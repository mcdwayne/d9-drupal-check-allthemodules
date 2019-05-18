<?php

namespace Drupal\date_ap_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\date_ap_style\ApStyleDateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 *
 * @FieldFormatter(
 *   id = "daterange_ap_style",
 *   label = @Translation("AP Style"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class ApStyleDateRangeFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use DateTimeRangeTrait;

  /**
   * The date formatter.
   *
   * @var \Drupal\date_ap_style\ApStyleDateFormatter
   */
  protected $apStyleDateFormatter;

  /**
   * Constructs a TimestampAgoFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\date_ap_style\ApStyleDateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ApStyleDateFormatter $date_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->apStyleDateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date_ap_style.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $config = \Drupal::config('date_ap_style.dateapstylesettings');
    $base_defaults = [
      'always_display_year' => $config->get('always_display_year'),
      'display_day' => $config->get('display_day'),
      'use_today' => $config->get('use_today'),
      'cap_today' => $config->get('cap_today'),
      'display_time' => $config->get('display_time'),
      'time_before_date' => $config->get('time_before_date'),
      'use_all_day' => $config->get('use_all_day'),
      'display_noon_and_midnight' => $config->get('display_noon_and_midnight'),
      'capitalize_noon_and_midnight' => $config->get('capitalize_noon_and_midnight'),
      'timezone' => $config->get('timezone'),
      'separator' => $config->get('separator'),
    ];
    return $base_defaults + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['always_display_year'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always display year'),
      '#description' => $this->t('When unchecked, the year will not be displayed if the date is in the same year as the current date.'),
      '#default_value' => $this->getSetting('always_display_year'),
    ];

    $elements['use_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use today'),
      '#default_value' => $this->getSetting('use_today'),
    ];

    $elements['cap_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize today'),
      '#default_value' => $this->getSetting('cap_today'),
    ];

    $elements['display_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time'),
      '#default_value' => $this->getSetting('display_time'),
    ];

    $elements['time_before_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time before date'),
      '#description' => $this->t('When checked, the time will be displayed before the date. Otherwise it will be displayed after the date.'),
      '#default_value' => $this->getSetting('time_before_date'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['use_all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "All Day" instead of midnight'),
      '#default_value' => $this->getSetting('use_all_day'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
        ],
        'unchecked' => [
          ':input[name$="[settings][display_noon_and_midnight]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['display_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display noon and midnight'),
      '#default_value' => $this->getSetting('display_noon_and_midnight'),
      '#description' => $this->t('Converts 12:00 p.m. to "noon" and 12:00 a.m. to "midnight".'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
        ],
        'unchecked' => [
          ':input[name$="[settings][use_all_day]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['capitalize_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize noon and midnight'),
      '#default_value' => $this->getSetting('capitalize_noon_and_midnight'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
          ':input[name$="[settings][display_noon_and_midnight]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Date range separator'),
      '#options' => [
        'endash' => $this->t('En dash'),
        'to' => $this->t('to'),
      ],
      '#default_value' => $this->getSetting('separator'),
    ];

    $elements['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => ['' => $this->t('- Default site/user time zone -')] + system_time_zones(FALSE),
      '#default_value' => $this->getSetting('timezone'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('always_display_year')) {
      $summary[] = $this->t('Always displaying year');
    }

    if ($this->getSetting('display_day')) {
      $summary[] = $this->t('Displaying day of the week');
    }

    if ($this->getSetting('use_today')) {
      $today = 'Displaying today';
      if ($this->getSetting('cap_today')) {
        $today .= ' (capitalized)';
      }
      $summary[] = $this->t($today);
    }

    if ($this->getSetting('display_time')) {
      $display_time = $this->t('Displaying time');
      if ($this->getSetting('time_before_date')) {
        $display_time .= ' (before date)';
      }
      else {
        $display_time .= ' (after date)';
      }
      $summary[] = $display_time;
      if ($this->getSetting('use_all_day')) {
        $summary[] = 'Show "All Day" instead of midnight';
      }
      elseif ($this->getSetting('display_noon_and_midnight')) {
        $noon_and_midnight = 'Displaying noon and midnight';
        if ($this->getSetting('capitalize_noon_and_midnight')) {
          $noon_and_midnight .= ' (capitalized)';
        }
        $summary[] = $this->t($noon_and_midnight);
      }
    }

    if ($this->getSetting('separator') == 'endash') {
      $summary[] = $this->t('Using en dash date range separator');
    }
    else {
      $summary[] = $this->t('Using "to" date range separator');
    }

    if ($timezone = $this->getSetting('timezone')) {
      $summary[] = $this->t('Time zone: @timezone', ['@timezone' => $timezone]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $opts = [
      'always_display_year',
      'display_day',
      'use_today',
      'cap_today',
      'display_time',
      'time_before_date',
      'use_all_day',
      'display_noon_and_midnight',
      'capitalize_noon_and_midnight',
    ];

    $options = [];
    foreach ($opts as $opt) {
      if ($this->getSetting($opt)) {
        $options[$opt] = TRUE;
      }
    }

    $timezone = $this->getSetting('timezone') ?: NULL;

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        $dates['start'] = $start_date->getTimestamp();
        $dates['end'] = $end_date->getTimestamp();

        $elements[$delta] = [
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
          '#markup' => $this->apStyleDateFormatter->formatRange($dates, $options, $timezone, $langcode),
        ];
      }
    }

    return $elements;
  }

}
