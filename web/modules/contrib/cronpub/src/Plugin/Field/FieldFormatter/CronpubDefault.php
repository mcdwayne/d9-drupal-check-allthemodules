<?php

/**
 * @file
 * Contains \Drupal\cronpub\Plugin\Field\FieldFormatter\CronpubDefault.
 */

namespace Drupal\cronpub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'cronpub_default' formatter.
 *
 * @FieldFormatter(
 *   id = "cronpub_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "cronpub_field_type"
 *   }
 * )
 */
class CronpubDefault extends FormatterBase {

  /**
   * The filter options for output dates.
   *
   * @var array
   */
  protected $filterOptions;

  /**
   * Constructs a FormatterBase object.
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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->filterOptions = [
      'all' => $this->t('All events'),
      'all_start' => $this->t('All events beginning'),
      'all_end' => $this->t('All events ending'),
      'upcoming' => $this->t('Upcoming events'),
      'upcoming_start' => $this->t('Upcoming events beginning'),
      'upcoming_end' => $this->t('Upcoming events ending'),
      'past' => $this->t('Past events'),
      'past_start' => $this->t('Past events beginning'),
      'past_end' => $this->t('Past events ending'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'title' => 'Next actions',
        'string_formatter' => '%action on %date.',
        'date_formatter' => 'fallback',
        'limit' => 1,
        'filter' => 'all',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title to display'),
      '#default_value' => $this->getSetting('title'),
    ];
    $elements['string_formatter'] = [
      '#type' => 'textfield',
      '#title' => t('String formatter for text output'),
      '#default_value' => $this->getSetting('string_formatter'),
      '#description' => t('Text output as a formatted string. Use <em>%action, %date</em> as placeholder for plugin label and formatted date string.'),
    ];

    /* @var $date_formater \Drupal\Core\Config\Entity\ConfigEntityStorage */
    $date_formaters = \Drupal::service('entity_type.manager')
      ->getStorage('date_format')->loadMultiple();
    $date_format_opts = [];
    foreach ($date_formaters as $key => $formatter) {
      /* @var  $formatter \Drupal\Core\Datetime\Entity\DateFormat */
      $date_format_opts[$key] = $formatter->label();
    }
    $elements['date_formatter'] = array(
      '#type' => 'select',
      '#title' => t('Date formatter'),
      '#default_value' => $this->getSetting('date_formatter') ?: 1,
      '#options' => $date_format_opts,
      '#description' => t('Select date formatter to use.'),
    );
    $elements['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of events'),
      '#description' => $this->t('How many time intervals units to show in output. (1-200)'),
      '#default_value' => $this->getSetting('limit') ?: 1,
      '#min' => 1,
      '#max' => 200,
    ];

    $elements['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Eventfilter'),
      '#description' => $this->t('How many time interval units should be shown in the formatted output.'),
      '#default_value' => $this->getSetting('filter') ?: 'all',
      '#options' => $this->filterOptions,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Title: "%title"',
      ['%title' => $this->getSetting('title')]);
    $summary[] = $this->t('Display %num dates with filter: %filter', [
      '%num' => $this->getSetting('limit') ?: 1,
      '%filter' => $this->filterOptions[$this->getSetting('filter')]
      ]);
    $summary[] = $this->t('Formatter: "%formatted"',
      ['%formatted' => $this->getSetting('string_formatter')]);
    $summary[] = $this->t('Date format: "%formatted"',
      ['%formatted' => $this->getSetting('date_formatter')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $item) {

      /* @var $item \Drupal\cronpub\Plugin\Field\FieldType\CronpubFieldType */
      /* @var $cp_entity \Drupal\cronpub\Entity\CronpubEntity */
      $cp_entity = $item->getCronpubEntity();


      if ($cp_entity) {
        $cronology = $cp_entity->getChronology();
        $filter = $this->getSetting('filter');
        $plugin_definition = $cp_entity->getPluginDefinition();

        if (preg_match('/^upcoming/', $filter) == 1) {
          $cronology = array_filter($cronology,'self::upcomingFilterCallback', ARRAY_FILTER_USE_BOTH);
        }
        elseif (preg_match('/^past/', $filter) == 1) {
          $cronology = array_filter($cronology,'self::pastFilterCallback', ARRAY_FILTER_USE_BOTH);
        }


        if (preg_match('/start$/', $filter) == 1) {
          $cronology = array_filter($cronology,'self::startFilterCallback', ARRAY_FILTER_USE_BOTH);
        }
        elseif (preg_match('/end$/', $filter) == 1) {
          $cronology = array_filter($cronology,'self::endFilterCallback', ARRAY_FILTER_USE_BOTH);
        }

        if ($limit = $this->getSetting('limit')) {
          $cronology = array_slice($cronology, 0, $this->getSetting('limit'), true);
        }

        $collector = [
          '#theme' => 'cronpub_formatted_output',
          '#title' => $this->getSetting('title'),
          '#datelist' => [],
        ];
        $string_formatter = $this->getSetting('string_formatter');
        $date_formatter = $this->getSetting('date_formatter');
        foreach ($cronology as $date => $properties) {
          $date = \Drupal::service('date.formatter')->format($date, $date_formatter);
          $action_method = $properties['job'];
          $string = $this->t($string_formatter, [
            '%action' => $plugin_definition[$action_method]['label'],
            '%date' => $date
          ]);
          $collector['#datelist'][] = ['string' => $string];
        }

        $elements[] = $collector;
      }

    }

    return $elements;
  }

  public static function upcomingFilterCallback($var) {
    return $var['state'] === 'pending';
  }
  public static function pastFilterCallback($var) {
    return $var['state'] !== 'pending';
  }
  public static function startFilterCallback($var) {
    return $var['job'] === 'start';
  }
  public static function endFilterCallback($var) {
    return $var['job'] === 'end';
  }

}
