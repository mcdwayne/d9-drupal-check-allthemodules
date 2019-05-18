<?php

namespace Drupal\date_recur_opening_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;

/**
 * Plugin implementation of the 'date_recur_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "date_recur_opening_hours_formatter",
 *   label = @Translation("Opening hours"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurOpeningHoursFormatter extends DateRangeDefaultFormatter {

  /**
   * Option for first day to display as first day of the week.
   */
  public const FIRST_DAY_SYSTEM = 'system';

  /**
   * Option for first day to display as current day.
   */
  public const FIRST_DAY_TODAY = 'today';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DateRecurOpeningHoursFormatter.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $date_formatter, $date_format_storage);
    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() : array {
    return [
      'first_day' => static::FIRST_DAY_SYSTEM,
      'timespan' => 60 * 60 * 24 * 7,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) : array {
    $form = parent::settingsForm($form, $form_state);

    $form['first_day'] = [
      '#type' => 'radios',
      '#options' => [
        static::FIRST_DAY_SYSTEM => $this->t('First day of the week'),
        static::FIRST_DAY_TODAY => $this->t('Current day'),
      ],
      '#title' => $this->t('First day'),
      '#description' => $this->t('Which day to show first.'),
      '#default_value' => $this->getSetting('first_day'),
    ];

    $form['timezone_override']['#access'] = FALSE;
    $form['format_type']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) : array {
    $cachable = (new CacheableMetadata())
      ->addCacheContexts(['timezone']);

    // Cache until midnight tomorrow, in this users timezone.
    $tomorrow = (new DrupalDateTime('tomorrow'));
    $now = (new DrupalDateTime('now'));
    $cachable->setCacheMaxAge($tomorrow->getTimestamp() - $now->getTimestamp());

    // Today, in users timezone.
    $today = (new DrupalDateTime('monday'))->format('w');

    switch ($this->getSetting('first_day')) {
      case static::FIRST_DAY_SYSTEM:
        // Weekday int. 0-6 (Sun-Sat).
        $firstDayInt = $this->configFactory->get('system.date')
          ->get('first_day');
        break;

      case static::FIRST_DAY_TODAY:
        $firstDayInt = $today;
        break;
    }

    $dayMap = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    ];
    $firstDayStr = $dayMap[$firstDayInt];
    $weekStartString = (!($today == $firstDayInt) ? 'last ' : '') . $firstDayStr . ' 00:00';

    // Create a datetime object in users timezone.
    $dt = new DrupalDateTime($weekStartString);

    $interval = new \DateInterval('PT' . $this->getSetting('timespan') . 'S');
    $dtend = clone $dt;
    $dtend->add($interval);

    // Convert to PHP date time as date_recur expects it.
    // See https://www.drupal.org/project/date_recur/issues/2967636
    // Fix in Drupal 8.6.x https://www.drupal.org/node/2936388.
    $php_dt = new \DateTime($dt->format('r'));
    $php_dtend = new \DateTime($dtend->format('r'));

    $occurrences = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */

      // Occurrences uses PHP date time.
      // If you pass DrupalDateTime then it will never terminate because you
      // cannot compare DrupalDateTime with DateTime.
      $itemOccurrences = $item->getOccurrenceHandler()
        ->getOccurrencesForDisplay($php_dt, $php_dtend);
      array_push($occurrences, ...$itemOccurrences);
    }

    // Order occurrences by start time.
    usort($occurrences, function ($a, $b) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $aStart */
      ['value' => $aStart] = $a;
      /** @var \Drupal\Core\Datetime\DrupalDateTime $bStart */
      ['value' => $bStart] = $b;
      return ($aStart < $bStart) ? -1 : 1;
    });

    // Reformat occurrences from field columns to something sensible.
    $occurrences = array_map(function (array $occurrence) {
      return [
        'start' => $occurrence['value'],
        'end' => $occurrence['end_value'],
      ];
    }, $occurrences);

    $elements = [];
    $cachable->applyTo($elements);
    $elements[0] = [
      '#theme' => 'date_recur_opening_hours_list',
      '#date_ranges' => $occurrences,
      '#time_separator' => $this->getSetting('separator'),
    ];

    return $elements;
  }

}
