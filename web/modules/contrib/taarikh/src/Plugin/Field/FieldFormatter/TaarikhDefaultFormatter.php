<?php

namespace Drupal\taarikh\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\taarikh\Plugin\AlgorithmPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'taarikh_date' formatter.
 *
 * @FieldFormatter(
 *   id = "taarikh_default",
 *   label = @Translation("Taarikh date and time"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class TaarikhDefaultFormatter extends DateTimeFormatterBase {

  /**
   * @var \Drupal\taarikh\Plugin\AlgorithmPluginManager
   */
  protected $taarikhAlgorithmManager;

  /**
   * Constructs a new DateTimeDefaultFormatter.
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
   * @param \Drupal\taarikh\Plugin\AlgorithmPluginManager $algorithm_plugin_manager
   *  The taarikh algorithm plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, AlgorithmPluginManager $algorithm_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $date_formatter, $date_format_storage);

    $this->taarikhAlgorithmManager = $algorithm_plugin_manager;
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
      $container->get('entity.manager')->getStorage('date_format'),
      $container->get('plugin.manager.taarikh_algorithm')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // @todo: Build settings form.
    return [
        'format_type' => 'medium',
        'algorithm' => 'fatimid_astronomical',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $algorithm = $this->taarikhAlgorithmManager->createInstance($this->getSetting('algorithm'));
    $elements = [];
    foreach ($items as $delta => $item) {
      if (!empty($item->date)) {
        $date = $algorithm->convertFromDrupalDateTime($item->date);
        $elements[$delta] = [
          '#markup' => $this->formatDate($date),
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

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date, $format_type = NULL) {
    if (empty($format_type)) {
      $format_type = $this->getSetting('format_type');
    }

    // @todo: Figure out timezone support.
//    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();

    $format = $this->dateFormatStorage->load($format_type)->getPattern();
    return $date->getFormatter()->format($format);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // Build a list of algorithms.
    $algorithms = array_map(function ($plugin_definition) {
      return $plugin_definition['title'] . ' (' . $plugin_definition['id'] . ')';
    }, $this->taarikhAlgorithmManager->getDefinitions());

    $form['algorithm'] = [
      '#type' => 'select',
      '#title' => $this->t('Algorithm'),
      '#description' => t("Choose the algorithm to use when converting the date."),
      '#options' => $algorithms,
      '#default_value' => $this->getSetting('algorithm'),
    ];

    // Build a list of format types.
    $algorithm = $this->taarikhAlgorithmManager->createInstance($this->getSetting('algorithm'));
    $date = $algorithm->convertFromDrupalDateTime(new DrupalDateTime());
    $format_options = array_map(function ($type_info) use ($date) {
      /** @var \Drupal\Core\Datetime\DateFormatInterface $type_info */
      $format = $this->formatDate($date, $type_info->id());
      return $type_info->label() . ' (' . $format . ')';
    }, $this->dateFormatStorage->loadMultiple());

    $form['format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $format_options,
      '#default_value' => $this->getSetting('format_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    /** @var \Drupal\taarikh\TaarikhAlgorithmPluginInterface $algorithm */
    $algorithm_type = $this->getSetting('algorithm');
    $algorithm_definition = $this->taarikhAlgorithmManager->getDefinition($algorithm_type);
    $algorithm = $this->taarikhAlgorithmManager->createInstance($algorithm_type);
    $date = $algorithm->convertFromDrupalDateTime(new DrupalDateTime());
    $summary[] = t('Algorithm: @display', ['@display' => $algorithm_definition['title']]);
    $summary[] = t('Format: @display', ['@display' => $this->formatDate($date)]);

    return $summary;
  }

}
