<?php

namespace Drupal\datetime_extras\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\duration_field\Service\DurationServiceInterface;
use Drupal\duration_field\Service\GranularityServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'daterange_duration' widget.
 *
 * @FieldWidget(
 *   id = "daterange_duration",
 *   label = @Translation("Date and time range with duration"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeDurationWidget extends DateRangeDefaultWidget {

  /**
   * The duration service.
   *
   * @var \Drupal\duration_field\Service\DurationServiceInterface
   */
  protected $durationService;

  /**
   * The duration service.
   *
   * @var \Drupal\duration_field\Service\GranularityServiceInterface
   */
  protected $granularityService;

  /**
   * Sets the duration service.
   */
  public function setDurationService(DurationServiceInterface $duration_service) {
    $this->durationService = $duration_service;
  }

  /**
   * Sets the granularity service.
   */
  public function setGranularityService(GranularityServiceInterface $granularity_service) {
    $this->granularityService = $granularity_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    // Use setter injection to be immune from changes to the parent constructor.
    // @see https://www.previousnext.com.au/blog/safely-extending-drupal-8-plugin-classes-without-fear-of-constructor-changes
    $instance->setDurationService($container->get('duration_field.service'));
    $instance->setGranularityService($container->get('duration_field.granularity.service'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_duration' => [],
      'duration_granularity' => 'd:h:i',
      'time_increment' => '1',
    ] + parent::defaultSettings();
  }

  /**
   * Return the possible options for time increments.
   *
   * @return array
   *   Valid options for time increments, keyed by seconds, values are labels.
   */
  protected function getTimeIncrementOptions() {
    return [
      1 => $this->t('1 second'),
      30 => $this->t('30 seconds'),
      60 => $this->t('1 minute'),
      300 => $this->t('5 minutes'),
      600 => $this->t('10 minutes'),
      900 => $this->t('15 minutes'),
      1800 => $this->t('30 minutes'),
      3600 => $this->t('1 hour'),
      86400 => $this->t('1 day'),
    ];
  }

  /**
   * Returns the current value of the default duration setting as an interval.
   *
   * @return \DateInterval
   *   The current value of the default duration setting.
   */
  protected function getDefaultDurationInterval() {
    $default_duration = $this->getSetting('default_duration');
    return $this->durationService->convertDateArrayToDateInterval($default_duration);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['duration_granularity'] = [
      '#type' => 'granularity',
      '#title' => t('Duration granularity'),
      '#default_value' => $this->getSetting('duration_granularity'),
    ];
    $element['default_duration'] = [
      '#type' => 'duration',
      '#title' => t('Default duration'),
      '#default_value' => $this->getDefaultDurationInterval(),
      '#granularity' => $this->getSetting('duration_granularity'),
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      // Blast the default #element_validate callback to leave this duration
      // as an array (and don't convert it into a DateInterval object), so we
      // can save it to config storage.
      // @see https://www.drupal.org/project/duration_field/issues/3020681
      '#element_validate' => [],
    ];
    $element['time_increment'] = [
      '#type' => 'select',
      '#title' => $this->t('Time increment'),
      '#default_value' => $this->getSetting('time_increment'),
      '#options' => $this->getTimeIncrementOptions(),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $default_duration_interval = $this->getDefaultDurationInterval();
    $duration_granularity = $this->getSetting('duration_granularity');
    $time_increment = $this->getSetting('time_increment');
    $increment_options = $this->getTimeIncrementOptions();
    $summary = [];
    // Annoyingly, GranularityService::getHumanReadableStringFromDateInterval()
    // expects the granularity as an array, but everything else stores/expects
    // it as a string. So, we have to invoke the granularity service to convert
    // the string into the granularity array.
    $granularity_array = $this->granularityService->convertGranularityStringToGranularityArray($duration_granularity);
    $default_duration = $this->durationService->getHumanReadableStringFromDateInterval($default_duration_interval, $granularity_array, ' ', 'short');
    $summary['default_duration'] = $this->t('Default duration: @duration', ['@duration' => $default_duration]);
    $summary['duration_granularity'] = $this->t('Duration granularity: @granularity', ['@granularity' => $duration_granularity]);
    $summary['time_increment'] = $this->t('Time increment : @increment', ['@increment' => $increment_options[$time_increment]]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $increment = $this->getSetting('time_increment');
    foreach (['value', 'end_value'] as $sub_element) {
      $form_element[$sub_element]['#date_increment'] = $increment;
      // If the increment is in days, don't collect time at all.
      if ($increment >= 86400) {
        $form_element[$sub_element]['#date_time_format'] = '';
        $form_element[$sub_element]['#date_time_element'] = 'none';
        $form_element[$sub_element]['#date_time_callbacks'] = [];
      }
    }
    // Since the user will probably define a duration, not an end time, mark the
    // element unrequired. We'll force a value during our custom validation.
    $form_element['end_value']['#required'] = FALSE;
    $item = $items[$delta];
    if ($item->start_date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->start_date;
    }
    if ($item->end_date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->end_date;
    }
    if (!empty($start_date) && !empty($end_date)) {
      $interval = $start_date->diff($end_date);
    }
    $form_element['end_type'] = [
      '#type' => 'radios',
      '#options' => [
        'duration' => $this->t('Duration'),
        'end_date' => $this->t('End date'),
      ],
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#default_value' => 'duration',
      '#weight' => '-5',
    ];
    $form_element['value']['#weight'] = '-10';
    $form_element['end_value']['#weight'] = '0';

    $end_type_name = $this->fieldDefinition->getName() . '[' . $delta . '][end_type]';
    // Use #states to hide the end_value if we're using a duration.
    // Sadly [#2419131] means #states doesn't work directly on a datetime.
    // @todo This hack breaks the label for end_value.
    // @see https://www.drupal.org/node/3026456
    $form_element['end_value']['#theme_wrappers'] = ['container'];
    $form_element['end_value']['#states']['visible'][] = [
      ':input[name="' . $end_type_name . '"]' => ['value' => 'end_date'],
    ];
    $form_element['duration'] = [
      '#type' => 'duration',
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#granularity' => $this->getSetting('duration_granularity'),
      // No harm setting this here. It'll all Just Work(tm) if duration_field
      // starts supporting it (or sites apply the working patch themselves).
      // Otherwise, it's simply ignored.
      // @see https://www.drupal.org/project/duration_field/issues/3020676
      '#date_increment' => $increment,
      '#weight' => '10',
      '#states' => [
        'visible' => [
          ':input[name="' . $end_type_name . '"]' => ['value' => 'duration'],
        ],
      ],
    ];
    // Set the default duration. If we already have an end_date value, use that.
    // Otherwise, use the default duration from the widget settings.
    if (empty($interval)) {
      $interval = $this->getDefaultDurationInterval();
    }
    $form_element['duration']['#default_value'] = $interval;

    // Add #validate callback to set the end_value from duration. We want our
    // #element_validate to run first, so put it at the front of the array.
    array_unshift($form_element['#element_validate'], [get_class($this), 'validateDuration']);
    return $form_element;
  }

  /**
   * If the widget is using duration, update end_value for further validation.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDuration(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($element['end_type']['#value'] === 'duration') {
      if (!empty($element['value']['#value']['object'])
          && $element['value']['#value']['object'] instanceof DrupalDateTime
          && !empty($element['duration']['#value'])
      ) {
        // Get a DrupalDateTime for the start time plus the duration offset.
        $date = clone($element['value']['#value']['object']);
        $date->add($element['duration']['#value']);
        // Set the end_value via form_state so it persists to submit handlers.
        $end_element['#parents'] = array_merge($element['#parents'], ['end_value']);
        $form_state->setValueForElement($end_element, $date);
        // Also set the end_value's #value so that the new end_value is
        // available as other #validate callbacks happen, especially
        // DateRangeWidgetBase::validateStartEnd().
        $element['end_value']['#value'] = [
          'date' => $date->format(DateFormat::load('html_date')->getPattern()),
          'time' => $date->format(DateFormat::load('html_time')->getPattern()),
          'object' => $date,
        ];
      }
    }
    elseif (!empty($element['#required']) && empty($element['end_value']['#value']['object'])) {
      $form_state->setError($element, t('You must define either a duration or an end date.'));
    }
  }

}
