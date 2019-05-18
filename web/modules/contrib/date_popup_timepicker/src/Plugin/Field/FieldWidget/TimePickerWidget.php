<?php

namespace Drupal\date_popup_timepicker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'datetime_timepicker' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timepicker",
 *   label = @Translation("Timepicker"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class TimePickerWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $name = $items->getName();
    $name = $name . '[' . $delta . '][value][time]';
    $element['#attached']['library'][] = 'date_popup_timepicker/timepicker';
    $element['#attached']['drupalSettings']['datePopup'][$name] = [
      'settings' => self::processFieldSettings($this->getSettings()),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Define whether or not to show a leading zero for hours < 10.
      'showLeadingZero' => TRUE,
      // Define whether or not to show a leading zero for minutes < 10.
      'showMinutesLeadingZero' => TRUE,
      // Define an alternate input to parse selected time to.
      'altField' => '#alternate_input',
      // Used as default time when input field is empty or for inline
      // timePicker.
      // Set to 'now' for the current time, '' for no highlighted time.
      'defaultTime' => 'now',
      // Trigger options.
      // Define when the timepicker is shown.
      // 'focus': when the input gets focus, 'button' when the button
      // trigger element is clicked.
      // 'both': when the input gets focus and when the button is clicked.
      'showOn' => 'focus',
      // jQuery selector that acts as button trigger. ex: '#trigger_button'.
      'button' => NULL,
      // Localization.
      // Define the locale text for "Hours".
      'hourText' => 'Hour',
      // Define the locale text for "Minute".
      'minuteText' => 'Minute',
      // Define the locale text for periods.
      'amPmText' => ['AM', 'PM'],
      // Position.
      // Corner of the dialog to position, used with the jQuery UI Position
      // utility if present.
      'myPosition' => 'left top',
      // Corner of the input to position.
      'atPosition' => 'left bottom',
      // Events.
      // Callback function executed before the timepicker is
      // rendered and displayed.
      'beforeShow' => NULL,
      // Define a callback function when an hour / minutes is selected.
      'onSelect' => NULL,
      // Define a callback function when the timepicker is closed.
      'onClose' => NULL,
      // Define a callback to enable / disable certain hours.
      // ex: function onHourShow(hour).
      'onHourShow' => NULL,
      // Define a callback to enable / disable certain minutes. ex:
      // function onMinuteShow(hour, minute).
      'onMinuteShow' => NULL,
      // Custom hours and minutes.
      'hours' => [
        // First displayed hour.
        'starts' => 0,
        // Last displayed hour.
        'ends' => 23,
      ],
      'minutes' => [
        // First displayed minute.
        'starts' => 0,
        // Last displayed minute.
        'ends' => 55,
        // Interval of displayed minutes.
        'interval' => 5,
        // Optional extra entries for minutes.
        'manual' => [],
      ],
      // Number of rows for the input tables, minimum 2,
      // makes more sense if you use multiple of 2.
      'rows' => 4,
      // Define if the hours section is displayed or not.
      // Set to false to get a minute only dialog.
      'showHours' => TRUE,
      // Define if the minutes section is displayed or not.
      // Set to false to get an hour only dialog.
      'showMinutes' => TRUE,

      // Min and Max time.
      // Set the minimum time selectable by the user, disable hours and minutes
      // previous to min time.
      'minTime' => [
        'hour' => 0,
        'minute' => 0,
      ],
      // Set the minimum time selectable by the user, disable hours and minutes
      // after max time.
      'maxTime' => [
        'hour' => 23,
        'minute' => 59,
      ],
      // Buttons.
      // Shows an OK button to confirm the edit.
      'showCloseButton' => FALSE,
      // Text for the confirmation button (ok button).
      'closeButtonText' => 'Done',
      // Shows the 'now' button.
      'showNowButton' => FALSE,
      // Text for the now button.
      'nowButtonText' => 'Now',
      // Shows the deselect time button.
      'showDeselectButton' => FALSE,
      // Text for the deselect button.
      'deselectButtonText' => 'Deselect',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $options = $this->getSettings();

    $element['showLeadingZero'] = [
      '#type' => 'checkbox',
      '#title' => t('Show leading zero'),
      '#description' => t('Define whether or not to show a leading zero for hours < 10.'),
      '#default_value' => $options['showLeadingZero'],
    ];
    $element['showMinutesLeadingZero'] = [
      '#type' => 'checkbox',
      '#title' => t('Show minutes leading zero'),
      '#description' => t('Define whether or not to show a leading zero for minutes < 10.'),
      '#default_value' => $options['showMinutesLeadingZero'],
    ];
    $element['defaultTime'] = [
      '#type' => 'textfield',
      '#title' => t('Default time'),
      '#description' => t("Used as default time when input field is empty or for inline timePicker. Set to 'now' for the current time, '' for no highlighted time."),
      '#default_value' => $options['defaultTime'],
    ];
    $element['showOn'] = [
      '#type' => 'select',
      '#title' => t('Show on'),
      '#description' => t("Define when the timepicker is shown."),
      '#options' => [
        'focus' => t('Focus'),
        'button' => t('Button'),
        'both' => t('Both'),
      ],
      '#default_value' => $options['showOn'],
    ];
    $element['hourText'] = [
      '#type' => 'textfield',
      '#title' => t('Hour text'),
      '#default_value' => $options['hourText'],
    ];
    $element['minuteText'] = [
      '#type' => 'textfield',
      '#title' => t('Minute text'),
      '#default_value' => $options['minuteText'],
    ];
    $element['amPmText'] = [
      '#type' => 'fieldset',
      '#title' => t('Periods text'),
      '#collapsible' => FALSE,
      0 => [
        '#type' => 'textfield',
        '#title' => t('AM'),
        '#default_value' => $options['amPmText'][0],
      ],
      1 => [
        '#type' => 'textfield',
        '#title' => t('PM'),
        '#default_value' => $options['amPmText'][1],
      ],
    ];
    $element['hours'] = [
      '#type' => 'fieldset',
      '#title' => t('Hours'),
      '#collapsible' => FALSE,
      'starts' => [
        '#type' => 'textfield',
        '#title' => t('Starts'),
        '#description' => t('First displayed hour.'),
        '#default_value' => $options['hours']['starts'],
      ],
      'ends' => [
        '#type' => 'textfield',
        '#title' => t('Ends'),
        '#description' => t('Last displayed hour.'),
        '#default_value' => $options['hours']['ends'],
      ],
      '#element_validate' => [
        [$this, 'fieldSettingsFormValidate'],
      ],
    ];
    $element['minutes'] = [
      '#type' => 'fieldset',
      '#title' => t('Minutes'),
      '#collapsible' => FALSE,
      'starts' => [
        '#type' => 'textfield',
        '#title' => t('Starts'),
        '#description' => t('First displayed minute.'),
        '#default_value' => $options['minutes']['starts'],
      ],
      'ends' => [
        '#type' => 'textfield',
        '#title' => t('Ends'),
        '#description' => t('Last displayed minute.'),
        '#default_value' => $options['minutes']['ends'],
      ],
      'interval' => [
        '#type' => 'textfield',
        '#title' => t('Interval'),
        '#description' => t('Interval of displayed minutes.'),
        '#default_value' => $options['minutes']['interval'],
      ],
      '#element_validate' => [
        [$this, 'fieldSettingsFormValidate'],
      ],
    ];
    $element['rows'] = [
      '#type' => 'textfield',
      '#title' => t('Rows'),
      '#description' => t('Number of rows for the input tables, minimum 2, makes more sense if you use multiple of 2.'),
      '#default_value' => $options['rows'],
      '#element_validate' => [
        [$this, 'fieldSettingsFormValidate'],
      ],
    ];
    $element['showHours'] = [
      '#type' => 'checkbox',
      '#title' => t('Show hours'),
      '#description' => t('Define if the hours section is displayed or not. Set to false to get a minute only dialog.'),
      '#default_value' => $options['showHours'],
    ];
    $element['showMinutes'] = [
      '#type' => 'checkbox',
      '#title' => t('Show minutes'),
      '#description' => t('Define if the minutes section is displayed or not. Set to false to get an hour only dialog.'),
      '#default_value' => $options['showMinutes'],
    ];
    $element['minTime'] = [
      '#type' => 'fieldset',
      '#title' => t('Min time'),
      '#description' => t('Set the minimum time selectable by the user, disable hours and minutes previous to min time.'),
      '#collapsible' => FALSE,
      'hour' => [
        '#type' => 'textfield',
        '#title' => t('Min hour'),
        '#default_value' => $options['minTime']['hour'],
      ],
      'minute' => [
        '#type' => 'textfield',
        '#title' => t('Min minute'),
        '#default_value' => $options['minTime']['minute'],
      ],
      '#element_validate' => [
        [$this, 'fieldSettingsFormValidate'],
      ],
    ];
    $element['maxTime'] = [
      '#type' => 'fieldset',
      '#title' => t('Max time'),
      '#description' => t('Set the minimum time selectable by the user, disable hours and minutes after max time.'),
      '#collapsible' => FALSE,
      'hour' => [
        '#type' => 'textfield',
        '#title' => t('Max hour'),
        '#default_value' => $options['maxTime']['hour'],
      ],
      'minute' => [
        '#type' => 'textfield',
        '#title' => t('Max minute'),
        '#default_value' => $options['maxTime']['minute'],
      ],
      '#element_validate' => [
        [$this, 'fieldSettingsFormValidate'],
      ],
    ];
    $element['showCloseButton'] = [
      '#type' => 'checkbox',
      '#title' => t('Show close button'),
      '#description' => t('Shows an OK button to confirm the edit.'),
      '#default_value' => $options['showCloseButton'],
    ];
    $element['closeButtonText'] = [
      '#type' => 'textfield',
      '#title' => t('Close button text'),
      '#description' => t('Text for the confirmation button (ok button).'),
      '#default_value' => $options['closeButtonText'],
    ];
    $element['showNowButton'] = [
      '#type' => 'checkbox',
      '#title' => t('Show now button'),
      '#description' => t('Shows the "now" button.'),
      '#default_value' => $options['showNowButton'],
    ];
    $element['nowButtonText'] = [
      '#type' => 'textfield',
      '#title' => t('Now button text'),
      '#description' => t('Text for the now button.'),
      '#default_value' => $options['nowButtonText'],
    ];
    $element['showDeselectButton'] = [
      '#type' => 'checkbox',
      '#title' => t('Show deselect button'),
      '#description' => t('Shows the deselect time button.'),
      '#default_value' => $options['showDeselectButton'],
    ];
    $element['deselectButtonText'] = [
      '#type' => 'textfield',
      '#title' => t('Deselect button text'),
      '#description' => t('Text for the deselect button.'),
      '#default_value' => $options['deselectButtonText'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsFormValidate(&$element, FormStateInterface $form_state) {
    $key = $element['#parents'][count($element['#parents']) - 1];
    $copy_element_settings = $element['#parents'];
    unset($copy_element_settings[count($copy_element_settings) - 1]);
    $settings = &$form_state->getValue($copy_element_settings);
    if (isset($settings)) {
      // For two-tiered array.
      foreach ($settings[$key] as $subkey => $value) {
        // Init validation limits.
        if ($key == 'minutes' && $subkey == 'interval') {
          $limits = [1, 59];
        }
        elseif ($key == 'hours' || $subkey == 'hour') {
          $limits = [0, 23];
        }
        // Remaining things are minutes.
        else {
          $limits = [0, 59];
        }
        // Validate int hours and minutes settings.
        if ($value !== '') {
          if (!is_numeric($value) || intval($value) != $value || $value < $limits[0] || $value > $limits[1]) {
            $t_args = [
              '%name' => $element['#title'],
              '@start' => $limits[0],
              '@end' => $limits[1],
            ];
            $form_state->setErrorByName($element['#markup'], t('%name must be an integer between @start and @end.', $t_args));
          }
          else {
            $form_state->setValue($settings[$key][$subkey], (int) $value);
          }
        }
        else {
          $settings[$key][$subkey] = NULL;
        }
      }
      // For one-tiered array.
      if ($settings[$key] !== '') {
        // Validate rows part.
        if ($key === 'rows') {
          if (!is_numeric($settings[$key]) || intval($settings[$key]) != $settings[$key] || $settings[$key] < 2) {
            $t_args = [
              '%name' => $element['#title'],
            ];
            $form_state->setErrorByName($element['#markup'], t('%name must be an integer greater than 1.', $t_args));
          }
          else {
            $form_state->setValue($settings[$key], (int) $settings[$key]);
          }
        }
      }
      else {
        $settings[$key] = NULL;
      }
    }
  }

  /**
   * Function of typification options Timepicker.
   *
   * @param array $settings
   *   Settings for JS Timepicker.
   *
   * @return array
   *   return array of changed settings after typefications of all parameters.
   */
  public static function processFieldSettings(array $settings) {
    $options = isset($settings) ? $settings : [];
    if (!empty($options)) {
      $groups = [
        'boolean' => [
          'showLeadingZero',
          'showMinutesLeadingZero',
          'showHours',
          'showMinutes',
          'showCloseButton',
          'showNowButton',
          'showDeselectButton',
        ],
        'int' => [
          'hours',
          'minutes',
          'rows',
          'hour',
          'minute',
          'interval',
          'starts',
          'ends',
        ],
        'no_filtering' => [],
      ];
      // Callback for the array_walk_recursive().
      $filter = function (&$item, $key, $groups) {
        if (in_array($key, $groups['boolean'], TRUE)) {
          if ($item !== NULL) {
            $item = (bool) $item;
          }
        }
        elseif (in_array($key, $groups['int'], TRUE)) {
          if ($item !== NULL) {
            $item = (int) $item;
          }
        }
        elseif (in_array($key, $groups['no_filtering'], TRUE)) {
          // Do nothing.
        }
        else {
          // @todo Use filter_xss_admin() instead?
          $item = Html::escape($item);
        }
      };
      // Filter user submitted settings since plugin builds output by just
      // concatenation of strings so it's possible, for example,
      // to insert html into labels.
      array_walk_recursive($options, $filter, $groups);
    }
    return self::fieldSettingsFinalNullCleanType($options);
  }

  /**
   * Method deleting Null parameters before send to JS.
   *
   * @param array $settings
   *   Non-filter parameters.
   *
   * @return array
   *   Returned filtering Parameters for send to JS.
   */
  public static function fieldSettingsFinalNullCleanType(array &$settings) {
    $new = $settings;
    // Convert boolean settings to boolean.
    $boolean = [
      'showLeadingZero',
      'showMinutesLeadingZero',
      'showHours',
      'showMinutes',
      'showCloseButton',
      'showNowButton',
      'showDeselectButton',
    ];
    foreach ($boolean as $key) {
      $new[$key] = (bool) $settings[$key];
    }
    // Final cleanup.
    $not_null = function ($el) {
      return isset($el);
    };
    foreach (['hours', 'minutes', 'minTime', 'maxTime'] as $key) {
      $new[$key] = array_filter($settings[$key], $not_null);
      if (empty($new[$key])) {
        unset($new[$key]);
      }
    }
    if (!isset($new['rows'])) {
      // Make sure that NULL value is removed from settings.
      unset($new['rows']);
    }
    return $new;
  }

}
