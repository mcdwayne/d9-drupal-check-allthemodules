<?php

namespace Drupal\datetime_range_timezone\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the single date plugin.
 *
 * @FieldFormatter(
 *   id = "daterange_timezone_single_date",
 *   label = @Translation("Single Date"),
 *   field_types = {
 *     "daterange_timezone"
 *   }
 * )
 */
class DateRangeTimezoneSingleDate extends FormatterBase implements ContainerFactoryPluginInterface {

  use DateRangeTimezoneTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new DateRangeTimezone.
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
   *   Third party settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format entity storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    foreach ($items as $delta => $item) {
      $date_field = $this->getSetting('date_field');
      $date = $item->{$date_field} ? $this->formatDate($item->{$date_field}, $item->timezone) : '';
      $timezone = $this->getSetting('display_timezone') && $item->timezone ? ' ' . system_time_zones()[$item->timezone] : NULL;
      $build[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => sprintf('%s%s', $date, $timezone),
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format_type' => 'medium',
      'date_field' => 'start_date',
      'display_timezone' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Date field'),
      '#description' => $this->t('Choose the date to display, either the start or end date.'),
      '#options' => [
        'start_date' => $this->t('Start Date'),
        'end_date' => $this->t('End Date'),
      ],
      '#default_value' => $this->getSetting('date_field'),
    ];

    $form['format_type'] = [
      '#type' => 'select',
      '#title' => t('Date format'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $this->getDateFormatOptions(),
      '#default_value' => $this->getSetting('format_type'),
    ];

    $form['display_timezone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Timezone'),
      '#description' => $this->t('Should we display the timezone after the formatted date?'),
      '#default_value' => $this->getSetting('display_timezone'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    $summary[] = $this->t('Format: @display', ['@display' => $this->formatDate($date)]);

    $summary[] = $this->t('@action the timezone', [
      '@action' => $this->getSetting('display_timezone') ? 'Showing' : 'Hiding',
    ]);

    return $summary;
  }

}
