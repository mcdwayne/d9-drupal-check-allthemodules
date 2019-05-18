<?php

namespace Drupal\date_popup_timepicker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;

/**
 * Plugin implementation of the 'datetime_timepicker_list' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timepicker_list",
 *   label = @Translation("Timepicker"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeTimePickerWidget extends DateRangeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $name = $items->getName();
    $name_field[] = $name . '[' . $delta . '][value][time]';
    $name_field[] = $name . '[' . $delta . '][end_value][time]';
    foreach ($name_field as $name) {
      $element['#attached']['library'][] = 'date_popup_timepicker/timepicker';
      $element['#attached']['drupalSettings']['datePopup'][$name] = [
        'settings' => TimePickerWidget::processFieldSettings($this->getSettings()),
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return TimePickerWidget::defaultSettings() + parent::defaultSettings();
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
    return TimePickerWidget::fieldSettingsFormValidate($element, $form_state);
  }

}
