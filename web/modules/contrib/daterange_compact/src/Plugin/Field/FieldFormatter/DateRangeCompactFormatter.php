<?php

namespace Drupal\daterange_compact\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\daterange_compact\DateRangeFormatterInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Compact' formatter for 'daterange' fields.
 *
 * This formatter renders the data range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "daterange_compact",
 *   label = @Translation("Compact"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeCompactFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date range formatter service.
   *
   * @var \Drupal\daterange_compact\DateRangeFormatterInterface
   */
  protected $dateRangeFormatter;

  /**
   * The date range format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateRangeFormatStorage;

  /**
   * Constructs a new DateRangeCompactFormatter.
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
   * @param \Drupal\daterange_compact\DateRangeFormatterInterface $date_range_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_range_format_storage
   *   The date format entity storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateRangeFormatterInterface $date_range_formatter, EntityStorageInterface $date_range_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateRangeFormatter = $date_range_formatter;
    $this->dateRangeFormatStorage = $date_range_format_storage;
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
      $container->get('daterange_compact.date_range.formatter'),
      $container->get('entity_type.manager')->getStorage('date_range_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format_type' => 'medium',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $format_types = $this->dateRangeFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $options[$type] = $type_info->label();
    }

    $form['format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date and time range format'),
      '#description' => $this->t("Choose a format for displaying the date and time range."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Format: @format', ['@format' => $this->getSetting('format_type')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        $start_timestamp = $item->start_date->getTimestamp();
        $end_timestamp = $item->end_date->getTimestamp();
        $format = $this->getSetting('format_type');

        if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
          $timezone = DATETIME_STORAGE_TIMEZONE;
          $text = $this->dateRangeFormatter->formatDateRange($start_timestamp, $end_timestamp, $format, $timezone);
        }
        else {
          $timezone = drupal_get_user_timezone();
          $text = $this->dateRangeFormatter->formatDateTimeRange($start_timestamp, $end_timestamp, $format, $timezone);
        }

        $elements[$delta] = [
          '#plain_text' => $text,
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
        ];
      }
    }

    return $elements;
  }

}
